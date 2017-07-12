var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    name: 'RuleShow',
    template: require('./template.html'),
    components: {
        multiselect: VueMultiselect,
        'rule-editor': require('../ruleEditor')
    },
    data: function () {
        return {
            id:           this.$route.params.id,
            rule:         null,
            assetOptions: [],
            assetLoading: false,
            assetRequest: null,
            isUpdating:   false,
            errors:       [],
            platforms:    require('../../../helpers/platforms'),
            statuses:     require('../../../helpers/discoveryStatuses'),
            isActiveOptions: [
                {id: true, label: 'Active'},
                {id: false, label: 'Disabled'},
            ]
        }
    },
    computed: {
        statusOptions: function () {
            return _.map(this.statuses, function (label, key) {
                return {
                    id: key,
                    label: label
                }
            })
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Rules - ' + this.id)

        // Listen to data reload events
        this.$root.bus.$on('data.reload', this.onDataReload);

        // Fetch rule
        this.fetchRule()
    },
    beforeDestroy: function ()
    {
        // Unlisten to data reload event
        this.$root.bus.$off('data.reload', this.onDataReload);
    },
    methods: {

        /**
         * Fetch rule for current id.
         */
        fetchRule: function () {

            // Clear asset
            this.rule = null

            // Make API request
            this.$http.get('rules/'+this.id)
                .then(function (response) {

                    // Grab rule data
                    this.rule = response.data

                    // Format status
                    this.rule.status = {
                        id:    this.rule.status,
                        label: this.statuses[this.rule.status]
                    }

                    // Format isActive status
                    this.rule.is_active = {
                        id:    this.rule.is_active,
                        label: this.rule.is_active ? 'Active' : 'Disabled'
                    }

                }.bind(this), function (response) {

                    // Send generic error
                    this.$root.bus.$emit('error', 'Rule could not be fetched')

                })

        },

        /**
         * Update this rule.
         */
        update: function ()
        {
            // Rule is locked
            if (this.rule.is_locked) return

            // Already updating
            if (this.isUpdating) return

            // Set updating flag
            this.isUpdating = true

            // Clear errors
            this.errors = []

            // Pack our payload
            var payload = {
                comment:   this.rule.comment,
                status:    this.rule.status.id,
                priority:  this.rule.priority,
                is_active: this.rule.is_active.id,
                rule:      this.rule.rule
            };

            // Send request to API
            this.$http.patch('rules/'+this.id, payload)
                .then(function (response) {

                    // Capture transaction if provided
                    var options = {}
                    var headers = response.headers;
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Rule updated successfully.', options)

                    // Clear saving flag
                    this.isUpdating = false

                }, function (response) {

                    // Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    // Clear saving flag
                    this.isUpdating = false

                });
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchRule()
        }

    }
})
