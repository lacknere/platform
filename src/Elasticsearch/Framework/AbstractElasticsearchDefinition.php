<?php declare(strict_types=1);

namespace Shopware\Elasticsearch\Framework;

use OpenSearchDSL\BuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;

#[Package('framework')]
abstract class AbstractElasticsearchDefinition
{
    final public const KEYWORD_FIELD = [
        'type' => 'keyword',
        'ignore_above' => 10000,
        'normalizer' => 'sw_lowercase_normalizer',
    ];

    final public const BOOLEAN_FIELD = ['type' => 'boolean'];

    final public const FLOAT_FIELD = ['type' => 'double'];

    final public const INT_FIELD = ['type' => 'long'];

    final public const SEARCH_FIELD = [
        'fields' => [
            'search' => ['type' => 'text', 'analyzer' => 'sw_whitespace_analyzer'],
            'ngram' => ['type' => 'text', 'analyzer' => 'sw_ngram_analyzer'],
        ],
    ];

    abstract public function getEntityDefinition(): EntityDefinition;

    /**
     * @return array{_source?: array{includes: string[]}, properties: array<mixed>}
     */
    abstract public function getMapping(Context $context): array;

    /**
     * Can be used to define custom queries to define the data to be indexed.
     */
    public function getIterator(): ?IterableQuery
    {
        return null;
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string, array<string, mixed>>
     */
    public function fetch(array $ids, Context $context): array
    {
        return [];
    }

    abstract public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface;

    /**
     * @return array<string, mixed>
     */
    protected static function getTextFieldConfig(): array
    {
        return self::KEYWORD_FIELD + self::SEARCH_FIELD;
    }
}
