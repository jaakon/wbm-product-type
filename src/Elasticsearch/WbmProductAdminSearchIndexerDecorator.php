<?php declare(strict_types=1);

namespace Wbm\ProductType\Elasticsearch;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IterableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Admin\Indexer\AbstractAdminIndexer;
use Wbm\ProductType\ProductTypeData\WbmProductTypeDataDefinition;

class WbmProductAdminSearchIndexerDecorator extends AbstractAdminIndexer
{
    public function __construct(
        private readonly AbstractAdminIndexer $inner,
        private readonly Connection $connection,
    ) {
    }

    public function getDecorated(): AbstractAdminIndexer
    {
        return $this->inner;
    }

    public function getEntity(): string
    {
        return $this->inner->getEntity();
    }

    public function getName(): string
    {
        return $this->inner->getName();
    }

    public function getIterator(): IterableQuery
    {
        return $this->inner->getIterator();
    }

    public function fetch(array $ids): array
    {
        $documents = $this->inner->fetch($ids);
        $productTypeData = $this->fetchProductTypeData($ids);

        foreach ($documents as $id => &$document) {
            $productType = $productTypeData[$id] ?? null;
            if ($productType !== null) {
                $lowerType = strtolower($productType);
                $document['textBoosted'] .= ' ' . $lowerType;
                $document['text'] .= ' ' . $lowerType;
            }
        }

        return $documents;
    }

    public function globalData(array $result, Context $context): array
    {
        return $this->inner->globalData($result, $context);
    }

    public function getUpdatedIds(EntityWrittenContainerEvent $event): array
    {
        $ids = $this->inner->getUpdatedIds($event);

        $wbmChanges = $event->getPrimaryKeysWithPropertyChange(WbmProductTypeDataDefinition::ENTITY_NAME, ['productType']);

        foreach ($wbmChanges as $change) {
            if (isset($change['productId'])) {
                $ids[] = $change['productId'];
            } elseif (\is_string($change)) {
                $productId = $this->resolveProductId($change);
                if ($productId !== null) {
                    $ids[] = $productId;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string> $ids
     *
     * @return array<string, string>
     */
    private function fetchProductTypeData(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(product_id)) AS id, product_type
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
            if ($row['product_type'] !== null) {
                $result[(string) $row['id']] = (string) $row['product_type'];
            }
        }

        return $result;
    }

    private function resolveProductId(string $wbmDataId): ?string
    {
        $productId = $this->connection->fetchOne(
            'SELECT LOWER(HEX(product_id)) FROM wbm_product_type_data
             WHERE id = :id AND product_version_id = :liveVersionId',
            [
                'id' => Uuid::fromHexToBytes($wbmDataId),
                'liveVersionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
        );

        return $productId !== false ? (string) $productId : null;
    }
}
