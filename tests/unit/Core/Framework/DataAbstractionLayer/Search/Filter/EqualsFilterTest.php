<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\Filter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * @internal
 */
#[CoversClass(EqualsFilter::class)]
class EqualsFilterTest extends TestCase
{
    public function testEncode(): void
    {
        $filter = new EqualsFilter('foo', 'bar');

        static::assertEquals(
            [
                'field' => 'foo',
                'value' => 'bar',
                'isPrimary' => false,
                'resolved' => null,
                'extensions' => [],
                '_class' => EqualsFilter::class,
            ],
            $filter->jsonSerialize()
        );
    }

    public function testClone(): void
    {
        $filter = new EqualsFilter('foo', 'bar');
        $clone = clone $filter;

        static::assertEquals($filter->jsonSerialize(), $clone->jsonSerialize());
        static::assertEquals($filter->getField(), $clone->getField());
        static::assertEquals($filter->getFields(), $clone->getFields());
        static::assertEquals($filter->getValue(), $clone->getValue());
        static::assertNotSame($filter, $clone);
    }
}
