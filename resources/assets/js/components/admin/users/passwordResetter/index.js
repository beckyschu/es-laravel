var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['user'],
    data: function () {
        return {
            isResetting: false,
            errors: [],
            temporaryPassword: null
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=password_resetter]').remodal({
            hashTracking: false
        })

        // Listen to events
        this.$root.bus.$on('passwordResetter.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to events
        this.$root.bus.$off('passwordResetter.open', this.onOpen)

    },
    methods: {

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Reset data for this creator.
         */
        reset: function () {
            this.temporaryPassword = null
        },

        /**
         * Complete the process, reset and close.
         */
        complete: function ()
        {
            this.closeModal()

            window.setTimeout(function () {
                this.reset()
            }.bind(this), 500);
        },

        /**
         * Reset the password.
         */
        resetPassword: function ()
        {
            //Already resetting, bail out
            if (this.isResetting) return;

            //Set flag
            this.isResetting = true

            //Clear errors
            this.errors = []

            //Send request to API
            this.$http.post('users/'+this.user+'/reset-password')
                .then(function (response) {

                    //Clear flag
                    this.isResetting = false

                    //Capture temporary password
                    this.temporaryPassword = response.data.meta.password

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isCreating = false

                });
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.modal.open()
        }

    }
})
