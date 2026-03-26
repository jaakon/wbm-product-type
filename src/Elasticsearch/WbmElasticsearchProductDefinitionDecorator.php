<?php declare(strict_types=1);

namespace Wbm\ProductType\Elasticsearch;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use OpenSearchDSL\BuilderInterface;
use OpenSearchDSL\Query\Compound\BoolQuery;
use OpenSearchDSL\Query\FullText\MatchQuery;
use OpenSearchDSL\Query\Joining\NestedQuery;
use OpenSearchDSL\Query\TermLevel\TermQuery;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Shopware\Elasticsearch\Framework\ElasticsearchFieldBuilder;

class WbmElasticsearchProductDefinitionDecorator extends AbstractElasticsearchDefinition
{
    public function __construct(
        private readonly AbstractElasticsearchDefinition $decorated,
        private readonly Connection $connection,
    ) {
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->decorated->getEntityDefinition();
    }

    public function getMapping(Context $context): array
    {
        $mapping = $this->decorated->getMapping($context);

        $mapping['properties']['wbmProductTypeData'] = ElasticsearchFieldBuilder::nested([
            'productType' => [
                'type' => 'keyword',
                'ignore_above' => 10000,
                ...self::SEARCH_FIELD,
            ],
            'productIdFromApi' => self::INT_FIELD,
        ]);

        return $mapping;
    }

    public function fetch(array $ids, Context $context): array
    {
        $documents = $this->decorated->fetch($ids, $context);

        $productTypeData = $this->fetchProductTypeData(array_keys($documents));

        foreach ($documents as $id => &$document) {
            $data = $productTypeData[$id] ?? null;

            $document['wbmProductTypeData'] = [
                'id' => $data['wbmId'] ?? $id,
                '_count' => 1,
                'productType' => $data['productType'] ?? null,
                'productIdFromApi' => isset($data['productIdFromApi']) ? (int) $data['productIdFromApi'] : null,
            ];
        }

        return $documents;
    }

    public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
    {
        $query = $this->decorated->buildTermQuery($context, $criteria);

        $term = $criteria->getTerm();
        if ($term === null || $term === '') {
            return $query;
        }

        $bool = new BoolQuery();
        $bool->add($query, BoolQuery::SHOULD);
        $bool->add(
            new NestedQuery('wbmProductTypeData', new TermQuery('wbmProductTypeData.productType', $term, ['boost' => 5])),
            BoolQuery::SHOULD,
        );
        $bool->add(
            new NestedQuery('wbmProductTypeData', new MatchQuery('wbmProductTypeData.productType', $term, ['boost' => 3])),
            BoolQuery::SHOULD,
        );

        return $bool;
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string, array{wbmId: string, productType: string|null, productIdFromApi: string|null}>
     */
    private function fetchProductTypeData(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                LOWER(HEX(product_id)) AS id,
                LOWER(HEX(id)) AS wbmId,
                product_type AS productType,
                product_id_from_api AS productIdFromApi
            FROM wbm_product_type_data
            WHERE product_id IN (:ids) AND product_version_id = :liveVersionId',
            [
                'ids' => Uuid::fromHexToBytesList($ids),
                'liveVersionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
            ['ids' => ArrayParameterType::BINARY],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['id']] = $row;
        }

        return $result;
    }
}
