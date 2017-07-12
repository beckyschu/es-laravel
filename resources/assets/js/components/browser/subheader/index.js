var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    name: 'BrowserSubheader',
    template: require('./template.html'),
    props: ['title', 'scope'],
    components: {
        'search': require('../../global/search'),
        'quick-status-updator': require('../quickStatusUpdator')
    },
    data: function () {
        return {
            actualTitle: null
        }
    },
    created: function () {
        this.$root.bus.$on('subheader.updateTitle', this.onSubheaderUpdateTitle)

        // Set actual title to initial prop
        this.actualTitle = this.title
    },
    beforeDestroy: function () {
        this.$root.bus.$off('subheader.updateTitle', this.onSubheaderUpdateTitle)
    },
    methods: {

        /**
         * Return switch path with hash for provided listing.
         */
        getSwitchPath: function (listing)
        {
            return {
                path: '/browser/' + listing,
                hash: this.$route.hash
            }
        },

        /**
         * Open the filter selector.
         */
        openFilterSelector: function () {
            this.$root.bus.$emit('filterSelector.open');
        },

        /**
         * Listen to subheader update title events.
         */
        onSubheaderUpdateTitle: function (title) {
            this.actualTitle = title
        },

        /**
         * Return whether or not this listing is filtered.
         *
         * @return bool
         */
        isFiltered: function () {
            if (_.filter(this.scope.filters, function (value, key) {
                    return value && value.length;
                }).length) return true;

            if (_.filter(this.scope.ranges, function (value, key) {
                    return value.from || value.to;
                }).length) return true;

            return false;
        },

        /**
         * Clear scope filters.
         */
        clearFilters: function () {
            this.scope.reset()
        }

    }
})
