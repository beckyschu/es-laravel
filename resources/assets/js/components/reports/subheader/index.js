var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            title: 'Reports',
            showGeneratorButtons: false,
            downloadingReport: false,
            reportUrl: null,
        }
    },
    created: function ()
    {
        // Listen to events
        this.$root.bus.$on('subheader.updateTitle', this.onUpdateTitle)
        this.$root.bus.$on('downloadingReport', this.onDownloadingReport)
        this.$root.bus.$on('downloadingReportComplete', this.onDownloadingReportComplete)
        this.$root.bus.$on('setReportUrl', this.onSetReportUrl)
        this.$root.bus.$on('showGeneratorButtons', this.onShowGeneratorButtons)
        this.$root.bus.$on('hideGeneratorButtons', this.onHideGeneratorButtons)
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('subheader.updateTitle', this.onUpdateTitle)
        this.$root.bus.$off('downloadingReport', this.onDownloadingReport)
        this.$root.bus.$off('downloadingReportComplete', this.onDownloadingReportComplete)
        this.$root.bus.$off('setReportUrl', this.onSetReportUrl)
        this.$root.bus.$off('showGeneratorButtons', this.onShowGeneratorButtons)
        this.$root.bus.$off('hideGeneratorButtons', this.onHideGeneratorButtons)
    },
    methods: {

        /**
         * Update title event listener.
         */
        onUpdateTitle: function (title) {
            this.title = title
        },

        onDownloadingReport () {
            this.downloadingReport = true
        },

        onDownloadingReportComplete (url) {
            this.reportUrl = url
            this.downloadingReport = false
        },

        onSetReportUrl (url) {
            this.reportUrl = url
        },

        onShowGeneratorButtons () {
            this.showGeneratorButtons = true
        },

        onHideGeneratorButtons () {
            this.showGeneratorButtons = false
        },

        /**
         * Return whether or not this subheader should display report generator buttons.
         */
        shouldShowGeneratorButtons: function ()
        {
            return this.showGeneratorButtons && (
                'showReport' == this.$route.name || 'generateReport' == this.$route.name
            )
        },

    }
})
