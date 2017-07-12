var Vue = require('vue')
var _ = require('underscore')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            isSending: false,
            modal: null,
            payload: {
                first_name: null,
                last_name: null,
                email: null,
                urls: null,
                type: null,
                information: null
            },
            typeOptions: [
                {id: 'Trademark', label: 'Trademark'},
                {id: 'Copyright', label: 'Copyright'},
            ]
        }
    },
    mounted: function () {

        // Instantiate modal
        this.modal = $('[data-remodal-id=submit-form]').remodal({
            hashTracking: false
        })

        // Listen to events
        this.$root.bus.$on('submitForm.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to events
        this.$root.bus.$off('submitForm.open', this.onOpen)

    },
    methods: {

        /**
         * Submit the form.
         */
        submit: function () {

            // Already uploading
            if (this.isSending) return

            // Required fields have not been completed
            if (
                ! this.payload.first_name ||
                ! this.payload.last_name ||
                ! this.payload.email ||
                ! this.payload.urls ||
                ! this.payload.type
            ) return

            // Set flag
            this.isSending = true

            // Transform payload
            let payload = _.mapObject(this.payload, (v, k) => {
                if ('type' == k) return v.id
                return v
            })

            // Send request to API
            this.$http.post('submissions', payload)
                .then(function (response) {

                    // Clear flag
                    this.isSending = false

                    // Close modal
                    this.closeModal()

                    // Send success message
                    this.$root.bus.$emit('success', 'Your submission has been dispatched')

                }, function (response) {

                    // Clear flag
                    this.isImporting = false

                    // Send error message
                    this.$root.bus.$emit('error', 'Your submission could not be dispatched at this time')

                });

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
            this.modal.open();
        }

    }
})
