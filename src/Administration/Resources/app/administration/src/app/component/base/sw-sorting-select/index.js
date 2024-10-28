/**
 * @package admin
 */

import template from './sw-sorting-select.html.twig';
import './sw-sorting-select.scss';

const { Component } = Shopware;
const SORT_DIRECTION_ASC = 'ASC';
const SORT_DIRECTION_DESC = 'DESC';
const SORT_DIRECTION_NONE = 'NONE';

/**
 * @private
 */
Component.register('sw-sorting-select', {
    template,

    compatConfig: Shopware.compatConfig,

    emits: ['sorting-changed'],

    props: {
        sortBy: {
            type: String,
            required: true,
        },

        sortDirection: {
            type: String,
            required: false,
            validValues: [
                SORT_DIRECTION_ASC,
                SORT_DIRECTION_DESC,
            ],
            validator(value) {
                return [
                    SORT_DIRECTION_ASC,
                    SORT_DIRECTION_DESC,
                ].includes(value);
            },
        },

        /**
         * @deprecated tag:v6.7.0 - additionalSortOptions will be removed. Use `sortOptions` directly instead.
         */
        additionalSortOptions: {
            type: Array,
            default: () => [],
            required: false,
        },

        sortOptions: {
            type: Array,
            default: () => [
                {
                    sortBy: 'name',
                },
                {
                    sortBy: 'createdAt',
                },
                {
                    sortBy: 'updatedAt',
                },
            ],
            required: false,
        },

        label: {
            type: String,
            required: false,
            default: '',
        },

        aside: {
            type: Boolean,
            default: false,
            required: false,
        },

        component: {
            type: String,
            required: false,
            default: 'sw-select-field',
            validValues: [
                'sw-select-field',
                'sw-single-select',
            ],
            validator(value) {
                return [
                    'sw-select-field',
                    'sw-single-select',
                ].includes(value);
            },
        }
    },

    computed: {
        resolvedSortOptions() {
            const sortOptions = [];

            this.sortOptions.forEach((sortOption) => {
                if (sortOption.value) {
                    if (sortOption.value.includes(':')) {
                        const [sortBy, sortDirection] = sortOption.value.split(':');

                        sortOptions.push({
                            name: sortOption.name ?? sortOption.label,
                            sortBy,
                            sortDirection,
                        });

                        return;
                    }

                    sortOptions.push({
                        name: sortOption.name ?? sortOption.label,
                        sortBy: sortOption.value,
                        sortDirection: SORT_DIRECTION_NONE,
                    });

                    return;
                }

                if (sortOption.directionSortable ?? true) {
                    sortOptions.push({
                        ...sortOption,
                        sortDirection: SORT_DIRECTION_ASC,
                    });

                    sortOptions.push({
                        ...sortOption,
                        sortDirection: SORT_DIRECTION_DESC,
                    });

                    return;
                }

                sortOptions.push({
                    ...sortOption,
                    sortDirection: SORT_DIRECTION_NONE,
                });
            });

            /**
             * @deprecated tag:v6.7.0 - additionalSortOptions will be removed.
             */
            this.additionalSortOptions.forEach((sortOption) => {
                if (sortOption.value.includes(':')) {
                    const [sortBy, sortDirection] = sortOption.value.split(':');

                    sortOptions.push({
                        ...sortOption,
                        sortBy,
                        sortDirection,
                    });

                    return;
                }

                sortOptions.push({
                    ...sortOption,
                    sortBy: sortOption.value,
                    sortDirection: SORT_DIRECTION_NONE,
                });
            });

            return sortOptions;
        },

        sortingConditionConcatenation() {
            return this.getValue({
                sortBy: this.sortBy,
                sortDirection: this.sortDirection ?? SORT_DIRECTION_NONE,
            });
        },
    },

    methods: {
        getValue(option) {
            if (option.sortDirection === SORT_DIRECTION_NONE) {
                return option.sortBy;
            }

            return `${option.sortBy}:${option.sortDirection}`;
        },

        getLabel(option) {
            if (option.name) {
                return option.name;
            }

            const snippetKey = `sw-sorting-select.sortBy.label.${option.sortBy}`;
            let label = option.sortBy;

            if (option.label) {
                label = this.$te(option.label) ? this.$tc(option.label) : option.label;
            } else if (this.$te(snippetKey)) {
                label = this.$tc(snippetKey);
            }

            if (option.sortDirection === SORT_DIRECTION_NONE) {
                return label;
            }

            return `${label}, ${this.$tc(`sw-sorting-select.sortDirection.label.${option.sortDirection}`)}`;
        },

        onSortingChanged(value) {
            let sortBy = value;
            let sortDirection = null;

            if (value.includes(':')) {
                [
                    sortBy,
                    sortDirection,
                ] = value.split(':');
            }

            this.$emit('sorting-changed', { sortBy, sortDirection, value });
        },
    },
});
