<?php declare(strict_types=1);

namespace Wbm\ProductType\Extension;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Wbm\ProductType\ProductTypeData\WbmProductTypeDataDefinition;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                'wbmProductTypeData',
                'id',
                'product_id',
                WbmProductTypeDataDefinition::class,
                false
            ))->addFlags(new ApiAware(), new CascadeDelete())
        );
    }

    public function getEntityName(): string
    {
        return ProductDefinition::ENTITY_NAME;
    }
}
