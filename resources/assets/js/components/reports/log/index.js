var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'status-badge': require('../../global/statusBadge')
    },
    data: function () {
        return {
            isLoading: false,
            isDeleting: false,
            isDownloading: false,
            reports: [],
            selected: []
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'The Report');

        // Listen to reload event
        this.$root.bus.$on('data.reload', this.onDataReload);

        // Fetch initial data
        this.fetchReports()
    },
    beforeDestroy: function ()
    {
        // Unlisten to reload event
        this.$root.bus.$off('data.reload', this.onDataReload);
    },
    methods: {

        /**
         * Fetch reports from API.
         */
        fetchReports: function ()
        {
            // Set loading flag
            this.isLoading = true

            // Clear reports
            this.reports = []

            // Make API request
            this.$http.get('reports')
                .then(function (response) {

                    // Set reports collection
                    this.reports = response.data.data

                    // Clear loading flag
                    this.isLoading = false

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.isLoading = false

                    // Send generic error
                    this.$root.bus.$emit('error');

                })
        },

        /**
         * Data reload listener.
         */
        onDataReload: function () {
            this.fetchReports()
        },

        /**
         * Toggle all report rows.
         */
        toggleAll: function ()
        {
            // All are currently selected, deselect all
            if (this.selected.length == this.reports.length) {
                this.selected = []
                return
            }

            // Some are unselected, select all
            _.each(this.reports, (report) => {
                if (! _.contains(this.selected, report.id)) {
                    this.selected.push(report.id)
                }
            })
        },

        /**
         * Toggle the given row.
         */
        toggle: function (reportId)
        {
            // Already selected, deselect
            if (this.selected.includes(reportId)) {
                this.selected = _.without(this.selected, reportId)
                return
            }

            // Not selected yet, select
            this.selected.push(reportId)
        },

        /**
         * Return whether or not all selected reports have PDF available.
         */
        massDownloadAvailable () {
            return ! _.filter(this.selected, (reportId) => {
                let report = _.find(this.reports, (report) => report.id == reportId)
                return ! report.pdf
            }).length
        },

        /**
         * Send delete reports request to API.
         */
        deleteReports ()
        {
            // None selected, bail out
            if (! this.selected.length) return

            // Already deleting, bail out
            if (this.isDeleting) return

            // Set flag
            this.isDeleting = true

            // Define request options
            let options = {
                body: {
                    reports: this.selected
                }
            }

            // Make API request
            this.$http.delete('reports', options)
                .then(function (response) {

                    // Clear deleted reports from collection
                    this.reports = _.filter(this.reports, (report) => ! this.selected.includes(report.id))

                    // Clear selected collection
                    this.selected = []

                    // Clear deleting flag
                    this.isDeleting = false

                }.bind(this), function (response) {

                    // Clear deleting flag
                    this.isDeleting = false

                    // Send generic error
                    this.$root.bus.$emit('error');

                })
        },

        /**
         * Send download reports request to API.
         */
        downloadReports ()
        {
            // None selected, bail out
            if (! this.selected.length) return

            // Already downloading, bail out
            if (this.isDownloading) return

            // Set flag
            this.isDownloading = true

            // Define request body
            let body = {
                reports: this.selected
            }

            // Make API request
            this.$http.post('reports/download', body)
                .then(function (response) {

                    // Loop files and dispatch to downloader
                    _.each(response.data.data, (file) => {
                        this.$root.bus.$emit('addDownload', file.name, file.path)
                    })

                    // Clear downloading flag
                    this.isDownloading = false

                }.bind(this), function (response) {

                    // Clear downloading flag
                    this.isDownloading = false

                    // Send generic error
                    this.$root.bus.$emit('error');

                })
        }

    }
})
