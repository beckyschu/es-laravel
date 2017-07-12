var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    name: 'AssetShow',
    template: require('./template.html'),
    props: ['auth'],
    components: {
        'status-badge':    require('../../../global/statusBadge'),
        'platform-ident':  require('../../../global/platformIdent'),
        'keyword-creator': require('../../keywords/keywordCreator'),
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            id:                this.$route.params.id,
            asset:             null,
            ebayCategories:    [],
            statuses:          require('../../../helpers/basicStatuses'),
            crawlStatuses:     require('../../../helpers/crawlStatuses'),
            crawlStatusColors: require('../../../helpers/crawlStatusColors'),
            isSavingDetails:   false,
            errors:            [],

            // Deletion system
            deletePassword: null,
            deletePermanent: false,
            showDeletePassword: false,
            isDeleting: false
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Assets - ' + this.id)

        // Listen to data reload events
        this.$root.bus.$on('data.reload', this.onDataReload);

        // Listen to socket updates
        this.$root.bus.$on('App\\Events\\Broadcast\\CrawlWasUpdated', this.onCrawlUpdated)

        // Fetch data
        this.fetchAsset()
    },
    beforeDestroy: function ()
    {
        // Unlisten to data reload event
        this.$root.bus.$off('data.reload', this.onDataReload);

        // Unlisten to socket update event
        this.$root.bus.$off('App\\Events\\Broadcast\\CrawlWasUpdated', this.onCrawlUpdated)

        // Reset crawl scheduler asset
        this.$root.bus.$emit('crawlScheduler.setAsset', null)
    },
    methods: {

        /**
         * Fetch asset for current id.
         */
        fetchAsset: function () {

            //Unsubscribe from sockets
            this.unsubscribeFromCrawls()

            //Clear asset
            this.asset = null

            //Make API request
            this.$http.get('assets/'+this.id)
                .then(function (response) {

                    //Grab asset
                    var asset = response.data.data

                    //Fetch attached account
                    var account = _.find(response.data.included, function (include) {
                        return 'accounts' == include.type && asset.relationships.account.data.id == include.id;
                    })

                    //Fetch crawls
                    var crawls = []
                    _.each(asset.relationships.crawls.data, function (crawl) {

                        //Fetch crawl entity
                        var entity = _.find(response.data.included, function (include) {
                            return 'crawls' == include.type && crawl.id == include.id;
                        })

                        //Fetch attached crawler
                        var crawler = _.find(response.data.included, function (include) {
                            return 'crawlers' == include.type && entity.relationships.crawler.data.id == include.id;
                        })

                        crawls.push(_.extend(
                            {
                                id: entity.id,
                                crawler: _.extend(
                                    {id: crawler.id},
                                    crawler.attributes
                                )
                            },
                            entity.attributes
                        ))

                    })

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

                    //Push asset data on to stack
                    this.asset = _.extend(
                        {
                            id: asset.id,
                            account: _.extend(
                                {id: account.id},
                                account.attributes
                            ),
                            crawls: crawls,
                            keywords: keywords
                        },
                        asset.attributes
                    )

                    // Fetch eBay categories
                    this.ebayCategories = []
                    _.each(response.data.meta.ebay_categories,
                        (c) => this.ebayCategories.push({
                            id: c.id,
                            label: c.name
                        })
                    )

                    // Update asset category
                    if (this.asset.ebay_category) {
                        this.asset.ebay_category = _.find(
                            this.ebayCategories,
                            (c) => c.id == this.asset.ebay_category
                        )
                    }

                    //Update subheader title
                    this.$root.bus.$emit('subheader.updateTitle', 'Assets - '+this.asset.name)

                    //Subscribe to sockets
                    this.subscribeToCrawls()

                    // Set asset in crawl scheduler
                    this.$root.bus.$emit('crawlScheduler.setAsset', this.asset)

                }.bind(this), function (response) {

                    //Send generic error
                    this.$root.bus.$emit('error')

                })

        },

        /**
         * Subscribe to crawls.
         */
        subscribeToCrawls: function ()
        {
            if (! this.asset) return;

            _.each(this.asset.crawls, function (crawl) {
                this.$root.bus.$emit('subscribe', 'crawl.'+crawl.id)
            }.bind(this))
        },

        /**
         * Unsubscribe from crawls.
         */
        unsubscribeFromCrawls: function ()
        {
            if (! this.asset) return;

            _.each(this.asset.crawls, function (crawl) {
                this.$root.bus.$emit('unsubscribe', 'crawl.'+crawl.id)
            }.bind(this))
        },

        /**
         * Update the details for this account.
         */
        updateDetails: function ()
        {
            //Already saving
            if (this.isSavingDetails) return

            //Set saving flag
            this.isSavingDetails = true

            //Clear errors
            this.errors = []

            //Pack our payload
            var payload = {
                data: {
                    type: 'assets',
                    id: this.id,
                    attributes: {
                        name:             this.asset.name,
                        description:      this.asset.description,
                        counter_keywords: this.asset.counter_keywords,
                        ebay_category:    this.asset.ebay_category ? this.asset.ebay_category.id : null,
                        status:           this.asset.status
                    }
                }
            };

            //Send request to API
            this.$http.patch('assets/'+this.id, payload)
                .then(function (response) {

                    //Capture transaction if provided
                    var options = {}
                    var headers = response.headers;
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Asset details updated successfully.', options)

                    //Clear saving flag
                    this.isSavingDetails = false

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear saving flag
                    this.isSavingDetails = false

                });
        },

        /**
         * Delete the asset.
         */
        deleteAsset: function ()
        {
            // Already deleting
            if (this.isDeleting) return

            // Show the delete password
            if (! this.showDeletePassword) {
                this.showDeletePassword = true
                Vue.nextTick(function () {
                    this.$refs.deletePasswordField.focus()
                }.bind(this))
                return
            }

            // No password provided
            if (! this.deletePassword) return

            // Set flag
            this.isDeleting = true

            // Send API request
            this.$http.delete('assets/'+this.id, {
                body: {
                    password:  this.deletePassword,
                    permanent: this.deletePermanent
                }
            })
                .then(function (response) {

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Asset deleted')

                    // Redirect to listing
                    this.$router.push('/admin/assets')
                    return

                }, function (response) {

                    // Password was incorrect
                    if (response.data.errors && response.data.errors[0].detail == 'Your password is incorrect.') {
                        this.$root.bus.$emit('error', 'Your password is incorrect, please try again')
                    }

                    // Dispatch error message
                    else {
                        this.$root.bus.$emit('error', 'Asset could not be deleted')
                    }

                    // Clear the flag
                    this.isDeleting = false

                })
        },

        /**
         * Cancel delete account process.
         */
        cancelDeleteAsset: function ()
        {
            this.showDeletePassword = false
            this.deletePermanent    = false
            this.deletePassword     = null
        },

        /**
         * Generate a "from now" timestamp.
         */
        fromNow: function (date) {
            return moment(date).fromNow();
        },

        /**
         * Return a moment object for the given datetime.
         */
        moment: function (date) {
            return moment(date)
        },

        /**
         * Get formatted dates.
         */
        formatDate: function (date) {
            return moment(date).format('MM/DD/YY h:mma');
        },

        /**
         * Open the keyword creator.
         */
        openKeywordCreator: function () {
            this.$root.bus.$emit('keywordCreator.open');
        },

        /**
         * Delete the provided keyword.
         */
        deleteKeyword: function (keyword)
        {
            // Ask for confirmation
            if (! confirm('Are you sure you want to delete this keyword?')) return

            // Send request to API
            this.$http.delete('keywords/'+keyword.id)
                .then(function (response) {

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Keyword deleted.')

                    // Refresh data
                    this.$root.bus.$emit('data.reload')

                }, function (response) {

                    //

                });
        },

        /**
         * View discoveries for the given asset.
         */
        viewDiscoveries: function ()
        {
            //Switch account if required
            this.auth.switchAccount(this.asset.account.id)

            //Redirect to the listing
            this.$router.push('/browser/discoveries?asset='+this.id)
        },

        /**
         * Return info to be displayed inside the crawl status badge.
         */
        getCrawlBadgeInfo: function (crawl)
        {
            if ('crawling' == crawl.status && crawl.crawl_percent_complete) {
                return '('+crawl.crawl_percent_complete+'%)'
            }

            if ('scheduled' == crawl.status) {
                return crawl.queue_position
            }

            return null
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchAsset()
        },

        /**
         * Crawl updated event listener.
         */
        onCrawlUpdated: function (data) {

            // The initial fetch hasn't completed
            if (! this.asset) return

            // Find the crawl in collection
            var crawl = _.find(this.asset.crawls, function (crawl) {
                return crawl.id == data.crawl.id
            })

            // Could not find crawl in listing
            if (! crawl) return

            // Assign new attributes to crawl
            _.assign(crawl, data.crawl)

            // Force status badge to update
            this.$root.bus.$emit('status.update', 'crawls', crawl.id, crawl.status)

        },

        /**
         * Add counter keyword tag event.
         */
        addCounterKeywordTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.asset.counter_keywords, newTag)) return

            // Push tag into asset data
            this.asset.counter_keywords.push(newTag)
        },

        /**
         * Format the provided keyword schedule.
         */
        formatSchedule: function (schedule)
        {
            if (! schedule) return 'Never';

            if ('daily' == schedule) return 'Every day';
            if ('2days' == schedule) return 'Every other day';
            if ('3days' == schedule) return 'Every 3 days';
            if ('4days' == schedule) return 'Every 4 days';
            if ('5days' == schedule) return 'Every 5 days';

            return schedule;
        }

    }
})
