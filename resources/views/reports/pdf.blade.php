<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="x-ua-compatible" content="ie=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="{{ asset('css/main.css') }}" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.9/highcharts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.0.1/progressbar.min.js"></script>
        <script type="text/javascript">
            var charts = {
                report: JSON.parse("{!! addslashes(json_encode($reportData)) !!}"),
                drawRadialReport: function ()
                {
                    // Initiate the chart
                    var circle = new ProgressBar.Circle('#radial_report', {
                        color: 'rgb(159,66,79)',
                        trailColor: 'rgb(216,227,234)',
                        strokeWidth: 8
                    })

                    // Set the graph to the given percentage
                    circle.set(parseInt(this.report.activity.radial) / 100)
                },
                drawClosedPlatformsChart: function ()
                {
                    var ctx = document.getElementById('closed_platforms_chart')

                    var chartData = [
                        parseInt(this.report.closedPlatforms.chineseMarketplaces),
                        parseInt(this.report.closedPlatforms.domesticMarketplaces),
                        parseInt(this.report.closedPlatforms.internationalMarketplaces),
                        parseInt(this.report.closedPlatforms.socialMediaPlatforms),
                        parseInt(this.report.closedPlatforms.thirdPartyWebsites),
                    ]

                    var chartOptions = {
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
                            color: 'rgb(159,66,79)',
                            data: chartData
                        }]
                    }

                    var that = this

                    Highcharts.chart(ctx, chartOptions, function (chart) {
                        that.drawLabels(chart, chartData, 'rgb(159,66,79)')
                    })
                },
                drawClosedListingsChart: function ()
                {
                    var ctx = document.getElementById('closed_listings_chart')

                    var chartData = _.map(this.report.closedListings.chartData, function (point) {
                        var int = parseInt(point)
                        return int ? int : null
                    })

                    var chartOptions = {
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
                            categories: _.map(this.report.closedListings.chartLabels, function (label, key) {
                                var html = '<div class="chart-label">'
                                html += label;
                                if (charts.report.closedListings.secondaryLabels[key]) {
                                    html += '<div class="chart-label__secondary">'
                                    html += charts.report.closedListings.secondaryLabels[key]
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
                                color: 'rgb(0,104,187)',
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

                    var that = this

                    Highcharts.chart(ctx, chartOptions, function (chart) {
                        that.drawLabels(chart, chartData, 'rgb(0,104,187)')
                    })
                },
                drawLabels: function (chart, chartData, labelColor) {

                    // Loop data points
                    for (var i = 0; i < chartData.length; i++) {

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

            document.addEventListener('DOMContentLoaded', function() {
                charts.drawRadialReport()
                charts.drawClosedPlatformsChart()
                charts.drawClosedListingsChart()
            })
        </script>
    </head>
    <body>
        <div class="infringement-report infringement-report--pdf">
            <header class="infringement-report__header">
                <h1>IP Shark - Activity Report</h1>
            </header>
            <div class="infringement-report__content">
                <div class="grid">

                    <div class="grid__col l-one-half">
                        <h2 class="infringement-report__subheader">Report Details</h2>
                        <div class="infringement-report__data-block infringement-report__data-block--report-details">
                            <ul class="infringement-report__data-list">
                                <li>
                                    <span>{{ $reportData->details->name }}</span>
                                    Client
                                </li>
                                <li>
                                    <span>{{ $reportData->details->date }}</span>
                                    Date
                                </li>
                                <li>
                                    <span>{{ $reportData->details->range }}</span>
                                    Range
                                </li>
                                <li>
                                    <span>{{ $reportData->details->scanType }}</span>
                                    Scan Type
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid__col l-one-half">
                        <h2 class="infringement-report__subheader">Infringement Activity - Removed</h2>
                        <div class="infringement-report__data-block infringement-report__data-block--radial">
                            <div class="radial-report" id="radial_report">
                                <div>
                                    <div class="radial-report__value">{{ $reportData->activity->radial }}</div>
                                </div>
                            </div>
                            <ul class="infringement-report__data-list">
                                <li>
                                    <span>{{ $reportData->activity->marketplaces }}</span>
                                    Marketplaces
                                </li>
                                <li>
                                    <span>{{ $reportData->activity->socialMedia }}</span>
                                    Social Media
                                </li>
                                <li>
                                    <span>{{ $reportData->activity->websites }}</span>
                                    Websites
                                </li>
                                <li>
                                    <span>{{ $reportData->activity->searchEngines }}</span>
                                    Search Engines*
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid__col l-one-half">
                        <h2 class="infringement-report__subheader infringement-report__subheader--border-bottom">Closed Listings</h2>
                        <div class="infringement-report__data-block infringement-report__data-block--chart">
                            <div id="closed_platforms_chart" style="width:100%; height: 308px; margin-top: -10px;"></div>
                        </div>
                    </div>

                    <div class="grid__col l-one-half">
                        <h2 class="infringement-report__subheader infringement-report__subheader--border-bottom">Closed Listings</h2>
                        <div class="infringement-report__data-block infringement-report__data-block--chart">
                            <div id="closed_listings_chart" style="width:100%; height: 300px; margin-top: -10px;"></div>
                        </div>
                    </div>

                    <div class="grid__col l-one-half">
                        <h2 class="infringement-report__subheader infringement-report__subheader--border-top">Report Summary</h2>
                        <div class="infringement-report__data-block infringement-report__data-block--report-summary">
                            <ul class="infringement-report__data-list">
                                <li>
                                    <span>{{ $reportData->summary->discovered }}</span>
                                    Discovered
                                </li>
                                <li>
                                    <span>{{ $reportData->summary->closed }}</span>
                                    Closed
                                </li>
                                <li>
                                    <span>{{ $reportData->summary->pending }}</span>
                                    Pending
                                </li>
                                <li>
                                    <span>{{ $reportData->summary->allTimeRemoved }}</span>
                                    All Time Removed
                                </li>
                            </ul>
                        </div>

                        @if ($reportData->logo)
                            <img src="{{ $reportData->logo }}" class="infringement-report__logo" />
                        @endif
                    </div>

                    <div class="grid__col l-one-half">
                        <h2 class="infringement-report__subheader infringement-report__subheader--border-top">Enforcement Breakdown</h2>
                        <div class="infringement-report__data-block infringement-report__data-block--feature">
                            <header class="infringement-report__data-block__header">
                                <p>Closed {{ $reportData->summary->closed }}</p>
                            </header>
                            <div class="infringement-report__data-block__content">
                                <ul class="infringement-report__data-list">
                                    @foreach($reportData->breakdown->labels as $key => $label)
                                        @if($label && $reportData->breakdown->data[$key])
                                            <li>
                                                <span>{{ $reportData->breakdown->data[$key] }}</span>
                                                {{ $label }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </body>
</html>
