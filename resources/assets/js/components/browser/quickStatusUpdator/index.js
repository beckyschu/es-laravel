var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            statuses: require('../../helpers/discoveryStatuses'),
            status: '',
            count: 0
        }
    },
    created: function () {

        // Listen to events
        this.$root.bus.$on('quickStatusUpdator.selectCountUpdated', this.onSelectCountUpdated)
        this.$root.bus.$on('statusUpdator.complete', this.onComplete)

        // Watch status for changes
        this.$watch(function () {
            return this.status
        }, function (newVal, oldVal) {
            if (newVal == '') return
            this.$root.bus.$emit('statusUpdator.open', newVal)
            jQuery('.quick-status-updator select[name=status]').prop('selectedIndex', 0)
            this.status = ''
        })

    },
    beforeDestroy: function () {

        // Unlisten to events
        this.$root.bus.$off('quickStatusUpdator.selectCountUpdated', this.onSelectCountUpdated)
        this.$root.bus.$off('statusUpdator.complete', this.onComplete)

    },
    methods: {

        /**
         * Select count updated event listener.
         */
        onSelectCountUpdated: function (count) {
            this.count = count
        },

        /**
         * Status updator complete event listener.
         */
        onComplete: function () {
            this.status = ''
        }

    }
})
