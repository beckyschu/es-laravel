var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'platform-ident':  require('../../../global/platformIdent'),
        'status-badge':    require('../../../global/statusBadge'),
        'token-generator': require('../tokenGenerator')
    },
    data: function () {
        return {
            id: this.$route.params.id,
            crawler: null,
            statuses: require('../../../helpers/crawlerStatuses'),
            statusColors: require('../../../helpers/crawlerStatusColors'),
            crawlStatuses: require('../../../helpers/crawlStatuses'),
            crawlStatusColors: require('../../../helpers/crawlStatusColors'),
            isClearing: false
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Crawler ' + this.paddedId(this.id))

        // Listen to data reload events
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch data
        this.fetchCrawler()
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$off('data.reload', this.onDataReload)
    },
    methods: {

        /**
         * Fetch crawler for current id.
         */
        fetchCrawler: function () {

            //Clear crawler
            this.crawler = null

            //Make API request
            this.$http.get('crawlers/'+this.id)
                .then(function (response) {

                    //Grab crawler
                    var crawler = response.data.data

                    //Fetch crawls
                    var crawls = []
                    _.each(crawler.relationships.crawls.data, function (crawl) {

                        //Fetch crawl entity
                        var entity = _.find(response.data.included, function (include) {
                            return 'crawls' == include.type && crawl.id == include.id;
                        })

                        //Fetch attached asset
                        var asset = _.find(response.data.included, function (include) {
                            return 'assets' == include.type && entity.relationships.asset.data.id == include.id;
                        })

                        //Fetch attached account
                        var account = _.find(response.data.included, function (include) {
                            return 'accounts' == include.type && asset.relationships.account.data.id == include.id;
                        })

                        crawls.push(_.extend(
                            {
                                id: entity.id,
                                asset: _.extend(
                                    {id: asset.id},
                                    asset.attributes
                                ),
                                account: _.extend(
                                    {id: account.id},
                                    account.attributes
                                )
                            },
                            entity.attributes
                        ))

                    })

                    //Push crawler data
                    this.crawler = _.extend(
                        {
                            id: crawler.id,
                            crawls: crawls
                        },
                        crawler.attributes
                    )

                }.bind(this), function (response) {

                    //Send generic error
                    this.$root.bus.$emit('error')

                })

        },

        /**
         * Return a padded ID for easier clicking.
         */
        paddedId: function (id) {
            return ('0000' + id).substr(-4, 4)
        },

        /**
         * Generate a "from now" timestamp.
         */
        fromNow: function (date) {
            return moment(date).fromNow();
        },

        /**
         * Get formatted dates.
         */
        formatDate: function (date) {
            return moment(date).format('MM/DD/YY h:mma');
        },

        /**
         * Clear failure for this crawler.
         */
        clearFailure: function ()
        {
            //Set flag
            this.isClearing = true

            //Send request to API
            this.$http.post('crawlers/'+this.id+'/reset')
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Failure cleared successfully. This crawler will now continue to crawl when requested.')

                    //Update status
                    this.crawler.status = 'healthy'

                    //Update status badge
                    this.$root.bus.$emit('status.update', 'crawlers', this.id, this.crawler.status)

                    //Clear flag
                    this.isClearing = false

                }, function (response) {

                    //Send generic error
                    this.$root.bus.$emit('error', 'Something went wrong when trying to reset this crawler. Please try again later.')

                    //Clear flag
                    this.isClearing = false

                });
        },

        /**
         * Open the token generator;
         */
        openTokenGenerator: function () {
            this.$root.bus.$emit('tokenGenerator.open');
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchCrawler()
        }

    }
})
