<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Cart\Price\Struct;

use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Util\FloatComparator;

#[Package('checkout')]
class CartPrice extends Struct
{
    final public const TAX_STATE_GROSS = 'gross';
    final public const TAX_STATE_NET = 'net';
    final public const TAX_STATE_FREE = 'tax-free';

    protected float $rawTotal;

    public function __construct(
        protected float $netPrice,
        protected float $totalPrice,
        protected float $positionPrice,
        protected CalculatedTaxCollection $calculatedTaxes,
        protected TaxRuleCollection $taxRules,
        protected string $taxStatus,
        ?float $rawTotal = null
    ) {
        $this->netPrice = FloatComparator::cast($netPrice);
        $this->totalPrice = FloatComparator::cast($totalPrice);
        $this->positionPrice = FloatComparator::cast($positionPrice);
        $rawTotal ??= $totalPrice;
        $this->rawTotal = FloatComparator::cast($rawTotal);
    }

    public function getNetPrice(): float
    {
        return $this->netPrice;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getCalculatedTaxes(): CalculatedTaxCollection
    {
        return $this->calculatedTaxes;
    }

    public function setCalculatedTaxes(CalculatedTaxCollection $calculatedTaxes): void
    {
        $this->calculatedTaxes = $calculatedTaxes;
    }

    public function getTaxRules(): TaxRuleCollection
    {
        return $this->taxRules;
    }

    public function getPositionPrice(): float
    {
        return $this->positionPrice;
    }

    public function getTaxStatus(): string
    {
        return $this->taxStatus;
    }

    public function hasNetPrices(): bool
    {
        return \in_array($this->taxStatus, [self::TAX_STATE_NET, self::TAX_STATE_FREE], true);
    }

    public function isTaxFree(): bool
    {
        return $this->taxStatus === self::TAX_STATE_FREE;
    }

    public static function createEmpty(string $taxState = self::TAX_STATE_GROSS): CartPrice
    {
        return new self(0, 0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), $taxState);
    }

    public function getApiAlias(): string
    {
        return 'cart_price';
    }

    public function getRawTotal(): float
    {
        return $this->rawTotal;
    }
}
