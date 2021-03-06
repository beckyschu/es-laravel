var Vue    = require('vue')
var Chart  = require('chart.js')
var _      = require('underscore')
var moment = require('moment')
var colors = require('../../../helpers/discoveryStatusColors')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            chart: null,
            chartData: null,
            isLoading: false,
            status: 'discovered',
            statuses: require('../../../helpers/discoveryStatuses'),
            platform: null,
            platforms: require('../../../helpers/platforms'),
            request: null
        }
    },
    mounted: function ()
    {
        // Fetch chart data
        this.getData()

        // Redraw graph whenever selected status changes
        this.$watch('status', function () {
            this.draw()
        })

        // Redraw graph whenever selected platform changes
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
            var options = {
                before: (request) => this.request = request,
                params: {}
            }

            // Add platform to payload
            if (this.platform) {
                options.params.platform = this.platform
            }

            // Make API request
            this.$http.get('reports/daily-discovery-statuses', options)
                .then(function (response) {

                    // Create moments for dates and store
                    this.chartData = _.map(response.data.data, function (day) {
                        day.date = moment(day.date)
                        return day
                    })

                    // Clear loading flag
                    this.isLoading = false

                    // Draw the chart
                    Vue.nextTick(function () {
                        this.draw()
                    }.bind(this))

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.isLoading = false

                })
        },

        /**
         * Draw the graph.
         */
        draw: function ()
        {
            if (this.chart) {
                this.chart.destroy()
            }

            var chartData = {
                labels: [],
                datasets: [
                    {
                        label: 'Closed Discoveries',
                        backgroundColor: 'rgba(35,129,196,0.25)',
                        data: [],
                        lineTension: 0,
                        pointBorderColor: 'white',
                        pointRadius: 5
                    },
                    {
                        label: 'Discoveries',
                        backgroundColor: colors[this.status],
                        data: [],
                        lineTension: 0,
                        pointBorderColor: 'white',
                        pointRadius: 5
                    }
                ]
            }

            _.each(this.chartData, function (day) {

                chartData.labels.push(day.date.format('D'))

                chartData.datasets[0].data.push(day.results['closed'])
                chartData.datasets[1].data.push(day.results[this.status])

            }.bind(this))

            var ctx = document.getElementById('discoveries_chart')

            this.chart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    animation: {
                        easing: 'easeInOutExpo'
                    },
                    legend: {
                        display: false
                    },
                    scales: {
                        yAxes: [{
                            gridLines: {
                                display: false
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            })
        }

    }
})
