<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Kernel;

use Shopware\Core\Framework\Event\BeforeSendRedirectResponseEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\CanonicalRedirectService;
use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernel as SymfonyHttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('framework')]
class HttpKernel extends SymfonyHttpKernel
{
    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ControllerResolverInterface $resolver,
        RequestStack $requestStack,
        ArgumentResolverInterface $argumentResolver,
        private readonly RequestTransformerInterface $requestTransformer,
        private readonly CanonicalRedirectService $canonicalRedirectService,
    ) {
        parent::__construct($dispatcher, $resolver, $requestStack, $argumentResolver);
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        if ($request->attributes->get('exception') !== null) {
            return parent::handle($request, $type, $catch);
        }

        if (!$request->attributes->has('sw-skip-transformer')) {
            try {
                $request = $this->requestTransformer->transform($request);
            } catch (\Throwable $e) {
                $event = new ExceptionEvent($this, $request, $type, $e);

                $this->dispatcher->dispatch($event, KernelEvents::EXCEPTION);

                if ($event->getResponse()) {
                    return $event->getResponse();
                }

                throw $e;
            }
        }

        $redirect = $this->canonicalRedirectService->getRedirect($request);

        // move redirect to service
        if ($redirect instanceof RedirectResponse) {
            $event = new BeforeSendRedirectResponseEvent($request, $redirect);
            $this->dispatcher->dispatch($event);

            return $event->getResponse();
        }

        return parent::handle($request, $type, $catch);
    }
}
