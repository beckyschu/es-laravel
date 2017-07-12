var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['asset'],
    data: function () {
        return {
            keyword: {
                asset: {
                    id: this.asset.id
                },
                keyword: null
            },
            modal: null,
            isCreating: false,
            errors: []
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=keyword_creator]').remodal({
            hashTracking: false
        });

        // Register filter selector open event
        this.$root.bus.$on('keywordCreator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy();

        // Unlisten to open event
        this.$root.bus.$off('keywordCreator.open', this.onOpen)

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
         * Create the keyword.
         */
        create: function ()
        {
            // Already creating, bail out
            if (this.isCreating) return;

            // Clear errors
            this.errors = []

            // Set flag
            this.isCreating = true

            // Send request to API
            this.$http.post('keywords', this.keyword)
                .then(function (response) {

                    // Close modal
                    this.closeModal()

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Keyword created successfully.')

                    // Clear flag
                    this.isCreating = false

                    // Refresh data
                    this.$root.bus.$emit('data.reload')

                }, function (response) {

                    // Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this))

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
