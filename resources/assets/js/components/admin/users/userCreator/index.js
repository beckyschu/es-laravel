var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            user: null,
            userData: {
                first_name: null,
                last_name: null,
                email: null,
                password: null,
                password_confirm: null,
                image: null,
                role: null,
                status: 'active'
            },
            accounts: [],
            attachableAccounts: [], //Collection of accounts available to attach
            attachableAccount: null, //ID for account attach
            modal: null,
            isCreating: false,
            isAttachingAccount: false,
            errors: [],
            userStatuses: require('../../../helpers/basicStatuses'),
            userRoles: require('../../../helpers/userRoles'),
            temporaryPassword: null,
            steps: {
                details:  'User Details',
                accounts: 'Assign Accounts',
            },
            activeStep: 'details',
            completeSteps: [],
            activeAccountForm: null
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=user_creator]').remodal({
            hashTracking: false
        })

        // Listen to events
        this.$root.bus.$on('userCreator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to events
        this.$root.bus.$off('userCreator.open', this.onOpen)

    },
    methods: {

        /**
         * Open the modal.
         */
        openModal: function ()
        {
            if (! this.attachableAccounts.length) {
                this.fetchAttachableAccounts()
            }

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
            this.user = null

            this.userData = {
                first_name: null,
                last_name: null,
                email: null,
                password: null,
                password_confirm: null,
                image: null,
                status: null
            }

            this.accounts          = []
            this.attachableAccount = null

            this.activeStep        = 'details'
            this.completeSteps     = []
            this.activeAccountForm = null

            this.temporaryPassword = null
        },

        /**
         * Complete the process, reset and close.
         */
        complete: function ()
        {
            this.closeModal()

            this.$root.bus.$emit('data.reload')

            window.setTimeout(function () {
                this.reset()
            }.bind(this), 500);
        },

        /**
         * Fetch accounts for attach dropdown.
         */
        fetchAttachableAccounts: function ()
        {
            //Clear accounts
            this.attachableAccounts = []

            //Make API request
            this.$http.get('accounts')
                .then(function (response) {

                    //Loop through accounts
                    _.each(response.data.data, function (account) {

                        //Push account data on to stack
                        this.attachableAccounts.push(_.extend(
                            {id: account.id},
                            account.attributes
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
         * Create the account.
         */
        create: function ()
        {
            //Already creating, bail out
            if (this.isCreating) return;

            //Clear errors
            this.errors = []

            //Passwords do not match
            if (this.userData.password !== this.userData.password_confirm) {
                this.errors.push('Passwords do not match')
                return
            }

            //Set flag
            this.isCreating = true

            //Pack our payload
            var payload = {
                data: {
                    type: 'users',
                    attributes: {
                        first_name: this.userData.first_name,
                        last_name:  this.userData.last_name,
                        email:      this.userData.email,
                        password:   this.userData.password,
                        role:       this.userData.role,
                        status:     this.userData.status
                    }
                }
            }

            //Send request to API
            this.$http.post('users', payload)
                .then(function (response) {

                    // Store user data
                    this.user = _.extend(
                        {id: response.data.data.id},
                        response.data.data.attributes
                    )

                    // Capture temporary password
                    this.temporaryPassword = response.data.meta.password

                    // We have a file to upload
                    if (this.userData.image)
                    {
                        var formData = new FormData
                        formData.append('image', this.userData.image)

                        this.$http.post('users/'+this.user.id+'/image', formData)
                            .then(function (response) {

                                // Advance step
                                this.advanceStep('details', 'accounts')

                                // Clear flag
                                this.isCreating = false

                            }, function (response) {

                                // Loop errors and add to collection
                                _.each(response.data.errors, function (error) {
                                    this.errors.push(error.detail)
                                }.bind(this));

                                // Clear flag
                                this.isCreating = false

                            })
                    }

                    // We have no image to upload
                    else {

                        // Advance step
                        this.advanceStep('details', 'accounts')

                        // Clear flag
                        this.isCreating = false

                    }

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
         * Select a new image for upload.
         */
        selectImage: function (e)
        {
            this.userData.image = e.target.files[0]
        },

        /**
         * Attach the account.
         */
        attachAccount: function ()
        {
            //Already attaching, bail out
            if (this.isAttachingAccount) return

            //Clear errors
            this.errors = []

            //No account selected, bail out
            if (! this.attachableAccount) {
                this.errors.push('You must select an account to attach.')
                return
            }

            //Set flag
            this.isAttachingAccount = true

            //Pack our payload
            var payload = {
                data: [
                    {
                        type: 'accounts',
                        id:   this.attachableAccount
                    }
                ]
            }

            //Send request to API
            this.$http.post('users/'+this.user.id+'/relationships/accounts', payload)
                .then(function (response) {

                    //Clear flag
                    this.isAttachingAccount = false

                    //Find attached account and add to list
                    this.accounts.push(_.find(this.attachableAccounts, function (account) {
                        return account.id == this.attachableAccount
                    }.bind(this)));

                    //Clear form data
                    this.attachableAccount = null

                    //Go back to account listing
                    this.activeAccountForm = null

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isAttachingAccount = false

                });
        },

        /**
         * Complete the given step.
         */
        completeStep: function (step)
        {
            this.completeSteps.push(step)
        },

        /**
         * Set the active step.
         */
        setStep: function (step)
        {
            this.activeStep = step
        },

        /**
         * Advance step from prev to next.
         */
        advanceStep: function (prev, next)
        {
            this.completeStep(prev)
            this.setStep(next)
        },

        /**
         * Return whether or not the given step is active.
         */
        isStepActive: function (step)
        {
            return step == this.activeStep
        },

        /**
         * Return whether or not the given step is complete.
         */
        isStepComplete: function (step)
        {
            return _.contains(this.completeSteps, step)
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.openModal()
        }

    }
})
