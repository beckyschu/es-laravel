var Vue = require('vue')
var _   = require('underscore')

import numeral from 'numeral'

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth', 'scope'],
    components: {
        'filter-summary':  require('../../filterSummary'),
        'filter-selector': require('../../filterSelector'),
        'status-updator':  require('../../statusUpdator'),
        'sort-header':     require('../../sortHeader'),
        'seller-row':      require('./sellerRow')
    },
    data: function () {
        return {
            initiated: false,
            loading: true,
            request: null, //Keep track of xhr so we can abort requests
            page: 1,
            hasMorePages: false,
            total: null,
            sort: {
                key: 'last_seen_at',
                dir: 'desc'
            },
            sellers: [],
            selectedRows: [],
        }
    },
    computed: {
        formattedTotal: function ()
        {
            return numeral(this.total).format('0,0')
        }
    },
    created: function ()
    {
        // Listen to events
        this.$root.bus.$on('data.reload', this.onDataReload)
        this.$root.bus.$on('search', this.onSearch)
        this.$root.bus.$on('clearFilters', this.onClearFilters)
        this.$root.bus.$on('rowToggled', this.onRowToggled)

        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Sellers')

        // Update listing when sort changes
        this.$watch(function () {
            return JSON.stringify(this.sort)
        }, function (newVal, oldVal) {
            this.$root.bus.$emit('data.reload');
        })

        // Fetch initial seller data
        this.fetchSellers()
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('data.reload', this.onDataReload)
        this.$root.bus.$off('search', this.onSearch)
        this.$root.bus.$off('clearFilters', this.onClearFilters)
        this.$root.bus.$off('rowToggled', this.onRowToggled)
    },
    methods: {

        /**
         * Fetch sellers according to current scope.
         */
        fetchSellers: function () {

            //Set loading flag
            this.loading = true

            //Clear sellers
            this.sellers = []

            //Clear selections
            this.selectedRows = []

            // Build request options
            let options = {
                beforeSend: function (request) {
                    if (this.request) {
                        this.request.cancel()
                    }

                    this.request = request;
                },
                params: _.extend(
                    {page: this.page},
                    this.scope.buildPayload()
                )
            }

            // Append sort
            if (this.sort.key) {
                let sortKey = ('desc' == this.sort.dir) ? '-' + this.sort.key : this.sort.key
                options.params.sort = sortKey
            }

            //Make API request
            this.$http.get('sellers', options)
                .then(function (response) {

                    //Set data from meta
                    this.total = response.data.meta.total
                    this.page  = response.data.meta.page

                    //Set has more pages
                    this.hasMorePages = (null !== response.data.links.next)

                    //Loop through sellers
                    _.each(response.data.data, function (seller) {

                        //Fetch account
                        var account = null
                        if (seller.relationships.account && seller.relationships.account.data) {
                            var accountId = seller.relationships.account.data.id;
                            account = _.find(response.data.included, function (include) {
                                return 'accounts' == include.type && accountId == include.id;
                            });
                        }

                        //Push seller data on to stack
                        this.sellers.push(_.extend(
                            {
                                id: seller.id,
                                account: account ? _.extend({id: account.id}, account.attributes) : null,
                                discovery_count: seller.relationships.discoveries.meta.count
                            },
                            seller.attributes
                        ));

                    }.bind(this))

                    //Clear loading flag
                    this.loading = false

                    //Set init flag
                    this.initiated = true

                    //Clear xhr
                    this.request = null

                }.bind(this), function (response) {

                    //Clear loading flag
                    this.loading = false

                    //Clear xhr
                    this.request = null

                    //Send generic error (if we actually sent the request)
                    if (0 < response.status) {
                        this.$root.bus.$emit('error');
                    }

                })

        },

        /**
         * Advance to next page.
         */
        nextPage: function () {

            //Increase page counter
            this.page++

            //Fetch new sellers
            this.fetchSellers()

        },

        /**
         * Go back to previous page.
         */
        prevPage: function () {

            //Decrease page counter
            this.page--

            //Fetch new sellers
            this.fetchSellers()

        },

        /**
         * Open the filter selector.
         */
        openFilterSelector: function () {
            this.$root.bus.$emit('filterSelector.open');
        },

        /**
         * Open the flag updator.
         */
        openFlagUpdator: function () {
            this.$root.bus.$emit('statusUpdator.open');
        },

        /**
         * Return whether or not this listing is filtered.
         */
        isFiltered: function () {
            if (_.filter(this.scope.filters, function (value, key) {
                return value && value.length;
            }).length) return true;

            if (_.filter(this.scope.ranges, function (value, key) {
                return value.from || value.to;
            }).length) return true;

            return false;
        },

        /**
         * Clear the applied filters.
         */
        clearFilters: function ()
        {
            this.scope.reset()

            this.$root.bus.emit('refreshFilterSelector')
        },

        /**
         * Return whether or not this row is currently selected.
         */
        isSelected: function (sellerId) {
            return _.contains(this.selectedRows, sellerId)
        },

        /**
         * Toggle all seller rows.
         */
        toggleAll: function ()
        {
            // All are currently selected, deselect all
            if (this.allSelected()) {
                this.selectedRows = []
                return
            }

            // Some are unselected, select all
            _.each(this.sellers, function (seller) {
                if (! _.contains(this.selectedRows, seller.id)) {
                    this.selectedRows.push(seller.id)
                }
            }.bind(this))
        },

        /**
         * Return whether or not all rows are selected.
         */
        allSelected: function ()
        {
            return this.sellers.length && this.sellers.length == this.selectedRows.length
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.page = 1;
            this.fetchSellers()
        },

        /**
         * Search event listener.
         */
        onSearch: function (query) {
            this.scope.queries.search = query
        },

        /**
         * Clear filters event listener.
         */
        onClearFilters: function (query) {
            this.clearFilters()
        },

        /**
         * Row toggled event listener.
         */
        onRowToggled: function (sellerId)
        {
            // This row is already selected, deselect it
            if (this.isSelected(sellerId)) {
                this.selectedRows = _.without(this.selectedRows, sellerId)
                return
            }

            // This row is not selected, lets select it
            this.selectedRows.push(sellerId)
        }

    }
})
