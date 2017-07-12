var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            isOpen: false,
            files: []
        }
    },
    created: function () {
        this.$root.bus.$on('openDownloads', this.onOpen)
        this.$root.bus.$on('addDownload', this.onAdd)
    },
    beforeDestroy: function () {
        this.$root.bus.$off('openDownloads', this.onOpen)
        this.$root.bus.$off('addDownload', this.onAdd)
    },
    methods: {

        /**
         * Close the downloader.
         */
        close () { this.isOpen = false },

        /**
         * Clear current file listing.
         */
        clear () { this.files = [] },

        /**
         * Event handler for open event.
         */
        onOpen () { this.isOpen = true },

        /**
         * Event handler for add file event.
         */
        onAdd (name, path) {
            this.isOpen = true
            this.files.push({ name, path })
            this.$refs.beeper.play()
        }
    }
})
