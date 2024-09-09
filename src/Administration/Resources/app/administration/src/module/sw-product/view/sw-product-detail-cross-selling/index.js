/*
 * @package inventory
 */

import template from './sw-product-detail-cross-selling.html.twig';
import './sw-product-detail-cross-selling.scss';

const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Shopware.compatConfig,

    inject: ['repositoryFactory', 'acl'],

    props: {
        allowEdit: {
            type: Boolean,
            required: false,
            // eslint-disable-next-line vue/no-boolean-default
            default: true,
        },
    },

    data() {
        return {
            crossSelling: null,
            isInherited: false,
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product',
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading',
            'isChild',
        ]),

        ...mapGetters('context', [
            'isSystemDefaultLanguage',
        ]),

        showCrossSellingCard() {
            return !this.isLoading && this.product.crossSellings && this.product.crossSellings.length > 0;
        },

        onAddCrossSellingTooltipMessage() {
            if (this.isSystemDefaultLanguage) {
                return this.$tc('sw-privileges.tooltip.warning');
            }

            return this.$tc('sw-product.crossselling.buttonAddCrossSellingLanguageWarning');
        },

        assetFilter() {
            return Shopware.Filter.getByName('asset');
        },
    },

    watch: {
        product(product) {
            product.crossSellings.forEach((item) => {
                if (item.assignedProducts.length > 0) {
                    return;
                }

                this.loadAssignedProducts(item);
            });
        },

        'product.crossSellings': {
            handler(value) {
                if (!value) {
                    return;
                }

                this.isInherited = this.isChild && !this.product.crossSellings.total;
            },
            immediate: true,
        },
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        mountedComponent() {
            this.isInherited = this.isChild && !this.product.crossSellings.total;
        },

        loadAssignedProducts(crossSelling) {
            const repository = this.repositoryFactory.create(
                crossSelling.assignedProducts.entity,
                crossSelling.assignedProducts.source,
            );

            const criteria = new Criteria(1, 25);
            criteria.addFilter(Criteria.equals('crossSellingId', crossSelling.id))
                .addSorting(Criteria.sort('position', 'ASC'))
                .addAssociation('product');

            repository.search(
                criteria,
                { ...Shopware.Context.api, inheritance: true },
            ).then((assignedProducts) => {
                crossSelling.assignedProducts = assignedProducts;
            });

            return crossSelling;
        },

        onAddCrossSelling() {
            const crossSellingRepository = this.repositoryFactory.create(
                this.product.crossSellings.entity,
                this.product.crossSellings.source,
            );
            this.crossSelling = crossSellingRepository.create();
            this.crossSelling.productId = this.product.id;
            this.crossSelling.position = this.product.crossSellings.length + 1;
            this.crossSelling.type = 'productStream';
            this.crossSelling.sortBy = 'name';
            this.crossSelling.sortDirection = 'ASC';
            this.crossSelling.limit = 24;

            this.product.crossSellings.push(this.crossSelling);
        },

        restoreInheritance() {
            this.isInherited = true;
        },

        removeInheritance() {
            this.isInherited = false;
        },
    },
};
