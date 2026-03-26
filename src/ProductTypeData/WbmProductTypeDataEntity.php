<?php declare(strict_types=1);

namespace Wbm\ProductType\ProductTypeData;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class WbmProductTypeDataEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;

    protected ?int $productIdFromApi = null;

    protected ?string $productType = null;

    protected ?ProductEntity $product = null;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductIdFromApi(): ?int
    {
        return $this->productIdFromApi;
    }

    public function setProductIdFromApi(?int $productIdFromApi): void
    {
        $this->productIdFromApi = $productIdFromApi;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): void
    {
        $this->productType = $productType;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }
}
