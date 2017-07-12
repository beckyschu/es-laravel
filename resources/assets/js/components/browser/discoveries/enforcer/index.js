var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['count'],
    data: function () {
        return {
            isEnforcing: false,
            modal: null,
            ctaLabel: 'Enforce listings',
            exportFile: null,
            step: 1
        }
    },
    mounted: function () {

        // Instantiate modal
        this.modal = $('[data-remodal-id=enforcer]').remodal({
            hashTracking: false
        })

        // Listen to events
        this.$root.bus.$on('discoveryEnforcer.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy();

        // Unlisten to events
        this.$root.bus.$off('discoveryEnforcer.open', this.onOpen)

    },
    methods: {

        /**
         * Enforce the applicable listings.
         */
        enforce: function ()
        {
            // Already enforcing, bail out
            if (this.isEnforcing) return

            // Set flag
            this.isEnforcing = true

            // Update CTA label for first step
            this.ctaLabel = 'Generating CSV...'

            // Generate filter payload
            var filterPayload = {
                'filter[status]': 'enforce'
            }

            // Generate CSV and store location
            this.$http.get('discoveries/export', {
                params: filterPayload
            })
                .then(function (response) {

                    // Grab URL for generated CSV
                    var url = response.data.data

                    // Store CSV location
                    this.exportFile = url

                    // Update CTA label
                    this.ctaLabel = 'Updating statuses...'

                    // Build status update payload
                    var payload = {
                        status:  'pending',
                        comment: 'Manual enforcer'
                    }

                    // Update statuses
                    this.$http.patch('discoveries', payload, {
                        params: filterPayload
                    })
                        .then(function (response)
                        {
                            // Clear loading flag
                            this.isExporting = false
                            this.ctaLabel    = 'Enforce listings'

                            // Loop response data
                            _.each(response.data.data, function (entity) {

                                // Fire update event so that status badges update
                                this.$root.bus.$emit('status.update', entity.type, entity.id, this.status)

                            }.bind(this))

                            // Advance step
                            this.step++

                        }, function (response) {

                            // Clear loading flag
                            this.isExporting = false
                            this.ctaLabel    = 'Enforce listings'

                            // Dispatch generic error
                            this.$root.bus.$emit('error')

                        });

                }.bind(this), function (response) {

                    // Clear loading flag
                    this.isExporting = false
                    this.ctaLabel    = 'Enforce listings'

                    // Dispatch generic error
                    this.$root.bus.$emit('error')

                })
        },

        /**
         * Finish the process.
         */
        finish: function ()
        {
            // Clear loading flag
            this.isEnforcing = false
            this.ctaLabel    = 'Enforce listings'

            // Set step back to start
            this.step = 1

            // Clear export
            this.exportFile = null

            // Close modal
            this.closeModal()

            // Refresh data
            this.$root.bus.$emit('data.reload')
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.modal.open()
        }

    }
})
