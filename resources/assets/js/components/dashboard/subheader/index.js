var Vue = require('vue')

module.exports = Vue.extend({
    name: 'DashboardSubheader',
    template: require('./template.html'),
    components: {
        search: require('../../global/search')
    },
    data: function () {
        return {
            title: 'Dashboard'
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
         * Open the filter selector.
         */
        openFilterSelector: function () {
            this.$root.bus.$emit('filterSelector.open');
        },

        /**
         * Return whether or not this subheader should display a search bar.
         */
        shouldShowSearch: function ()
        {
            return '/dashboard/activity' == this.$route.path
        },

        /**
         * Update title event listener.
         */
        onUpdateTitle: function (title) {
            this.title = title
        }

    }
})
