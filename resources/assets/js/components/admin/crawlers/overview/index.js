var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'platform-ident': require('../../../global/platformIdent'),
        'status-badge': require('../../../global/statusBadge')
    },
    data: function () {
        return {
            isLoading: false,
            interval: null,
            crawlers: [],
            statuses: require('../../../helpers/crawlerStatuses'),
            statusColors: require('../../../helpers/crawlerStatusColors')
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Crawlers')

        // Fetch crawlers
        this.fetchCrawlers()

        // Refresh crawlers every 5 seconds
        this.interval = setInterval(function () {
            this.fetchCrawlers(false);
        }.bind(this), 5000);
    },
    beforeDestroy: function ()
    {
        // Clear refresh interval
        clearInterval(this.interval);
    },
    methods: {

        /**
         * Fetch crawlers from API.
         */
        fetchCrawlers: function (clearExisting)
        {
            //By default, clear existing crawlers
            if ('undefined' == typeof clearExisting) {
                clearExisting = true
            }

            //Clear crawlers and set loading
            if (clearExisting) {
                this.isLoading = true
                this.crawlers = []
            }

            //Make API request
            this.$http.get('crawlers')
                .then(function (response) {

                    //Loop through crawlers
                    _.each(response.data.data, function (crawler) {

                        //Push crawler data on to stack
                        if (clearExisting) {
                            this.crawlers.push(_.extend(
                                {id: crawler.id},
                                crawler.attributes
                            ));
                        }

                        //Update existing crawler data
                        else {
                            var index = _.findIndex(this.crawlers, function (c) {
                                return c.id == crawler.id;
                            });

                            if (index > -1) {
                                this.crawlers.$set(index, _.extend(
                                    {id: crawler.id},
                                    crawler.attributes
                                ))
                            }
                        }

                    }.bind(this))

                    //Clear loading flag
                    this.isLoading = false

                }.bind(this), function (response) {

                    //Clear loading flag
                    this.isLoading = false

                    //Send generic error
                    this.$root.bus.$emit('error');

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

    }
})
