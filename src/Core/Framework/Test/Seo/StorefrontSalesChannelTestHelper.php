<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\Seo;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\CartRuleLoader;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\TestDefaults;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\Container;

trait StorefrontSalesChannelTestHelper
{
    public function getBrowserWithLoggedInCustomer(): KernelBrowser
    {
        $browser = KernelLifecycleManager::createBrowser(KernelLifecycleManager::getKernel(), false);
        $browser->setServerParameters([
            'HTTP_ACCEPT' => 'application/json',
        ]);

        /** @var Container $container */
        $container = static::getContainer();

        /** @var EntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = $container->get('sales_channel.repository');
        $salesChannel = $salesChannelRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT)),
            Context::createDefaultContext()
        )->getEntities()->first();
        TestCase::assertNotNull($salesChannel);

        $header = 'HTTP_' . str_replace('-', '_', mb_strtoupper(PlatformRequest::HEADER_ACCESS_KEY));
        $browser->setServerParameter($header, $salesChannel->getAccessKey());
        $browser->setServerParameter('test-sales-channel-id', $salesChannel->getId());

        $customerId = Uuid::randomHex();
        $this->createCustomerWithEmail($customerId, 'foo@foo.de', 'bar12345', $salesChannel);
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/account/login',
            [
                'username' => 'foo@foo.de',
                'password' => 'bar12345',
            ]
        );

        static::assertSame(200, $browser->getResponse()->getStatusCode());

        return $browser;
    }

    /**
     * @param array<string> $languageIds
     */
    public function createStorefrontSalesChannelContext(
        string $id,
        string $name,
        string $defaultLanguageId = Defaults::LANGUAGE_SYSTEM,
        array $languageIds = [],
        ?string $categoryEntrypoint = null
    ): SalesChannelContext {
        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('sales_channel.repository');
        $languageIds[] = $defaultLanguageId;
        $languageIds = array_unique($languageIds);

        $domains = [];
        $languages = [];

        $paymentMethod = $this->getValidPaymentMethodId();
        $shippingMethod = $this->getValidShippingMethodId();
        $country = $this->getValidCountryId(null);

        foreach ($languageIds as $langId) {
            $languages[] = ['id' => $langId];
            $domains[] = [
                'languageId' => $langId,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => 'http://example.com/' . $name . '/' . $langId,
            ];
        }

        $repo->upsert([[
            'id' => $id,
            'name' => $name,
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
            'accessKey' => Uuid::randomHex(),
            'secretAccessKey' => 'foobar',
            'languageId' => $defaultLanguageId,
            'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
            'currencyId' => Defaults::CURRENCY,
            'paymentMethodId' => $paymentMethod,
            'shippingMethodId' => $shippingMethod,
            'countryId' => $country,
            'currencies' => [['id' => Defaults::CURRENCY]],
            'languages' => $languages,
            'paymentMethods' => [['id' => $paymentMethod]],
            'shippingMethods' => [['id' => $shippingMethod]],
            'countries' => [['id' => $country]],
            'customerGroupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'domains' => $domains,
            'navigationCategoryId' => !$categoryEntrypoint ? $this->getValidCategoryId() : $categoryEntrypoint,
        ]], Context::createDefaultContext());

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $repo->search(new Criteria([$id]), Context::createDefaultContext())->first();

        return $this->createNewContext($salesChannel);
    }

    public function updateSalesChannelNavigationEntryPoint(string $id, string $categoryId): void
    {
        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('sales_channel.repository');

        $repo->update([['id' => $id, 'navigationCategoryId' => $categoryId]], Context::createDefaultContext());
    }

    private function createCustomerWithEmail(string $customerId, string $email, string $password, SalesChannelEntity $salesChannel): CustomerEntity
    {
        /** @var Container $container */
        $container = static::getContainer();

        $defaultBillingAddress = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'name' => 'test',
            'email' => $email,
            'password' => $password,
            'firstName' => 'foo',
            'lastName' => 'bar',
            'groupId' => $salesChannel->getCustomerGroupId(),
            'salutationId' => $this->getValidSalutationId(),
            'salesChannelId' => $salesChannel->getId(),
            'defaultBillingAddress' => [
                'id' => $defaultBillingAddress,
                'countryId' => $salesChannel->getCountryId(),
                'salutationId' => $this->getValidSalutationId(),
                'firstName' => 'foo',
                'lastName' => 'bar',
                'zipcode' => '48599',
                'city' => 'gronau',
                'street' => 'Schillerstr.',
            ],
            'defaultShippingAddressId' => $defaultBillingAddress,
            'customerNumber' => 'asdf',
        ];

        $customerRepository = $container->get('customer.repository');
        $customerRepository->upsert([$customer], Context::createDefaultContext());

        $customer = $customerRepository->search(new Criteria([$customerId]), Context::createDefaultContext())->first();

        static::assertInstanceOf(CustomerEntity::class, $customer);

        return $customer;
    }

    private function createNewContext(SalesChannelEntity $salesChannel): SalesChannelContext
    {
        $factory = static::getContainer()->get(SalesChannelContextFactory::class);

        $context = $factory->create(Uuid::randomHex(), $salesChannel->getId(), []);

        $ruleLoader = static::getContainer()->get(CartRuleLoader::class);
        $ruleLoader->loadByToken($context, $context->getToken());

        return $context;
    }
}
