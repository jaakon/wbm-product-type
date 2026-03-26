<?php declare(strict_types=1);

namespace Wbm\ProductType\ProductTypeData;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<WbmProductTypeDataEntity>
 */
class WbmProductTypeDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WbmProductTypeDataEntity::class;
    }
}
