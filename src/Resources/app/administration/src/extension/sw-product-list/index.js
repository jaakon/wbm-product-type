const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-product-list', {
    computed: {
        productCriteria() {
            const criteria = this.$super('productCriteria');
            criteria.addAssociation('wbmProductTypeData');

            if (this.term) {
                criteria.addQuery(
                    Criteria.equals('wbmProductTypeData.productType', this.term),
                    500,
                );
                criteria.addQuery(
                    Criteria.contains('wbmProductTypeData.productType', this.term),
                    80,
                );
            }

            return criteria;
        },

        listFilterOptions() {
            const options = this.$super('listFilterOptions');

            options['wbm-product-type-filter'] = {
                property: 'wbmProductTypeData.productType',
                type: 'multi-select-filter',
                label: this.$tc('wbm-product-type.list.filterProductType'),
                placeholder: this.$tc('wbm-product-type.list.filterProductType'),
                options: this.wbmProductTypeOptions,
            };

            return options;
        },
    },

    data() {
        return {
            wbmProductTypeOptions: [],
        };
    },

    created() {
        if (!this.defaultFilters.includes('wbm-product-type-filter')) {
            this.defaultFilters.push('wbm-product-type-filter');
        }

        this.loadWbmProductTypeFilterOptions();
    },

    methods: {
        getProductColumns() {
            const columns = this.$super('getProductColumns');

            columns.push({
                property: 'extensions.wbmProductTypeData.productType',
                label: this.$tc('wbm-product-type.list.columnProductType'),
                allowResize: true,
                sortable: false,
                visible: true,
            });

            return columns;
        },

        async loadWbmProductTypeFilterOptions() {
            const repository = this.repositoryFactory.create('wbm_product_type_data');
            const criteria = new Criteria(1, 1);
            criteria.addAggregation(Criteria.terms('productTypes', 'productType'));

            try {
                const result = await repository.search(criteria, Shopware.Context.api);
                const agg = result.aggregations?.productTypes;

                if (agg?.buckets) {
                    this.wbmProductTypeOptions = agg.buckets
                        .filter((b) => b.key)
                        .map((b) => ({ label: b.key, value: b.key }));
                }
            } catch {
                // filter stays empty if no data available
            }
        },
    },
});
