<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Cart\Delivery\Struct;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;

#[Package('checkout')]
class ShippingLocation extends Struct
{
    public function __construct(
        protected CountryEntity $country,
        protected ?CountryStateEntity $state = null,
        protected ?CustomerAddressEntity $address = null
    ) {
    }

    public static function createFromAddress(CustomerAddressEntity $address): self
    {
        \assert($address->getCountry() !== null);

        return new self(
            $address->getCountry(),
            $address->getCountryState(),
            $address
        );
    }

    public static function createFromCountry(CountryEntity $country): self
    {
        return new self($country, null, null);
    }

    public function getCountry(): CountryEntity
    {
        return $this->address?->getCountry() ?? $this->country;
    }

    public function getState(): ?CountryStateEntity
    {
        if ($this->address) {
            return $this->address->getCountryState();
        }

        return $this->state;
    }

    public function getAddress(): ?CustomerAddressEntity
    {
        return $this->address;
    }

    public function getApiAlias(): string
    {
        return 'cart_delivery_shipping_location';
    }
}
