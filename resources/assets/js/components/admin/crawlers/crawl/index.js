var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'platform-ident': require('../../../global/platformIdent'),
        'status-badge':   require('../../../global/statusBadge'),
    },
    data: function () {
        return {
            crawlerId: this.$route.params.crawler,
            id: this.$route.params.id,
            crawl: null,
            crawlLog: {
                loading: false,
                log: null,
                updatedAt: null
            },
            statuses: require('../../../helpers/crawlStatuses'),
            statusColors: require('../../../helpers/crawlStatusColors'),
            submissionStatuses: require('../../../helpers/submissionStatuses'),
            submissionStatusColors: require('../../../helpers/submissionStatusColors'),
            isCancelling: false,
            nowInterval: null,
            now: moment()
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Crawl '+this.paddedId(this.id))

        // Listen to events
        this.$root.bus.$on('data.reload', this.onDataReload)
        this.$root.bus.$on('App\\Events\\Broadcast\\CrawlWasUpdated', this.onCrawlUpdated)
        this.$root.bus.$on('App\\Events\\Broadcast\\CrawlLogUpdated', this.onLogUpdate)

        // Update now timestamp every second
        this.nowInterval = setInterval(function () {
            this.now = moment()
        }.bind(this), 1000)

        // Subscribe to socket
        this.$root.bus.$emit('subscribe', 'crawl.'+this.id)

        // Fetch data
        this.fetchCrawl()
    },
    beforeDestroy: function ()
    {
        // Clear time ticker interval
        clearInterval(this.nowInterval)

        // Unsubscribe from socket
        this.$root.bus.$emit('unsubscribe', 'crawl.'+this.id)

        // Unlisten to events
        this.$root.bus.$off('data.reload', this.onDataReload)
        this.$root.bus.$off('App\\Events\\Broadcast\\CrawlWasUpdated', this.onCrawlUpdated)
        this.$root.bus.$off('App\\Events\\Broadcast\\CrawlLogUpdated', this.onLogUpdate)
    },
    computed: {

        /**
         * Return a moment object for the start time for this crawl.
         */
        crawlStartedAt: function ()
        {
            return this.crawl.crawl_started_at ? moment(this.crawl.crawl_started_at) : null
        },

        /**
         * Return a moment object for the end time for this crawl.
         */
        crawlEndedAt: function ()
        {
            return this.crawl.crawl_ended_at ? moment(this.crawl.crawl_ended_at) : null
        },

        /**
         * Return a moment duration object for the current crawl.
         */
        crawlDuration: function ()
        {
            // Crawl has not started yet
            if (! this.crawlStartedAt) return null

            // Grab timestamps
            var start = this.crawlStartedAt
            var end   = this.crawlEndedAt ? this.crawlEndedAt : this.now

            // Return a duration object
            return moment.duration(end.diff(start));
        },

        /**
         * Return a moment duration object for the current crawl in human form.
         */
        crawlDurationHuman: function ()
        {
            // Grab duration object
            var duration = this.crawlDuration

            // Crawl has not started yet
            if (! duration) return null

            // Instantiate our formatted string
            var ret = ''

            // More than 60 minutes, include hours
            if (60 <= duration.asMinutes()) {
                ret += duration.hours()+'h '
            }

            // More than 60 seconds, include minutes
            if (60 <= duration.asSeconds()) {
                ret += duration.minutes()+'m '
            }

            // Always include seconds
            ret += duration.seconds()+'s'

            // Return formatted string
            return ret
        }

    },
    methods: {

        /**
         * Fetch crawl for current id.
         */
        fetchCrawl: function () {

            //Clear crawl
            this.crawl = null

            //Make API request
            this.$http.get('crawls/'+this.id)
                .then(function (response) {

                    //Push crawl data
                    this.crawl = response.data

                    //Fetch the crawl log
                    this.fetchCrawlLog()

                }.bind(this), function (response) {

                    //Send generic error
                    this.$root.bus.$emit('error')

                })

        },

        /**
         * Fetch the crawl log for this crawl.
         */
        fetchCrawlLog: function ()
        {
            // Set loading flag (only if log is currently empty)
            if (! this.crawlLog.log) this.crawlLog.loading = true

            // Make API request
            this.$http.get('crawls/'+this.id+'/log')
                .then(function (response) {

                    //Log data returned
                    if (response.data.data) {

                        //Grab log and timestamp
                        var log       = response.data.data.log
                        var updatedAt = response.data.data.updated_at

                        //Save log to data
                        this.crawlLog.log = log
                        this.crawlLog.updatedAt = updatedAt

                    }

                    // Clear loading flag
                    this.crawlLog.loading = false

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
         * Show show cancel button.
         */
        shouldShowCancel: function () {
            return _.contains(['crawling', 'scheduled'], this.crawl.status)
        },

        /**
         * Cancel the current crawl.
         */
        cancel: function ()
        {
            // Already cancelling, bail out
            if (this.isCancelling) return

            // Set flag
            this.isCancelling = true

            // Send request to API
            this.$http.post('crawls/'+this.id+'/cancel')
                .then(function (response) {

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Crawl cancelled successfully.')

                    // Clear flag
                    this.isCancelling = false

                    // Refresh crawl data
                    this.fetchCrawl()

                }.bind(this), function (response) {

                    // Dispatch generic error
                    this.$root.bus.$emit('error', 'An error occured when trying to cancel this crawl. Please try again later.')

                    // Clear flag
                    this.isCancelling = false

                }.bind(this))
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchCrawl()
        },

        /**
         * Crawl updated event listener.
         */
        onCrawlUpdated: function (data) {

            // This is not the same crawl
            if (data.crawl.id != this.id) return

            // The initial fetch hasn't completed
            if (! this.crawl) return

            // If status has changed to complete, fire a message
            if ('complete' !== this.crawl.generatedStatus && 'complete' == data.crawl.generatedStatus) {
                this.$root.bus.$emit('success', 'Crawl completed successfully')
            }

            // Assign new attributes to crawl
            _.assign(this.crawl, data.crawl)

            // Force status badge to update
            this.$root.bus.$emit('status.update', 'crawl', this.crawl.id, this.crawl.generatedStatus)

        },

        /**
         * Log update event listener.
         */
        onLogUpdate: function (data) {

            // This is not the same crawl
            if (data.crawl.id != this.id) return

            // Refetch the crawl log
            this.fetchCrawlLog()

        }

    }
})
