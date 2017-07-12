var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    name: 'RuleListing',
    template: require('./template.html'),
    components: {
        'rule-creator': require('../ruleCreator'),
        'status-badge': require('../../../global/statusBadge'),
    },
    data: function () {
        return {
            isLoading:    false,
            rules:        [],
            statuses:     require('../../../helpers/discoveryStatuses'),
            statusColors: require('../../../helpers/discoveryStatusColors')
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Rules');

        // Update listing when rule created
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch initial data
        this.fetchRules()
    },
    beforeDestroy: function () {

        // Unlisten to reload event
        this.$root.bus.$off('data.reload', this.onDataReload)

    },
    methods: {

        /**
         * Fetch rules from API.
         */
        fetchRules: function ()
        {
            // Set loading flag
            this.isLoading = true

            // Clear rules
            this.assets = []

            // Make API request
            this.$http.get('rules')
                .then(function (response) {

                    // Save collection
                    this.rules = response.data.data

                    // Clear loading flag
                    this.isLoading = false

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.isLoading = false

                    // Send generic error
                    this.$root.bus.$emit('error', 'Rules could not be loaded');

                })
        },

        /**
         * Send an event to open the create modal.
         */
        openRuleCreator: function () {
            this.$root.bus.$emit('ruleCreator.open');
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchRules()
        }

    }
})
