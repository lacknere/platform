<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Struct;

use Shopware\Core\Framework\Log\Package;

/**
 * @template TData of array = array<array-key, mixed>
 *
 * @implements \ArrayAccess<key-of<TData>, value-of<TData>>
 * @implements \IteratorAggregate<key-of<TData>, value-of<TData>>
 */
#[Package('framework')]
class ArrayStruct extends Struct implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @param TData $data
     */
    public function __construct(
        protected array $data = [],
        protected ?string $apiAlias = null
    ) {
    }

    public function has(string|int $property): bool
    {
        return \array_key_exists($property, $this->data);
    }

    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, mixed $value): void
    {
        /** @phpstan-ignore assign.propertyType (PHPStan cannot recognize the result correctly) */
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    public function get(string|int $key): mixed
    {
        return $this->offsetGet($key);
    }

    public function set(string|int $key, mixed $value): mixed
    {
        /** @phpstan-ignore assign.propertyType (PHPStan cannot recognize the result correctly) */
        return $this->data[$key] = $value;
    }

    /**
     * @param TData $options
     */
    public function assign(array $options)
    {
        /** @phpstan-ignore assign.propertyType (PHPStan cannot recognize the result of the array function correctly) */
        $this->data = array_replace_recursive($this->data, $options);

        return $this;
    }

    /**
     * @return TData
     */
    public function all(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        $jsonArray = parent::jsonSerialize();

        // The key-values pairs from the property $data are now serialized in the JSON property "data".
        // But the key-value pairs from data should appear in the serialization as they were properties of the ArrayStruct itself.
        // Therefore, the key-values moved one level up.
        unset($jsonArray['data']);
        $data = $this->data;
        $this->convertDateTimePropertiesToJsonStringRepresentation($data);

        return array_merge($jsonArray, $data);
    }

    public function getApiAlias(): string
    {
        return $this->apiAlias ?? 'array_struct';
    }

    /**
     * @return TData
     */
    public function getVars(): array
    {
        return $this->data;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        return \count($this->data);
    }
}
