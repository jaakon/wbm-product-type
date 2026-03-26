import template from './sw-product-detail-specifications.html.twig';

const { Component } = Shopware;

Component.override('sw-product-detail-specifications', {
    template,

    inject: ['repositoryFactory'],

    computed: {
        wbmProductTypeData() {
            return this.product?.extensions?.wbmProductTypeData ?? null;
        },

        wbmProductIdFromApi() {
            return String(this.wbmProductTypeData?.productIdFromApi ?? '');
        },

        wbmProductType() {
            return this.wbmProductTypeData?.productType ?? '';
        },

        wbmProductTypeDataRepository() {
            return this.repositoryFactory.create('wbm_product_type_data');
        },
    },

    methods: {
        onWbmProductTypeInput(value) {
            if (!this.product.extensions.wbmProductTypeData) {
                this.product.extensions.wbmProductTypeData =
                    this.wbmProductTypeDataRepository.create();
            }

            this.product.extensions.wbmProductTypeData.productType = value || null;
        },
    },
});
