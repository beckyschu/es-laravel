var Vue         = require('vue')
var Chart       = require('chart.js')
var ProgressBar = require('progressbar.js')
var Highcharts  = require('highcharts')
var _           = require('underscore')

module.exports = Vue.extend({
    props: ['report', 'charts', 'pdf', 'images'],
    template: require('./template.html'),
    data () {
        return {
            radialReport: null,
            closedPlatformsChart: null,
            closedListingsChart:  null
        }
    },
    mounted ()
    {
        // No charts provided
        if (! this.images) {

            // Drawl initial radial report
            this.drawRadialReport()

            // Watch for changes and redraw chart
            this.$watch(
                () => JSON.stringify(this.report.activity.radial),
                () => this.drawRadialReport()
            )

            // Draw initial chart
            this.drawClosedPlatformsChart()

            // Watch for changes and redraw chart
            this.$watch(() => {
                return JSON.stringify(this.report.closedPlatforms)
            }, () => {
                this.drawClosedPlatformsChart()
            })

            // Draw initial chart
            this.drawClosedListingsChart()

            // Watch for changes and redraw chart
            this.$watch(() => {
                return JSON.stringify(this.report.closedListings)
            }, () => {
                this.drawClosedListingsChart()
            })

        }
    },
    methods: {

        /**
         * Draw the radial report
         */
        drawRadialReport: function ()
        {
            // Already built, just set value
            if (this.radialReport) {
                this.radialReport.set(parseInt(this.report.activity.radial) / 100)
                return
            }

            // Initiate the chart
            this.radialReport = new ProgressBar.Circle('#radial_report', {
                color: 'rgb(159,66,79)',
                trailColor: 'rgb(216,227,234)',
                strokeWidth: 8
            })

            // Set the graph to the given percentage
            this.radialReport.set(parseInt(this.report.activity.radial) / 100)
        },

        /**
         * Draw the "Closed platforms" chart
         */
        drawClosedPlatformsChart: function ()
        {
            if (this.closedPlatformsChart) {
                this.closedPlatformsChart.destroy()
            }

            var ctx = document.getElementById('closed_platforms_chart')

            var chartData = [
                parseInt(this.report.closedPlatforms.chineseMarketplaces),
                parseInt(this.report.closedPlatforms.domesticMarketplaces),
                parseInt(this.report.closedPlatforms.internationalMarketplaces),
                parseInt(this.report.closedPlatforms.socialMediaPlatforms),
                parseInt(this.report.closedPlatforms.thirdPartyWebsites),
            ]

            this.charts.platforms = {
                chart: {
                    type: 'column',
                    plotBackgroundColor: 'rgb(233,237,240)',
                    animation: false,
                    spacingRight: 0,
                    spacingLeft: 0,
                    spacingTop: 27
                },
                credits: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        animation: false,
                        enableMouseTracking: false,
                        pointPadding: 0.2,
                        groupPadding: 0,
                        marker: {
                            enabled: false
                        },
                    }
                },
                tooltip: {
                    enabled: false
                },
                legend: {
                    enabled: false
                },
                title: null,
                xAxis: {
                    labels: {
                        rotation: 0,
                        style: {
                            fontSize: '8px'
                        }
                    },
                    categories: [
                        'Chinese<br />Marketplaces',
                        'Domestic<br />Marketplaces',
                        'International<br />Marketplaces',
                        'Social Media<br />Platforms',
                        '3rd Party<br />Websites'
                    ]
                },
                yAxis: {
                    title: {
                        text: null
                    }
                },
                series: [{
                    name: 'Closures',
                    color: '#AD5862',
                    data: chartData
                }]
            }

            this.closedPlatformsChart = Highcharts.chart(ctx, this.charts.platforms, function (chart) {
                this.drawLabels(chart, chartData, '#AD5862')
            }.bind(this))
        },

        /**
         * Draw the "Closed listings" chart
         */
        drawClosedListingsChart: function ()
        {
            if (this.closedListingsChart) {
                this.closedListingsChart.destroy()
            }

            let ctx = document.getElementById('closed_listings_chart')

            var chartData = _.map(this.report.closedListings.chartData, (point) => {
                let int = parseInt(point)
                return int ? int : null
            })

            this.charts.listings = {
                chart: {
                    type: 'column',
                    plotBackgroundColor: 'rgb(233,237,240)',
                    animation: false,
                    spacingRight: 0,
                    spacingLeft: 0,
                    spacingTop: 27
                },
                credits: {
                    enabled: false
                },
                plotOptions: {
                    series: {
                        animation: false,
                        enableMouseTracking: false,
                        pointPadding: 0.2,
                        groupPadding: 0,
                        marker: {
                            enabled: false
                        },
                    }
                },
                tooltip: {
                    enabled: false
                },
                legend: {
                    enabled: false
                },
                title: null,
                xAxis: {
                    labels: {
                        useHTML: true
                    },
                    categories: _.map(this.report.closedListings.chartLabels, (label, key) => {
                        let html = '<div class="chart-label">'
                        html += label;
                        if (this.report.closedListings.secondaryLabels[key]) {
                            html += '<div class="chart-label__secondary">'
                            html += this.report.closedListings.secondaryLabels[key]
                            html += '</div>'
                        }
                        html += '</div>'
                        return html
                    })
                },
                yAxis: {
                    title: {
                        text: null
                    }
                },
                series: [
                    {
                        name:  'Closures',
                        color: '#1B80C4',
                        data: chartData
                    },
                    {
                        type:  'area',
                        name:  'Closures',
                        lineWidth: 0,
                        fillColor: 'rgba(0,104,187,0.2)',
                        data: chartData
                    },
                ]
            }

            this.closedListingsChart = Highcharts.chart(ctx, this.charts.listings, function (chart) {
                this.drawLabels(chart, chartData, '#1B80C4')
            }.bind(this))
        },

        drawLabels (chart, chartData, labelColor) {

            // Loop data points
            for (var i = 0; i < chartData.length; i++) {

                // Data not set, skip
                if (! chartData[i]) continue

                // Generate a new label with the given offset
                var generateLabel = function (point, chart, xOffset, text) {
                    return chart.renderer.label(
                        text,
                        point.plotX + chart.plotLeft - xOffset,
                        point.plotY + chart.plotTop - 40,
                        'callout',
                        point.plotX + chart.plotLeft,
                        point.plotY + chart.plotTop
                    ).css({
                        color: '#ffffff',
                        fontSize: '10px'
                    }).attr({
                        fill: labelColor,
                        padding: 8,
                        r: 10,
                        zIndex: 6,
                    }).add()
                }

                // Fetch position data from point
                var point = chart.series[0].data[i]

                // Generate a reference label (for x offset calculation)
                var referenceLabel = generateLabel(
                    point,
                    chart,
                    0,
                    chartData[i].toLocaleString()
                )

                // Generate the actual label
                generateLabel(
                    point,
                    chart,
                    referenceLabel.width / 2,
                    chartData[i].toLocaleString()
                )

                // Destroy the reference label
                referenceLabel.destroy()

            }

        }

    }
})
