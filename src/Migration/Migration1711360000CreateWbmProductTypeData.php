<?php declare(strict_types=1);

namespace Wbm\ProductType\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1711360000CreateWbmProductTypeData extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1711360000;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `wbm_product_type_data` (
                `id`                 BINARY(16)   NOT NULL,
                `product_id`         BINARY(16)   NOT NULL,
                `product_version_id` BINARY(16)   NOT NULL,
                `product_id_from_api` INT          NULL,
                `product_type`       VARCHAR(255) NULL,
                `created_at`         DATETIME(3)  NOT NULL,
                `updated_at`         DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.wbm_product_type_data.product_id` (`product_id`, `product_version_id`),
                INDEX `idx.wbm_product_type_data.product_type` (`product_type`),
                CONSTRAINT `fk.wbm_product_type_data.product_id`
                    FOREIGN KEY (`product_id`, `product_version_id`)
                    REFERENCES `product` (`id`, `version_id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }
}
