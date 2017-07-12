var Vue         = require('vue')
var ProgressBar = require('progressbar.js')
var _           = require('underscore')
var url         = require('url')

module.exports = Vue.extend({
    name: 'DashboardSummary',
    template: require('./template.html'),
    components: {
        'filter-summary':  require('../../browser/filterSummary'),
        'filter-selector': require('../../browser/filterSelector'),
    },
    data: function () {
        return {
            discoveriesAreLoading: false,
            discoveryStatuses: require('../../helpers/discoveryStatuses'),
            discoveryStatusColors: require('../../helpers/discoveryStatusColors'),
            discoveriesTotal: null,
            discoveries: {},

            sellersAreLoading: false,
            sellerStatuses: require('../../helpers/sellerStatuses'),
            sellerStatusColors: require('../../helpers/sellerStatusColors'),
            sellersTotal: null,
            sellers: {}
        }
    },
    created: function () {

        // Listen to events
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch report data
        this.fetchDiscoveriesData()

    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('data.reload', this.onDataReload)
    },
    methods: {

        /**
         * Fetch discoveries data from API.
         */
        fetchDiscoveriesData: function () {

            // Set loading flag
            this.discoveriesAreLoading = true

            // Clear data
            this.discoveries = {}

            // Make API request
            this.$http.get('reports/discovery-statuses')
                .then(function (response) {

                    // Loop through discovery counts
                    _.each(response.data.data, function (count, status) {

                        // Count is zero, don't add to collection
                        if (! count) return;

                        // Change the way Reports are displayed (Status Post Processing)

                        if(status.localeCompare("rejected") == 0)
                        {
                          return;
                        }

                        if (status.localeCompare("inspect") == 0 || status.localeCompare("regressed") == 0)
                        {
                          if (typeof this.discoveries["discovered"] !== 'undefined'){
                            this.discoveries["discovered"] += count;
                          }
                          else {
                            this.discoveries["discovered"] = count;
                          }
                        }
                        else
                        {
                          // Add the status count
                          this.discoveries[status] = count;
                        }


                        // Increase total count
                        this.discoveriesTotal += count;

                    }.bind(this))

                    // Clear loading flag
                    this.discoveriesAreLoading = false

                    // Init graphs when DOM rendered
                    Vue.nextTick(function () {
                        this.drawDiscoveriesCharts()
                    }.bind(this))

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.discoveriesAreLoading = false

                    // Send generic error
                    this.$root.bus.$emit('error')

                })

        },

        /**
         * Draw discoveries charts for each status.
         */
        drawDiscoveriesCharts: function () {

            // Loop discoveries and draw
            _.each(this.discoveries, function (count, status)
            {
                // Count is zero, do nothing as the DOM element wont exist
                if (! count) return;

                // Initiate the graph
                var circle = new ProgressBar.Circle('#discoveries_'+status, {
                    color: this.discoveryStatusColors[status] ? this.discoveryStatusColors[status] : 'rgb(0,104,187)',
                    trailColor: 'rgb(216,227,234)',
                    strokeWidth: 6
                })

                // Calculate decimal percentage from total
                var percent = (count / this.discoveriesTotal * 100)/100

                // Animate the graph to the given percentage
                circle.animate(percent)

            }.bind(this))

            // //Loop sellers and init
            // _.each(this.sellers, function (count, status)
            // {
            //     //Count is zero, do nothing as the DOM element wont exist
            //     if (! count) return;
            //
            //     //Initiate the graph
            //     var circle = new ProgressBar.Circle('#sellers_'+status, {
            //         color: this.sellerStatusColors[status] ? this.sellerStatusColors[status] : 'rgb(0,104,187)',
            //         trailColor: 'rgb(216,227,234)',
            //         strokeWidth: 6
            //     })
            //
            //     //Calculate decimal percentage from total
            //     var percent = (count / this.sellersTotal * 100)/100
            //
            //     //Animate the graph to the given percentage
            //     circle.animate(percent)
            //
            // }.bind(this))

        },

        /**
         * Format the given number with commas for thousands.
         */
        formatNumber: function (x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        },

        /**
         * Return the label for the given discovery status.
         */
        discoveryStatusLabel: function (status) {
            return this.discoveryStatuses[status]
        },

        /**
         * Return the label for the given seller status.
         */
        sellerStatusLabel: function (status) {
            return this.sellerStatuses[status]
        },

        /**
         * Return whether or not the given object is empty.
         */
        isEmpty: function (object) {
            return _.isEmpty(object)
        },

        /**
         * Return a filtered path for discovery or sellers.
         */
        getFilteredPath: function (type, status)
        {
            //Set initial path
            var path = url.parse('/browser/'+type)

            //Init query with status

            if(status.localeCompare("discovered") == 0){
              path.hash = JSON.stringify({
                  status: [{id: "discovered", label: this.discoveryStatusLabel("discovered")},
                            {id: "inspect", label: this.discoveryStatusLabel("inspect")},
                            {id: "regressed", label: this.discoveryStatusLabel("regressed")}]
                  })
            }
            else {
              path.hash = JSON.stringify({
                  status: [{id: status, label: this.discoveryStatusLabel(status)}]
                  })
            }



            //Return formatted path
            return url.format(path);
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchDiscoveriesData()
        }

    }
})
