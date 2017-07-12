var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['user'],
    data: function () {
        return {
            modal: null,
            isAttaching: false,
            renderForm: true,
            accounts: [],
            account: null,
            errors: []
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=account_attacher]').remodal({
            hashTracking: false
        });

        // Listen to events
        this.$root.bus.$on('accountAttacher.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy();

        // Unlisten to events
        this.$root.bus.$off('accountAttacher.open', this.onOpen)

    },
    methods: {

        /**
         * Open modal.
         */
        openModal: function ()
        {
            //Fetch data if we need it
            if (! this.accounts.length) {
                this.fetchAccounts()
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
            //Clear selected account
            this.account = null

            //Re-render the form to fix chosen select BS
            this.renderForm = false
            window.setTimeout(function () {
                this.renderForm = true
            }.bind(this), 1)
        },

        /**
         * Fetch accounts for dropdown.
         */
        fetchAccounts: function ()
        {
            //Clear accounts
            this.accounts = []

            //Make API request
            this.$http.get('accounts')
                .then(function (response) {

                    //Loop through accounts
                    _.each(response.data.data, function (account) {

                        //Push account data on to stack
                        this.accounts.push(_.extend(
                            {id: account.id},
                            account.attributes
                        ));

                    }.bind(this))

                }.bind(this), function (response) {

                    //Close the modal
                    this.closeModal()

                    //Send generic error
                    this.$root.bus.$emit('error', 'We could not fetch the accounts list required to attach an account. Please try again later.')

                })
        },

        /**
         * Attach the account.
         */
        attach: function ()
        {
            //Already attaching, bail out
            if (this.isAttaching) return

            //Clear errors
            this.errors = []

            //No account selected, bail out
            if (! this.account) {
                this.errors.push('You must select an account to attach.')
                return
            }

            //Set flag
            this.isAttaching = true

            //Pack our payload
            var payload = {
                data: [
                    {
                        type: 'accounts',
                        id: this.account
                    }
                ]
            }

            //Send request to API
            this.$http.post('users/'+this.user+'/relationships/accounts', payload)
                .then(function (response) {

                    //Grab account object from collection
                    var account = _.find(this.accounts, function (a) {
                        return a.id == this.account
                    }.bind(this))

                    //Close modal
                    this.closeModal()

                    //Reset data
                    this.reset()

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Account attached successfully.')

                    //Clear flag
                    this.isAttaching = false

                    //Reload data
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
