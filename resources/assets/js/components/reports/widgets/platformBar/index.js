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
                before: (request) => this.request = request
            }

            // Make API request
            this.$http.get('reports/platform-status-counts', options)
                .then(function (response) {

                    // Store chart data
                    this.chartData = response.data.data

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
                        label: 'Discoveries',
                        backgroundColor: colors[this.status],
                        data: []
                    }
                ]
            }

            _.each(this.chartData, function (statuses, platform) {

                chartData.labels.push(platform)

                if (! this.status) {
                    chartData.datasets[0].data.push(_.reduce(statuses, function (memo, num) {
                        return memo + num
                    }))
                } else {
                    chartData.datasets[0].data.push(statuses[this.status])
                }

            }.bind(this))

            var ctx = document.getElementById('platform_chart')

            this.chart = new Chart(ctx, {
                type: 'bar',
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
                            ticks: {
                                min: 0,
                                maxTicksLimit: 3
                            },
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
