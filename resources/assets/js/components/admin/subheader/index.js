var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth'],
    components: {
        search: require('../../global/search')
    },
    data: function () {
        return {
            title: null
        }
    },
    created: function ()
    {
        // Listen to events
        this.$root.bus.$on('subheader.updateTitle', this.onUpdateTitle)
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('subheader.updateTitle', this.onUpdateTitle)
    },
    methods: {

        /**
         * Return whether or not this subheader should display a search bar.
         */
        shouldShowSearch: function ()
        {
            return '/admin/users' == this.$route.path
        },

        /**
         * Update title event listener.
         */
        onUpdateTitle: function (title) {
            this.title = title
        },

        /**
         * Open the crawl scheduler.
         */
        openCrawlScheduler: function () {
            this.$root.bus.$emit('crawlScheduler.open')
        }

    }
})
