var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')
var auth   = require('../../../authStore')

import Dropzone from 'vue2-dropzone'

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'previewer': require('./previewer'),
        'dropzone': Dropzone
    },
    data: function () {
        return {
            loadingReport: false,
            savingReport: false,
            reportId: null,
            unwatchReport: null,
            report: {},
            defaultReport: {
                details: {
                    name: null,
                    date: null,
                    range: null,
                    scanType: null
                },
                activity: {
                    marketplaces: null,
                    socialMedia: null,
                    websites: null,
                    searchEngines: null,
                    radial: null
                },
                closedPlatforms: {
                    chartRange: {
                        one: '10',
                        two: '100',
                        three: '500',
                        four: '1K',
                        five: '2K',
                        six: '3K'
                    },
                    chineseMarketplaces: 0,
                    domesticMarketplaces: 0,
                    internationalMarketplaces: 0,
                    socialMediaPlatforms: 0,
                    thirdPartyWebsites: 0
                },
                closedListings: {
                    chartData:   [0,0,0,0,0,0],
                    chartLabels: ['','','','','',''],
                    secondaryLabels: ['','','','','','']
                },
                summary: {
                    discovered:     0,
                    closed:         0,
                    pending:        0,
                    allTimeRemoved: 0
                },
                breakdown: {
                    labels: [],
                    data:   [],
                },
                logo: null
            },
            charts: {
                platforms: null,
                listings: null
            },
            auth: auth
        }
    },
    created ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Generate Report');

        // Listen to report download event
        this.$root.bus.$on('downloadReport', this.downloadReport)

        // Watch for ID changes
        this.$watch(
            () => this.$route.params.id,
            (newVal) => this.fetchReport(newVal),
            { immediate: true }
        )
    },
    beforeDestroy ()
    {
        // Clear subheader download button
        this.$root.bus.$emit('clearDownloadReportButton')

        // Unlisten to report download event
        this.$root.bus.$off('downloadReport', this.downloadReport)
    },
    methods: {

        /**
         * Fetch report for given ID.
         */
        fetchReport (id)
        {
            // Set report ID
            this.reportId = id

            // Hide generator buttons
            this.$root.bus.$emit('hideGeneratorButtons')
            this.$root.bus.$emit('setReportUrl', null)

            // Unwatch report
            if (this.unwatchReport) this.unwatchReport()

            // Set report to default values
            this.report = JSON.parse(JSON.stringify(this.defaultReport))

            // No ID provided, bail out
            if (! this.reportId) {
                this.$root.bus.$emit('showGeneratorButtons')
                this.watchReport()
                return
            }

            // Set flag
            this.loadingReport = true

            // Make API request
            this.$http.get('reports/' + this.reportId)
                .then(function (response) {

                    // Set report properties
                    _.extend(this.report, JSON.parse(response.data.data.report))

                    // Set report URL if set
                    this.$root.bus.$emit('setReportUrl', response.data.data.pdf)

                    // Show report generator buttons
                    this.$root.bus.$emit('showGeneratorButtons')

                    // Watch report for changes
                    this.watchReport()

                    // Clear flag
                    this.loadingReport = false

                }.bind(this), function (response) {

                    // Send generic error
                    this.$root.bus.$emit('error')

                    // Clear flag
                    this.loadingReport = false

                })
        },

        /**
         * Save report data to API.
         */
        saveReport ()
        {
            // Already saving, bail out
            if (this.savingReport) return

            // Set flag
            this.savingReport = true

            // Make API request
            let endpoint = this.reportId ? 'reports/' + this.reportId : 'reports'
            this.$http.post(endpoint, {
                report:   this.report,
                cp_chart: this.charts.platforms,
                cl_chart: this.charts.listings,
            })
                .then(function (response) {

                    // Clear loading flag
                    this.savingReport = false

                    // Update report ID
                    this.reportId = response.data.data.id

                    // Send success message
                    this.$root.bus.$emit('success', 'Report saved successfully');

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.savingReport = false

                    // Send generic error
                    this.$root.bus.$emit('error');

                })
        },

        /**
         * Download the report data as a PDF.
         */
        downloadReport ()
        {
            // Already saving, bail out
            if (this.savingReport) return

            // Set flag
            this.savingReport = true

            // Emit event
            this.$root.bus.$emit('downloadingReport')

            // Make API request
            let endpoint = this.reportId ? 'reports/' + this.reportId + '/pdf' : 'reports/pdf'
            this.$http.post(endpoint, {
                report: this.report
            })
                .then(function (response) {

                    // Clear loading flag
                    this.savingReport = false

                    // Update report ID
                    this.reportId = response.data.data.id

                    // Emit event
                    this.$root.bus.$emit('downloadingReportComplete', response.data.data.pdf)

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.savingReport = false

                    // Emit event
                    this.$root.bus.$emit('downloadingReportComplete')

                    // Send generic error
                    this.$root.bus.$emit('error');

                })
        },

        /**
         * Event handler for logo file uploads.
         */
        onLogoUploadSuccess (file, response) {
            this.report.logo = response.data.path
        },

        /**
         * Watch report for changes.
         */
        watchReport () {
            this.unwatchReport = this.$watch('report', () => {
                this.$root.bus.$emit('setReportUrl', null)
            }, { deep: true })
        }

    }
})
