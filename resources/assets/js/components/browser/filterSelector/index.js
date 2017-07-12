var Vue = require('vue')
var url = require('url')
var _   = require('underscore')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['scope', 'hide'],
    components: {
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            loading: false,
            categoriesStatus: null,
            modal: null,
            searchLoading: {
                asset: false,
                category: false,
                seller: false,
                origin: false,
            },
            searchRequests: {
                asset: null,
                category: null,
                seller: null,
                origin: null,
            }
        }
    },
    created: function() {

        // Listen to open event
        this.$root.bus.$on('filterSelector.open', this.onOpen);

    },
    mounted: function () {

        // Instantiate modal
        this.modal = $('[data-remodal-id=filters]').remodal({
            hashTracking: false
        });

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        // This took up a good portion of my life that I will never get back :(
        this.modal.destroy();

        // Unlisten to open event
        this.$root.bus.$off('filterSelector.open', this.onOpen);

    },
    methods: {

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Return whether or not this field should be hidden.
         */
        isHidden: function (field)
        {
            if ('undefined' == typeof this.hide || ! this.hide) {
                return false;
            }

            return _.contains(this.hide, field);
        },

        /**
         * Clear the applied filters.
         */
        clearFilters: function ()
        {
            this.scope.reset()
            this.closeModal()
        },

        /**
         * Listen to open event.
         */
        onOpen: function () {
            this.modal.open()
        },

        /**
         * Generic search options method.
         */
        searchFilterOptions: function (query, field)
        {
            // Don't do anything if no query provided or undersized
            if (query.length <= 2) return

            // Set loading flag
            this.searchLoading[field] = true

            // Cancel previous request if required
            if (this.searchRequests[field]) {
                this.searchRequests[field].abort()
            }

            // Make a post search
            this.$http.post('discoveries/filters/' + field, {
                query: query
            }, {
                before: function (request) {
                    this.searchRequests[field] = request
                }
            })
                .then(function (response) {

                    this.scope.filterOptions[field] = response.data
                    this.searchLoading[field] = false

                }, function (response) {

                    this.$root.bus.$emit('error', 'Could not search ' + field + ' filter options')
                    this.searchLoading[field] = false

                })
        },

        /**
         * Add query tag event.
         */
        addQueryTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.scope.filterOptions.query, newTag)) return

            // Push tag into scope
            this.scope.filterOptions.query.push(newTag)
            this.scope.filters.query.push(newTag)
        },

        /**
         * Add category tag event.
         */
        addCategoryTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.scope.filterOptions.category, newTag)) return

            // Push tag into scope
            this.scope.filterOptions.category.push(newTag)
            this.scope.filters.category.push(newTag)
        },

        /**
         * Add origin tag event.
         */
        addOriginTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.scope.filterOptions.origin, newTag)) return

            // Push tag into scope
            this.scope.filterOptions.origin.push(newTag)
            this.scope.filters.origin.push(newTag)
        },

        /**
         * Load all categories for dropdown.
         */
        loadCategories: function ()
        {
            // Already loading or loaded, bail out
            if (this.categoriesStatus) return

            // Update categories status
            this.categoriesStatus = 'loading'

            //Make API request
            this.$http.get('discoveries/filters/category')
                .then(function (response) {

                    // Replace filter options
                    this.scope.filterOptions.category = _.map(response.data, function (row) {
                        return {
                            id: row.category,
                            label: '(' + row.count + ') ' + row.category
                        }
                    })

                    // Update categories status
                    this.categoriesStatus = 'loaded'

                }.bind(this), function (response) {

                    // Clear categories status
                    this.categoriesStatus = null

                    // Emit an error
                    this.$root.bus.$emit('error', 'The category list could not be loaded');

                })
        }

    }
})
