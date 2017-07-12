var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['account'],
    data: function () {
        return {
            modal: null,
            isAttaching: false,
            renderForm: true,
            users: [],
            user: null,
            errors: []
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=user_attacher]').remodal({
            hashTracking: false
        })

        // Register open event
        this.$root.bus.$on('userAttacher.open', this.onOpen)

    },
    beforeDestroy: function () {

        //Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to open event
        this.$root.bus.$off('userAttacher.open', this.onOpen)

    },
    methods: {

        /**
         * Open modal.
         */
        openModal: function ()
        {
            //Fetch data if we need it
            if (! this.users.length) {
                this.fetchUsers()
            }

            //Open the modal
            this.modal.open()
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Reset data for this creator.
         */
        reset: function ()
        {
            //Clear selected user
            this.user = null

            //Re-render the form to fix chosen select BS
            this.renderForm = false
            window.setTimeout(function () {
                this.renderForm = true
            }.bind(this), 1)
        },

        /**
         * Fetch users for dropdown.
         */
        fetchUsers: function ()
        {
            //Clear users
            this.users = []

            //Make API request
            this.$http.get('users')
                .then(function (response) {

                    //Loop through users
                    _.each(response.data.data, function (user) {

                        //Push user data on to stack
                        this.users.push(_.extend(
                            {id: user.id},
                            user.attributes
                        ));

                    }.bind(this))

                }.bind(this), function (response) {

                    //Close the modal
                    this.closeModal()

                    //Send generic error
                    this.$root.bus.$emit('error', 'We could not fetch the users list required to attach a user. Please try again later.')

                })
        },

        /**
         * Attach the user.
         */
        attach: function ()
        {
            //Already attaching, bail out
            if (this.isAttaching) return

            //Clear errors
            this.errors = []

            //No user selected, bail out
            if (! this.user) {
                this.errors.push('You must select a user to attach.')
                return
            }

            //Set flag
            this.isAttaching = true

            //Pack our payload
            var payload = {
                data: [
                    {
                        type: 'users',
                        id: this.user
                    }
                ]
            }

            //Send request to API
            this.$http.post('accounts/'+this.account+'/relationships/users', payload)
                .then(function (response) {

                    //Close modal
                    this.closeModal()

                    //Reset data
                    this.reset()

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'User attached successfully.')

                    //Clear flag
                    this.isAttaching = false

                    //Refresh data
                    this.$root.bus.$emit('data.reload')

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isAttaching = false

                });
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.openModal();
        }

    }
})
