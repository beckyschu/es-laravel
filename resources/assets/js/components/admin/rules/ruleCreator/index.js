var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            rule: {
                status: null,
                priority: 1,
                comment: null
            },
            modal: null,
            isCreating: false,
            errors: [],
            statuses: require('../../../helpers/discoveryStatuses')
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
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=rule_creator]').remodal({
            hashTracking: false
        });

        // Register open event
        this.$root.bus.$on('ruleCreator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy();

        // Unlisten to open event
        this.$root.bus.$off('ruleCreator.open', this.onOpen)

    },
    methods: {

        /**
         * Open modal.
         */
        openModal: function ()
        {
            this.modal.open()
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Create the asset.
         */
        create: function ()
        {
            // Already creating, bail out
            if (this.isCreating) return;

            // Clear errors
            this.errors = []

            // Set flag
            this.isCreating = true

            // Pack payload
            let payload = {
                status:   this.rule.status.id,
                priority: this.rule.priority,
                comment:  this.rule.comment
            }

            // Send request to API
            this.$http.post('rules', payload)
                .then(function (response) {

                    // Close modal
                    this.closeModal()

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Rule created successfully.')

                    // Clear flag
                    this.isCreating = false

                    // Redirect to rule page
                    this.$router.push('/admin/rules/'+response.data.id)

                }, function (response) {

                    // Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    // Clear flag
                    this.isCreating = false

                });
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.openModal()
        }

    }
})
