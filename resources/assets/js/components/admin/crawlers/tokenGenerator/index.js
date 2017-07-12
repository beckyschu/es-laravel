var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['crawler'],
    data: function () {
        return {
            modal: null,
            token: null,
            isLoading: false
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=token_generator]').remodal({
            hashTracking: false
        });

        // Register open event
        this.$root.bus.$on('tokenGenerator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to events
        this.$root.bus.$off('tokenGenerator.open', this.onOpen)

    },
    methods: {

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Get token for the provided crawler.
         */
        getToken: function ()
        {
            //Already loading, bail out
            if (this.isLoading) return;

            //Set flag
            this.isLoading = true

            //Send request to API
            this.$http.get('crawlers/'+this.crawler+'/token')
                .then(function (response) {

                    //Clear flag
                    this.isLoading = false

                    //Set token
                    this.token = response.data.data

                }, function (response) {

                    //Close modal
                    this.closeModal()

                    //Dispatch generic error
                    this.$root.bus.$emit('error', 'Token could not be retrieved at this time. Please try again later.')

                    //Clear flag
                    this.isLoading = false

                });
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.getToken()
            this.modal.open()
        }

    }
})
