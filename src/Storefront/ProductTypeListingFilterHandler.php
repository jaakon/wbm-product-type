<?php declare(strict_types=1);

namespace Wbm\ProductType\Storefront;

use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter\AbstractListingFilterHandler;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class ProductTypeListingFilterHandler extends AbstractListingFilterHandler
{
    final public const FILTER_ENABLED_REQUEST_PARAM = 'product-type-filter';

    public function getDecorated(): AbstractListingFilterHandler
    {
        throw new DecorationPatternException(self::class);
    }

    public function create(Request $request, SalesChannelContext $context): ?Filter
    {
        if (!$request->request->get(self::FILTER_ENABLED_REQUEST_PARAM, true)) {
            return null;
        }

        $values = $this->getProductTypes($request);

        return new Filter(
            'product-type',
            $values !== [],
            [new TermsAggregation('product-type', 'product.wbmProductTypeData.productType')],
            new EqualsAnyFilter('product.wbmProductTypeData.productType', $values),
            $values,
        );
    }

    /**
     * @return list<string>
     */
    private function getProductTypes(Request $request): array
    {
        $types = $request->query->get('product-type', '');
        if ($request->isMethod(Request::METHOD_POST)) {
            $types = $request->request->get('product-type', '');
        }

        if (\is_string($types)) {
            $types = explode('|', $types);
        }

        /** @var list<non-falsy-string> $types */
        $types = array_filter((array) $types);

        return $types;
    }
}
