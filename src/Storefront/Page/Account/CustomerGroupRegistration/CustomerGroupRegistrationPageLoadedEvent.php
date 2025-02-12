<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Account\CustomerGroupRegistration;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class CustomerGroupRegistrationPageLoadedEvent extends PageLoadedEvent
{
    protected CustomerGroupRegistrationPage $page;

    public function __construct(
        CustomerGroupRegistrationPage $page,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): CustomerGroupRegistrationPage
    {
        return $this->page;
    }
}
