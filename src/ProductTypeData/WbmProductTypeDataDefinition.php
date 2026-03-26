<?php declare(strict_types=1);

namespace Wbm\ProductType\ProductTypeData;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class WbmProductTypeDataDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'wbm_product_type_data';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return WbmProductTypeDataEntity::class;
    }

    public function getCollectionClass(): string
    {
        return WbmProductTypeDataCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new IntField('product_id_from_api', 'productIdFromApi'))->addFlags(new ApiAware()),
            (new StringField('product_type', 'productType'))->addFlags(new ApiAware()),
            new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, false),
        ]);
    }
}
