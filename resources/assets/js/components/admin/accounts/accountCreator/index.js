var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    props: ['auth'],
    template: require('./template.html'),
    components: {
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            account: null,
            accountData: { //Data for account create
                name: null,
                status: 'active'
            },
            users: [],
            userData: { //Data for user create
                first_name: null,
                last_name: null,
                email: null,
                password: null,
                password_confirm: null,
                image: null,
                role: null,
                status: 'active'
            },
            attachableUsers: [], //Collection of users available to attach
            attachableUser: null, //ID for user attach
            assets: [],
            assetData: {
                name: null,
                description: null,
                keywords: [],
                counter_keywords: [],
                status: 'active'
            },
            modal: null,
            isCreating: false,      // Creating account
            isUpdating: false,      // Updating account
            isCreatingUser: false,  // Creating user
            isAttachingUser: false, // Attaching user
            isCreatingAsset: false, // Creating asset
            errors: [],
            accountStatuses: require('../../../helpers/accountStatuses'),
            userStatuses: require('../../../helpers/basicStatuses'),
            assetStatuses: require('../../../helpers/basicStatuses'),
            userRoles: require('../../../helpers/userRoles'),
            steps: {
                details: 'Account Details',
                users:   'Assign Users',
                assets:  'Assign Assets'
            },
            activeStep: 'details',
            completeSteps: [],
            activeUserForm: null,
            activeAssetForm: null,
        }
    },
    mounted: function() {

        // Create the modal
        this.modal = $('[data-remodal-id=account_creator]').remodal({
            hashTracking: false
        })

        // Listen to open event
        this.$root.bus.$on('accountCreator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to open event
        this.$root.bus.$off('accountCreator.open', this.onOpen)

    },
    methods: {

        /**
         * Open the modal.
         */
        openModal: function ()
        {
            if (! this.attachableUsers.length) {
                this.fetchAttachableUsers()
            }

            this.modal.open();
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Complete the process.
         */
        complete: function ()
        {
            this.closeModal()

            this.$root.bus.$emit('data.reload')

            setTimeout(function () {
                this.account             = null

                this.accountData.name    = null
                this.accountData.status  = null

                this.users               = []

                this.userData.first_name       = null
                this.userData.last_name        = null
                this.userData.email            = null
                this.userData.password         = null
                this.userData.password_confirm = null
                this.userData.image            = null
                this.userData.role             = null
                this.userData.status           = null

                this.attachableUser      = null

                this.assets                = []

                this.assetData.name             = null
                this.assetData.description      = null
                this.assetData.keywords         = []
                this.assetData.counter_keywords = []
                this.assetData.status           = null

                this.errors              = []

                this.activeStep          = 'details'
                this.completeSteps       = []

                this.activeUserForm      = null
                this.activeAssetForm     = null
            }.bind(this), 500);
        },

        /**
         * Fetch users for attach dropdown.
         */
        fetchAttachableUsers: function ()
        {
            //Clear users
            this.attachableUsers = []

            //Make API request
            this.$http.get('users')
                .then(function (response) {

                    //Loop through users
                    _.each(response.data.data, function (user) {

                        //Push user data on to stack
                        this.attachableUsers.push(_.extend(
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
         * Create the account.
         */
        create: function ()
        {
            //Already creating, bail out
            if (this.isCreating) return;

            //Account already exists, update instead
            if (this.account) {
                return this.update()
            }

            //Set flag
            this.isCreating = true

            //Clear errors
            this.errors = []

            //Pack our payload
            var payload = {
                data: {
                    type: 'accounts',
                    attributes: {
                        name:   this.accountData.name,
                        status: this.accountData.status
                    }
                }
            }

            //Send request to API
            this.$http.post('accounts', payload)
                .then(function (response) {

                    //Advance step
                    this.completeStep('details')
                    this.setStep('users')

                    //Clear flag
                    this.isCreating = false

                    //Store account data
                    this.account = _.extend(
                        {id: response.data.data.id},
                        response.data.data.attributes
                    )

                    //Add account in auth store
                    this.auth.accounts.push(this.account)

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
         * Update the account.
         */
        update: function ()
        {
            //Already updating, bail out
            if (this.isUpdating) return;

            //Set flag
            this.isUpdating = true

            //Clear errors
            this.errors = []

            //Pack our payload
            var payload = {
                data: {
                    type: 'accounts',
                    attributes: {
                        name:   this.accountData.name,
                        status: this.accountData.status
                    }
                }
            }

            //Send request to API
            this.$http.patch('accounts/'+this.account.id, payload)
                .then(function (response) {

                    //Advance step
                    this.completeStep('details')
                    this.setStep('users')

                    //Clear flag
                    this.isUpdating = false

                    //Store account data
                    this.account = _.extend(
                        {id: response.data.data.id},
                        response.data.data.attributes
                    )

                    //Update account in auth store
                    this.auth.accounts.$set(
                        _.findIndex(this.auth.accounts, function (account) {
                            return account.id == this.account.id
                        }.bind(this)),
                        this.account
                    )

                    //Update current account if selected
                    if (this.auth.account && this.auth.account.id == this.account.id) {
                        this.auth.account = this.account
                    }

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isUpdating = false

                });
        },

        /**
         * Create the user.
         */
        createUser: function ()
        {
            //Already creating, bail out
            if (this.isCreatingUser) return;

            //Clear errors
            this.errors = []

            //Passwords do not match
            if (this.userData.password !== this.userData.password_confirm) {
                this.errors.push('Passwords do not match')
                return
            }

            //Set flag
            this.isCreatingUser = true

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
                    },
                    relationships: {
                        accounts: {
                            data: [{
                                type: 'accounts',
                                id:   this.account.id
                            }]
                        }
                    }
                }
            }

            //Send request to API
            this.$http.post('users', payload)
                .then(function (response) {

                    // Store user data
                    this.users.push(_.extend(
                        {id: response.data.data.id},
                        response.data.data.attributes
                    ))

                    // Store this user ID
                    var userId = response.data.data.id

                    // We have a file to upload
                    if (this.userData.image)
                    {
                        var formData = new FormData
                        formData.append('image', this.userData.image)

                        this.$http.post('users/'+userId+'/image', formData)
                            .then(function (response) {

                                // Clear flag
                                this.isCreatingUser = false

                                // Clear form data
                                this.userData.first_name       = null
                                this.userData.last_name        = null
                                this.userData.email            = null
                                this.userData.password         = null
                                this.userData.password_confirm = null
                                this.userData.image            = null
                                this.userData.role             = null
                                this.userData.status           = null

                                // Go back to user listing
                                this.activeUserForm = null

                            }, function (response) {

                                // Loop errors and add to collection
                                _.each(response.data.errors, function (error) {
                                    this.errors.push(error.detail)
                                }.bind(this));

                                // Clear flag
                                this.isCreatingUser = false

                            })
                    }

                    // We have no image to upload
                    else {

                        //Clear flag
                        this.isCreatingUser = false

                        // Clear form data
                        this.userData.first_name       = null
                        this.userData.last_name        = null
                        this.userData.email            = null
                        this.userData.password         = null
                        this.userData.password_confirm = null
                        this.userData.image            = null
                        this.userData.role             = null
                        this.userData.status           = null

                        // Go back to user listing
                        this.activeUserForm = null

                    }

                }, function (response) {

                    // Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    // Clear flag
                    this.isCreatingUser = false

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
         * Attach the selected user.
         */
        attachUser: function ()
        {
            //Already attaching, bail out
            if (this.isAttachingUser) return

            //Clear errors
            this.errors = []

            //No user selected, bail out
            if (! this.attachableUser) {
                this.errors.push('You must select a user to attach.')
                return
            }

            //Set flag
            this.isAttachingUser = true

            //Pack our payload
            var payload = {
                data: [
                    {
                        type: 'users',
                        id:   this.attachableUser
                    }
                ]
            }

            //Send request to API
            this.$http.post('accounts/'+this.account.id+'/relationships/users', payload)
                .then(function (response) {

                    //Clear flag
                    this.isAttachingUser = false

                    //Find attached user and add to list
                    this.users.push(_.find(this.attachableUsers, function (user) {
                        return user.id == this.attachableUser
                    }.bind(this)));

                    //Clear form data
                    this.attachableUser = null

                    //Go back to user listing
                    this.activeUserForm = null

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isAttachingUser = false

                });
        },

        /**
         * Create the asset.
         */
        createAsset: function ()
        {
            //Already creating, bail out
            if (this.isCreatingAsset) return;

            //Clear errors
            this.errors = []

            //Set flag
            this.isCreatingAsset = true

            //Pack our payload
            var payload = {
                data: {
                    type: 'assets',
                    attributes: {
                        name:             this.assetData.name,
                        description:      this.assetData.description,
                        keywords:         this.assetData.keywords,
                        counter_keywords: this.assetData.counter_keywords,
                        status:           this.assetData.status
                    },
                    relationships: {
                        account: {
                            data: {
                                type: 'accounts',
                                id: this.account.id
                            }
                        }
                    }
                }
            }

            //Send request to API
            this.$http.post('assets', payload)
                .then(function (response) {

                    //Clear flag
                    this.isCreatingAsset = false

                    //Store asset data
                    this.assets.push(_.extend(
                        {id: response.data.data.id},
                        response.data.data.attributes
                    ))

                    //Clear form data
                    this.assetData.name             = null
                    this.assetData.description      = null
                    this.assetData.keywords         = []
                    this.assetData.counter_keywords = []
                    this.assetData.status           = null

                    //Go back to user listing
                    this.activeAssetForm = null

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isCreatingAsset = false

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
        },

        /**
         * Add keyword tag event.
         */
        addKeywordTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.assetData.keywords, newTag)) return

            // Push tag into asset data
            this.assetData.keywords.push(newTag)
        },

        /**
         * Add counter keyword tag event.
         */
        addCounterKeywordTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.assetData.counter_keywords, newTag)) return

            // Push tag into asset data
            this.assetData.counter_keywords.push(newTag)
        }

    }
})
