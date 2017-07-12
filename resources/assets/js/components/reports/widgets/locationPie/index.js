var Vue   = require('vue')
var Chart = require('chart.js')
var _     = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            chart: null,
            chartData: [],
            isLoading: false,
            status: null,
            statuses: require('../../../helpers/discoveryStatuses'),
            colors: [
                'rgb(175,88,99)',
                'rgb(213,181,40)',
                'rgb(75,185,95)',
                'rgb(67,186,191)',
                'rgb(35,129,196)'
            ],
            hoverColors: [
                'rgb(201,114,125)',
                'rgb(239,207,66)',
                'rgb(101,211,121)',
                'rgb(93,212,217)',
                'rgb(61,155,222)'
            ],
            request: null
        }
    },
    mounted: function ()
    {
        // Fetch chart data
        this.getData()

        // Redraw graph whenever selected status changes
        this.$watch('status', function () {
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

            // Add status to payload
            if (this.status) {
                options.params.status = this.status
            }

            // Make API request
            this.$http.get('reports/location-breakdown', options)
                .then(function (response) {

                    // Store chart data
                    this.chartData = response.data.data

                    // Clear loading flag
                    this.isLoading = false

                    // Draw the chart
                    if (this.chartData.length) {
                        Vue.nextTick(function () {
                            this.draw()
                        }.bind(this))
                    }

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
                        backgroundColor: this.colors,
                        hoverBackgroundColor: this.hoverColors,
                        data: []
                    }
                ]
            }

            _.each(this.chartData, function (group) {

                chartData.labels.push(group.label)
                chartData.datasets[0].data.push(group.count)

            }.bind(this))

            var ctx = document.getElementById('location_chart')

            this.chart = new Chart(ctx, {
                type: 'doughnut',
                data: chartData,
                options: {
                    animation: {
                        easing: 'easeInOutExpo'
                    },
                    legend: {
                        position: 'right'
                        //display: false
                    }
                }
            })
        }

    }
})
