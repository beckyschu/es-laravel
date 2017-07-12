var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            chartData: null,
            isLoading: false,
            platform: null,
            platforms: require('../../../helpers/platforms'),
            request: null
        }
    },
    mounted: function ()
    {
        // Fetch chart data
        this.getData()

        // Redraw chart whenever selected platform changes
        this.$watch('platform', function () {
            this.getData()
        })
    },
    beforeDestroy () {
        if (this.request) this.request.abort();
    },
    methods: {

        /**
         * Fetch chart data.
         */
        getData: function ()
        {
            // Set loading flag
            this.isLoading = true

            // Init options
            let options = {
                before: (request) => this.request = request,
                params: {}
            }

            // Add platform to payload
            if (this.platform) {
                options.params.platform = this.platform
            }

            // Make API request
            this.$http.get('reports/top-sellers', options)
                .then(function (response) {

                    // Store chart data
                    this.chartData = response.data.data

                    // Clear loading flag
                    this.isLoading = false

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.isLoading = false

                })
        }

    }
})
