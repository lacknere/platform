<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Search\Parser;

use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidFilterQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\SearchRequestException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Aggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MinAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\RangeAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\StatsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
class AggregationParser
{
    /**
     * @param array<string, mixed> $payload
     */
    public function buildAggregations(EntityDefinition $definition, array $payload, Criteria $criteria, SearchRequestException $searchRequestException): void
    {
        if (!\is_array($payload['aggregations'])) {
            throw DataAbstractionLayerException::invalidAggregationQuery('The aggregations parameter has to be a list of aggregations.');
        }

        foreach ($payload['aggregations'] as $index => $aggregation) {
            $parsed = $this->parseAggregation($index, $definition, $aggregation, $searchRequestException);

            if ($parsed) {
                $criteria->addAggregation($parsed);
            }
        }
    }

    /**
     * @param array<Aggregation> $aggregations
     *
     * @return array<array<string, mixed>>
     */
    public function toArray(array $aggregations): array
    {
        $data = [];

        foreach ($aggregations as $aggregation) {
            $data[] = $this->aggregationToArray($aggregation);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function aggregationToArray(Aggregation $aggregation): array
    {
        if ($aggregation instanceof AvgAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'avg',
                'field' => $aggregation->getField(),
            ];
        }
        if ($aggregation instanceof MaxAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'max',
                'field' => $aggregation->getField(),
            ];
        }
        if ($aggregation instanceof MinAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'min',
                'field' => $aggregation->getField(),
            ];
        }
        if ($aggregation instanceof StatsAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'stats',
                'field' => $aggregation->getField(),
            ];
        }
        if ($aggregation instanceof SumAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'sum',
                'field' => $aggregation->getField(),
            ];
        }
        if ($aggregation instanceof CountAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'count',
                'field' => $aggregation->getField(),
            ];
        }
        if ($aggregation instanceof EntityAggregation) {
            return [
                'name' => $aggregation->getName(),
                'type' => 'entity',
                'field' => $aggregation->getField(),
                'definition' => $aggregation->getEntity(),
            ];
        }
        if ($aggregation instanceof FilterAggregation) {
            $filters = [];
            foreach ($aggregation->getFilter() as $filter) {
                $filters[] = QueryStringParser::toArray($filter);
            }

            $aggregationArray = [
                'name' => $aggregation->getName(),
                'type' => 'filter',
                'filter' => $filters,
            ];

            $nestedAggregation = $aggregation->getAggregation();
            if ($nestedAggregation) {
                $aggregationArray['aggregation'] = $this->aggregationToArray($nestedAggregation);
            }

            return $aggregationArray;
        }
        if ($aggregation instanceof DateHistogramAggregation) {
            $data = [
                'name' => $aggregation->getName(),
                'type' => 'histogram',
                'interval' => $aggregation->getInterval(),
                'format' => $aggregation->getFormat(),
                'field' => $aggregation->getField(),
                'timeZone' => $aggregation->getTimeZone(),
            ];

            if ($aggregation->getSorting()) {
                $data['sort'] = [
                    'order' => $aggregation->getSorting()->getDirection(),
                    'naturalSorting' => $aggregation->getSorting()->getNaturalSorting(),
                    'field' => $aggregation->getSorting()->getField(),
                ];
            }

            if ($aggregation->getAggregation()) {
                $data['aggregation'] = $this->aggregationToArray($aggregation->getAggregation());
            }

            return $data;
        }

        if ($aggregation instanceof TermsAggregation) {
            $data = [
                'name' => $aggregation->getName(),
                'type' => 'terms',
                'field' => $aggregation->getField(),
            ];

            if ($aggregation->getSorting()) {
                $data['sort'] = [
                    'order' => $aggregation->getSorting()->getDirection(),
                    'naturalSorting' => $aggregation->getSorting()->getNaturalSorting(),
                    'field' => $aggregation->getSorting()->getField(),
                ];
            }

            if ($aggregation->getAggregation()) {
                $data['aggregation'] = $this->aggregationToArray($aggregation->getAggregation());
            }

            return $data;
        }

        throw DataAbstractionLayerException::invalidAggregationQuery(\sprintf('The aggregation of type "%s" is not supported.', $aggregation::class));
    }

    /**
     * @param array<string, mixed> $aggregation
     */
    private function parseAggregation(int $index, EntityDefinition $definition, array $aggregation, SearchRequestException $exceptions): ?Aggregation
    {
        $name = \array_key_exists('name', $aggregation) ? (string) $aggregation['name'] : null;

        if (empty($name) || is_numeric($name)) {
            $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation name should be a non-empty string.'), '/aggregations/' . $index);

            return null;
        }

        if (str_contains($name, '?') || str_contains($name, ':')) {
            $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation name should not contain a question mark or colon.'), '/aggregations/' . $index);

            return null;
        }

        $type = $aggregation['type'] ?? null;

        if (!\is_string($type) || empty($type) || is_numeric($type)) {
            $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregations of "%s" should be a non-empty string.'), '/aggregations/' . $index);

            return null;
        }

        if (empty($aggregation['field']) && $type !== 'filter') {
            $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation should contain a "field".'), '/aggregations/' . $index . '/' . $type . '/field');

            return null;
        }

        $field = '';
        if ($type !== 'filter') {
            $field = self::buildFieldName($definition, $aggregation['field']);
        }
        switch ($type) {
            case 'avg':
                return new AvgAggregation($name, $field);
            case 'max':
                return new MaxAggregation($name, $field);
            case 'min':
                return new MinAggregation($name, $field);
            case 'stats':
                return new StatsAggregation($name, $field);
            case 'sum':
                return new SumAggregation($name, $field);
            case 'count':
                return new CountAggregation($name, $field);
            case 'range':
                if (!isset($aggregation['ranges'])) {
                    $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation should contain "ranges".'), '/aggregations/' . $index . '/' . $type . '/field');

                    return null;
                }

                return new RangeAggregation($name, $field, $aggregation['ranges']);
            case 'entity':
                if (!isset($aggregation['definition'])) {
                    $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation should contain a "definition".'), '/aggregations/' . $index . '/' . $type . '/field');

                    return null;
                }

                return new EntityAggregation($name, $field, $aggregation['definition']);

            case 'filter':
                if (empty($aggregation['filter'])) {
                    $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation should contain an array of filters in property "filter".'), '/aggregations/' . $index . '/' . $type . '/field');

                    return null;
                }
                if (empty($aggregation['aggregation'])) {
                    $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation should contain an array of filters in property "filter".'), '/aggregations/' . $index . '/' . $type . '/field');

                    return null;
                }
                $filters = [];

                foreach ($aggregation['filter'] as $filterIndex => $query) {
                    try {
                        $filters[] = QueryStringParser::fromArray($definition, $query, $exceptions, '/filter/' . $filterIndex);
                    } catch (InvalidFilterQueryException $ex) {
                        $exceptions->add($ex, $ex->getParameters()['path']);
                    }
                }

                $nested = $this->parseAggregation($index, $definition, $aggregation['aggregation'], $exceptions);
                if ($nested === null) {
                    return null;
                }

                return new FilterAggregation($name, $nested, $filters);

            case 'histogram':
                $nested = null;
                $sorting = null;

                if (!isset($aggregation['interval'])) {
                    $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery('The aggregation should contain an date interval.'), '/aggregations/' . $index . '/' . $type . '/interval');

                    return null;
                }

                $interval = $aggregation['interval'];
                $format = $aggregation['format'] ?? null;
                $timeZone = $aggregation['timeZone'] ?? null;

                if (isset($aggregation['aggregation'])) {
                    $nested = $this->parseAggregation($index, $definition, $aggregation['aggregation'], $exceptions);
                }
                if (isset($aggregation['sort'])) {
                    $sort = $aggregation['sort'];
                    $order = $sort['order'] ?? FieldSorting::ASCENDING;
                    $naturalSorting = $sort['naturalSorting'] ?? false;

                    if (strcasecmp((string) $order, 'desc') === 0) {
                        $order = FieldSorting::DESCENDING;
                    } else {
                        $order = FieldSorting::ASCENDING;
                    }

                    $sorting = new FieldSorting($sort['field'], $order, (bool) $naturalSorting);
                }

                return new DateHistogramAggregation($name, $field, $interval, $sorting, $nested, $format, $timeZone);

            case 'terms':
                $nested = null;
                $limit = null;
                $sorting = null;

                if (isset($aggregation['aggregation'])) {
                    $nested = $this->parseAggregation($index, $definition, $aggregation['aggregation'], $exceptions);
                }

                if (isset($aggregation['limit'])) {
                    $limit = (int) $aggregation['limit'];
                }
                if (isset($aggregation['sort'])) {
                    $sort = $aggregation['sort'];
                    $order = $sort['order'] ?? FieldSorting::ASCENDING;
                    $naturalSorting = $sort['naturalSorting'] ?? false;

                    if (strcasecmp((string) $order, 'desc') === 0) {
                        $order = FieldSorting::DESCENDING;
                    } else {
                        $order = FieldSorting::ASCENDING;
                    }

                    $sorting = new FieldSorting($sort['field'], $order, (bool) $naturalSorting);
                }

                return new TermsAggregation($name, $field, $limit, $sorting, $nested);

            default:
                $exceptions->add(DataAbstractionLayerException::invalidAggregationQuery(\sprintf('The aggregation type "%s" used as key does not exist.', $type)), '/aggregations/' . $index);

                return null;
        }
    }

    private static function buildFieldName(EntityDefinition $definition, string $fieldName): string
    {
        $prefix = $definition->getEntityName() . '.';

        if (!str_contains($fieldName, $prefix)) {
            return $prefix . $fieldName;
        }

        return $fieldName;
    }
}
