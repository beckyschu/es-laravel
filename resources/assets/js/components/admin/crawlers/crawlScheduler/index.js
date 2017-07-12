var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'platform-ident': require('../../../global/platformIdent')
    },
    data: function () {
        return {
            crawlers: [],
            selectedCrawlers: [],
            assets: [],
            defaultAssetId: null,
            selectedAssetId: null,
            selectedAsset: null,
            selectedKeywords: [],
            mode: 'light',
            modal: null,
            isLoaded: false,
            loadingItems: [
                {id: 'crawlers', description: 'Loading crawlers...', status: 'pending'},
                {id: 'assets', description: 'Loading assets...', status: 'pending'},
            ].reverse(),
            isScheduling: false,
        }
    },
    watch: {
        selectedAssetId: function (selectedAssetId) {
            if (selectedAssetId) {
                this.selectedAsset = _.findWhere(this.assets, {id: selectedAssetId})
            } else {
                this.selectedAsset = null
            }

            this.deselectAllKeywords()
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=crawl_scheduler]').remodal({
            hashTracking: false
        });

        // Listen to events
        this.$root.bus.$on('crawlScheduler.open', this.onOpen)
        this.$root.bus.$on('crawlScheduler.setAsset', this.onSetAsset)

        // Listen to updateLoadingStatus event
        this.$on('updateLoadingStatus', function (id, status)
        {
            _.findWhere(this.loadingItems, {id: id}).status = status

            if (! _.filter(this.loadingItems, function (item) {
                return 'pending' == item.status || 'loading' == item.status
            }).length) {
                window.setTimeout(function () {
                    this.isLoaded = true
                }.bind(this), 500)
            } else {
                this.isLoaded = false
            }
        })

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to event
        this.$root.bus.$off('crawlScheduler.open', this.onOpen)
        this.$root.bus.$off('crawlScheduler.setAsset', this.onSetAsset)

    },
    methods: {

        /**
         * Open modal.
         */
        openModal: function ()
        {
            // Set loaded flag
            this.isLoaded = false

            // Reset data
            this.selectedAssetId  = null
            this.selectedAsset    = null
            this.selectedCrawlers = []
            this.selectedKeywords = []

            // Fetch data
            this.fetchCrawlers()
            this.fetchAssets()

            // Open the modal
            this.modal.open()
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Return whether or not crawler is selected.
         */
        isCrawlerSelected: function (crawlerId)
        {
            return _.contains(this.selectedCrawlers, crawlerId)
        },

        /**
         * Select all crawler checkboxes.
         */
        selectAllCrawlers: function ()
        {
            _.each(this.crawlers, function (crawler) {
                if (! _.contains(this.selectedCrawlers, crawler.id)) {
                    this.selectedCrawlers.push(crawler.id)
                }
            }.bind(this))
        },

        /**
         * Deselect all crawler checkboxes.
         */
        deselectAllCrawlers: function ()
        {
            _.each(this.crawlers, function (crawler) {
                this.selectedCrawlers = _.without(this.selectedCrawlers, crawler.id)
            }.bind(this))
        },

        /**
         * Fetch crawlers for dropdown.
         */
        fetchCrawlers: function ()
        {
            // Set loading item
            this.$emit('updateLoadingStatus', 'crawlers', 'loading')

            // Clear crawlers
            this.crawlers = []

            // Make API request
            this.$http.get('crawlers')
                .then(function (response) {

                    // Loop through crawlers
                    _.each(response.data.data, function (crawler) {

                        this.crawlers.push(_.extend(
                            {id: crawler.id},
                            crawler.attributes
                        ));

                    }.bind(this))

                    // Set loading item
                    this.$emit('updateLoadingStatus', 'crawlers', 'complete')

                }.bind(this), function (response) {

                    // Close the modal
                    this.closeModal()

                    // Send generic error
                    this.$root.bus.$emit('error', 'We could not fetch the crawlers list required to schedule a crawl. Please try again later.')

                })
        },

        /**
         * Fetch assets for dropdown.
         */
        fetchAssets: function ()
        {
            // Set loading item
            this.$emit('updateLoadingStatus', 'assets', 'loading')

            // Clear assets
            this.assets = []

            // Make API request
            this.$http.get('assets')
                .then(function (response) {

                    // Loop through assets
                    _.each(response.data.data, function (asset) {

                        // Fetch keywords
                        var keywords = []
                        _.each(asset.relationships.keywords.data, function (keyword) {

                            // Fetch keyword entity
                            var entity = _.find(response.data.included, function (include) {
                                return 'keywords' == include.type && keyword.id == include.id;
                            })

                            // Push keyword on to stack
                            keywords.push(_.extend(
                                {id: entity.id},
                                entity.attributes
                            ))

                        })

                        // Push on to assets
                        this.assets.push(_.extend(
                            {
                                id: asset.id,
                                keywords: keywords
                            },
                            asset.attributes
                        ))

                    }.bind(this))

                    // Set default asset
                    if (this.defaultAssetId) {
                        this.selectedAssetId = this.defaultAssetId
                    }

                    // Set loading item
                    this.$emit('updateLoadingStatus', 'assets', 'complete')

                }.bind(this), function (response) {

                    // Close the modal
                    this.closeModal()

                    // Send generic error
                    this.$root.bus.$emit('error', 'We could not fetch the assets list required to schedule a crawl. Please try again later.')

                })
        },

        /**
         * Return whether or not keyword is selected.
         */
        isKeywordSelected: function (keyword)
        {
            return _.contains(this.selectedKeywords, keyword.id)
        },

        /**
         * Select all keyword checkboxes.
         */
        selectAllKeywords: function ()
        {
            _.each(this.selectedAsset.keywords, function (keyword) {
                if (! _.contains(this.selectedKeywords, keyword.id)) {
                    this.selectedKeywords.push(keyword.id)
                }
            }.bind(this))
        },

        /**
         * Deselect all keyword checkboxes.
         */
        deselectAllKeywords: function ()
        {
            this.selectedKeywords = []
        },

        /**
         * Create the asset.
         */
        create: function ()
        {
            //Already scheduling, bail out
            if (this.isScheduling) return;

            //No crawlers selected
            if (! this.selectedCrawlers.length) return;

            //No keywords selected
            if (! this.selectedKeywords.length) return;

            //Set flag
            this.isScheduling = true

            //Pack our payload
            var payload = {
                data: {
                    asset:    this.selectedAsset.id,
                    crawlers: this.selectedCrawlers,
                    keywords: this.selectedKeywords,
                    mode:     this.mode
                }
            }

            //Send request to API
            this.$http.post('crawls/schedule', payload)
                .then(function (response) {

                    //Close modal
                    this.closeModal()

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Crawl scheduled successfully.')

                    //Clear flag
                    this.isScheduling = false

                    //Refresh data
                    this.$root.bus.$emit('data.reload')

                }, function (response) {

                    //Loop errors and add to collection
                    this.$root.bus.$emit('error', 'We could not schedule a crawl at this time. Please try again later.')

                    //Clear flag
                    this.isScheduling = false

                });
        },

        /**
         * Get description for the given mode.
         */
        getModeDescription: function (mode)
        {
            if ('heavy' == mode) {
                return 'Heavy (Crawl all data points for each listing)';
            }

            if ('light' == mode) {
                return 'Light (Only fetch URL and vital data points)';
            }

            return mode;
        },

        /**
         * Open modal event listener.
         */
        onOpen: function () {
            this.openModal()
        },

        /**
         * Set the scheduler asset.
         */
        onSetAsset: function (asset) {
            this.defaultAssetId = asset ? asset.id : null
        }

    }
})
