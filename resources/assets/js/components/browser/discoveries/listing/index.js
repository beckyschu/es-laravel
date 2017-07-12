var Vue = require('vue')
var _   = require('underscore')

import numeral from 'numeral'

module.exports = Vue.extend({
    name: 'DiscoveriesListing',
    template: require('./template.html'),
    props: ['auth', 'scope'],
    components: {
        'filter-summary':  require('../../filterSummary'),
        'filter-selector': require('../../filterSelector'),
        'status-updator':  require('../../statusUpdator'),
        'sort-header':     require('../../sortHeader'),
        'discovery-row':   require('./discoveryRow'),
        'importer':        require('../importer'),
        'submit-form':     require('../submitForm'),
        'enforcer':        require('../enforcer')
    },
    data: function () {
        return {
            initiated: false,
            loading: true,
            request: null, //Keep track of xhr so we can abort requests
            page: 1,
            perPage: 20,
            hasMorePages: false,
            total: null,
            sort: {
                key: 'last_seen_at',
                dir: 'desc'
            },
            discoveries: [],
            selectedRows: [],
            entireSetSelected: false,
            enforceCount: null,
            isGeneratingCsv: false,
            renderFilterSelector: true,
            showImages: false
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
        this.$root.bus.$on('status.updated', this.onStatusUpdated)
        this.$root.bus.$on('statusUpdator.complete', this.onStatusUpdatorComplete)
        this.$root.bus.$on('search', this.onSearch)
        this.$root.bus.$on('rowToggled', this.onRowToggled)

        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'The Breakdown');

        // Update listing when sort changes
        this.$watch(function () {
            return JSON.stringify(this.sort)
        }, function (newVal, oldVal) {
            this.$root.bus.$emit('data.reload');
        });

        // Emit selectCountUpdated event when necessary
        this.$watch(function () {

            // Entire set is selected
            if (this.entireSetSelected) {
                return this.total
            }

            // Return only the select count
            return this.selectedRows.length

        }, function (newVal, oldVal) {
            this.$root.bus.$emit('quickStatusUpdator.selectCountUpdated', newVal);
        });

        // Fetch initial discovery data
        this.fetchDiscoveries()
    },
    beforeDestroy: function () {
        this.$root.bus.$off('data.reload', this.onDataReload)
        this.$root.bus.$off('status.updated', this.onStatusUpdated)
        this.$root.bus.$off('statusUpdator.complete', this.onStatusUpdatorComplete)
        this.$root.bus.$off('search', this.onSearch)
        this.$root.bus.$off('rowToggled', this.onRowToggled)
    },
    methods: {

        /**
         * Fetch discoveries according to current scope.
         */
        fetchDiscoveries: function () {

            //Set loading flag
            this.loading = true

            //Clear discoveries
            this.discoveries = []

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
            this.$http.get('discoveries', options)
                .then(function (response) {

                    //Set data from meta
                    this.total        = response.data.meta.total
                    this.page         = response.data.meta.page
                    this.enforceCount = response.data.meta.enforceCount

                    //Set has more pages
                    this.hasMorePages = (null !== response.data.links.next)

                    //Loop through discoveries
                    _.each(response.data.data, function (discovery) {

                        //Fetch asset
                        var asset_id = discovery.relationships.asset.data.id;
                        var asset = _.find(response.data.included, function (include) {
                            return 'assets' == include.type && asset_id == include.id;
                        });

                        //Fetch seller
                        var seller = null
                        if (discovery.relationships.seller.data) {
                            var sellerId = discovery.relationships.seller.data.id;
                            seller = _.find(response.data.included, function (include) {
                                return 'sellers' == include.type && sellerId == include.id;
                            });
                        }

                        //Fetch account
                        var account = null
                        if (discovery.relationships.account && discovery.relationships.account.data) {
                            var accountId = discovery.relationships.account.data.id;
                            account = _.find(response.data.included, function (include) {
                                return 'accounts' == include.type && accountId == include.id;
                            });
                        }

                        //Push discovery data on to stack
                        this.discoveries.push(_.extend(
                            {
                                id: discovery.id,
                                asset: _.extend({id: asset.id}, asset.attributes),
                                seller: seller ? _.extend({id: seller.id}, seller.attributes) : null,
                                account: account ? _.extend({id: account.id}, account.attributes) : null,
                            },
                            discovery.attributes
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

            //Fetch new discoveries
            this.fetchDiscoveries()

        },

        /**
         * Go back to previous page.
         */
        prevPage: function () {

            //Decrease page counter
            this.page--

            //Fetch new discoveries
            this.fetchDiscoveries()

        },

        /**
         * Return whether or not this listing has any selected rows.
         *
         * @return bool
         */
        hasSelectedRows: function () {

            // Entire set is selected
            if (this.entireSetSelected) return true

            // Check if there are any selected rows
            return 0 < this.selectedRows.length

        },

        /**
         * Return whether or not this row is currently selected.
         */
        isSelected: function (discoveryId) {

            // Entire set is selected
            if (this.entireSetSelected) return true

            // Check if this row is selected
            return _.contains(this.selectedRows, discoveryId)

        },

        /**
         * Open the filter selector.
         */
        openFilterSelector: function () {
            this.$root.bus.$emit('filterSelector.open');
        },

        /**
         * Open the status updator.
         */
        openStatusUpdator: function () {
            this.$root.bus.$emit('statusUpdator.open');
        },

        /**
         * Open the submit form modal.
         */
        openSubmitForm: function () {
            this.$root.bus.$emit('submitForm.open');
        },

        /**
         * Open the importer modal.
         */
        openImporter: function () {
            this.$root.bus.$emit('discoveryImporter.open');
        },

        /**
         * Open the enforcer modal.
         */
        openEnforcer: function () {
            this.$root.bus.$emit('discoveryEnforcer.open');
        },

        /**
         * Generate and redirect to a CSV for the current dataset.
         */
        generateCsv: function ()
        {
            //Bail out if already generating
            if (this.isGeneratingCsv) return;

            //Set loading flag
            this.isGeneratingCsv = true

            //Make API request
            this.$http.get('discoveries/export', {
                params: this.scope.buildPayload()
            })
                .then(function (response) {

                    //Grab URL for generated CSV
                    let url = response.data.data

                    //Redirect to the CSV
                    window.location.href = url

                    //Clear loading flag
                    this.isGeneratingCsv = false

                }.bind(this), function (response) {

                    //Clear loading flag
                    this.isGeneratingCsv = false

                    //Dispatch generic error
                    this.$root.bus.$emit('error', 'CSV export could not be generated at this time')

                })
        },

        /**
         * Toggle all discovery rows.
         */
        toggleAll: function ()
        {
            // Disable entire set selection
            this.entireSetSelected = false

            // All are currently selected, deselect all
            if (this.allSelected()) {
                this.selectedRows = []
                return
            }

            // Some are unselected, select all
            _.each(this.discoveries, function (discovery) {
                if (! _.contains(this.selectedRows, discovery.id)) {
                    this.selectedRows.push(discovery.id)
                }
            }.bind(this))
        },

        /**
         * Return whether or not all rows are selected.
         */
        allSelected: function ()
        {
            return this.discoveries.length
                && (
                    this.entireSetSelected
                    || this.discoveries.length == this.selectedRows.length
                )
        },

        /**
         * Toggle selection of the entire set.
         */
        toggleSelectEntireSet: function ()
        {
            this.entireSetSelected = ! this.entireSetSelected
            this.selectedRows = []
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

            this.$emit('refreshFilterSelector')
        },

        /**
         * Return whether or not the collection contains enforcable listings.
         */
        hasEnforceListings: function () {
            return this.enforceCount > 0;
        },

        /**
         * Toggle the images column.
         */
        toggleImages: function () {
            this.showImages = ! this.showImages
        },

        /**
         * Quick set a status from a shortcut button.
         */
        quickSetStatus: function (status) {
            this.$root.bus.$emit('statusUpdator.open', status)
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.page = 1
            this.selectedRows = []
            this.fetchDiscoveries()
        },

        /**
         * Status updated event listener.
         */
        onStatusUpdated: function (event)
        {
            // Status has changed TO enforce
            if ('enforce' == event.newStatus && 'enforce' !== event.oldStatus) {
                this.enforceCount++;
            }

            // Status has changed FROM enforce
            if ('enforce' !== event.newStatus && 'enforce' == event.oldStatus) {
                this.enforceCount--;
            }
        },

        /**
         * Status updator complete event listener.
         */
        onStatusUpdatorComplete: function (event)
        {
            if (event.enforceCount === 0 || event.enforceCount) {
                this.enforceCount = event.enforceCount;
            }
        },

        /**
         * Search event listener.
         */
        onSearch: function (query) {
            this.scope.filters.query = query
        },

        /**
         * Row toggled event listener.
         */
        onRowToggled: function (discoveryId)
        {
            // Disable entire set selection
            this.entireSetSelected = false

            // This row is already selected, deselect it
            if (this.isSelected(discoveryId)) {
                this.selectedRows = _.without(this.selectedRows, discoveryId)
                return
            }

            // This row is not selected, lets select it
            this.selectedRows.push(discoveryId)
        }

    }
})
