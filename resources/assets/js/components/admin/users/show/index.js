var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'account-attacher':  require('../accountAttacher'),
        'password-resetter': require('../passwordResetter'),
        'status-badge':      require('../../../global/statusBadge')
    },
    data: function () {
        return {
            id: this.$route.params.id,
            user: null,
            image: null,
            basicStatuses: require('../../../helpers/basicStatuses'),
            basicStatusColors: require('../../../helpers/basicStatusColors'),
            userRoles: require('../../../helpers/userRoles'),
            visibleSections: ['accounts'],
            isSaving: false,
            isSettingDefaultAccount: false,
            isDetachingAccount: false,
            errors: [],

            // Deletion system
            deletePassword: null,
            deletePermanent: false,
            showDeletePassword: false,
            isDeleting: false
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Users - '+this.id)

        // Listen to events
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch data
        this.fetchUser()
    },
    beforeDestroy: function ()
    {
        // Unisten to events
        this.$root.bus.$off('data.reload', this.onDataReload)
    },
    methods: {

        /**
         * Fetch user for current id.
         */
        fetchUser: function () {

            //Clear user
            this.user = null

            //Make API request
            this.$http.get('users/'+this.id)
                .then(function (response) {

                    //Grab user
                    var user = response.data.data

                    //Fetch accounts
                    var accounts = []
                    _.each(user.relationships.accounts.data, function (account) {

                        var entity = _.find(response.data.included, function (include) {
                            return 'accounts' == include.type && account.id == include.id;
                        })

                        accounts.push(_.extend(
                            {id: entity.id},
                            entity.attributes
                        ))

                    })

                    //Push user data
                    this.user = _.extend(
                        {
                            id: user.id,
                            accounts: accounts
                        },
                        user.attributes
                    )

                    //Update subheader title
                    this.$root.bus.$emit('subheader.updateTitle', 'Users - '+this.user.first_name+' '+this.user.last_name)

                }.bind(this), function (response) {

                    //Send generic error
                    this.$root.bus.$emit('error')

                })

        },

        /**
         * Generate a "from now" timestamp.
         */
        fromNow: function (date) {
            return moment(date).fromNow();
        },

        /**
         * Toggle the given section.
         */
        toggle: function (section)
        {
            if (this.sectionIsVisible(section)) {
                this.visibleSections = _.without(this.visibleSections, section)
            } else {
                this.visibleSections.push(section);
            }
        },

        /**
         * Returns whether or not the given section should be visible.
         */
        sectionIsVisible: function (section) {
            return _.contains(this.visibleSections, section);
        },

        /**
         * Update the details for this user.
         */
        update: function ()
        {
            //Already saving
            if (this.isSaving) return

            //Clear errors
            this.errors = []

            //Passwords do not match
            if (this.user.password !== this.user.password_confirm) {
                this.errors.push('Passwords do not match')
                return
            }

            //Set saving flag
            this.isSaving = true

            //Pack our payload
            var payload = {
                data: {
                    type: 'users',
                    id: this.id,
                    attributes: {
                        first_name: this.user.first_name,
                        last_name:  this.user.last_name,
                        email:      this.user.email,
                        role:       this.user.role,
                        status:     this.user.status
                    }
                }
            };

            //Send request to API
            this.$http.patch('users/'+this.id, payload)
                .then(function (response) {

                    // We have a file to upload
                    if (this.image)
                    {
                        var formData = new FormData
                        formData.append('image', this.image)

                        this.$http.post('users/'+this.id+'/image', formData)
                            .then(function (response) {

                                //Capture transaction if provided
                                var options = {}
                                var headers = response.headers();
                                if (_.has(headers, 'x-transaction')) {
                                    options.transaction = {
                                        id: headers['x-transaction'],
                                        canUndo: _.has(headers, 'x-undo')
                                    }
                                }

                                //Dispatch success message
                                this.$root.bus.$emit('success', 'User details updated successfully.', options)

                                //Clear saving flag
                                this.isSaving = false

                            }, function (response) {

                                //Loop errors and add to collection
                                _.each(response.data.errors, function (error) {
                                    this.errors.push(error.detail)
                                }.bind(this));

                                //Clear saving flag
                                this.isSaving = false

                            })
                    }

                    // We have no file to upload
                    else {

                        //Capture transaction if provided
                        var options = {}
                        var headers = response.headers();
                        if (_.has(headers, 'x-transaction')) {
                            options.transaction = {
                                id: headers['x-transaction'],
                                canUndo: _.has(headers, 'x-undo')
                            }
                        }

                        //Dispatch success message
                        this.$root.bus.$emit('success', 'User details updated successfully.', options)

                        //Clear saving flag
                        this.isSaving = false

                    }

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear saving flag
                    this.isSaving = false

                });
        },

        /**
         * Select a new image for upload.
         */
        selectImage: function (e)
        {
            this.image = e.target.files[0]
        },

        /**
         * Send an event to open the reset password modal.
         */
        openPasswordResetter: function () {
            this.$root.bus.$emit('passwordResetter.open');
        },

        /**
         * Send an event to open the account attacher.
         */
        openAccountAttacher: function () {
            this.$root.bus.$emit('accountAttacher.open');
        },

        /**
         * Set the default account for this user.
         */
        setDefaultAccount: function (accountId)
        {
            //Already saving
            if (this.isSettingDefaultAccount) return

            //Set saving flag
            this.isSettingDefaultAccount = accountId

            //Pack our payload
            var payload = {
                data: {
                    type: 'users',
                    id: this.id,
                    attributes: {
                        default_account: accountId,
                    }
                }
            };

            //Send request to API
            this.$http.patch('users/'+this.id, payload)
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Default account updated successfully.')

                    //Clear saving flag
                    this.isSettingDefaultAccount = false

                    //Update default account for user
                    this.user.default_account = response.data.data.attributes.default_account;

                }, function (response) {

                    //Dispatch error message
                    this.$root.bus.$emit('error', _.first(response.data.errors).detail)

                    //Clear saving flag
                    this.isSettingDefaultAccount = false

                });
        },

        /**
         * Detach the provided account from the current user.
         */
        detachAccount: function (accountId)
        {
            //Already saving
            if (this.isDetachingAccount) return

            //Set saving flag
            this.isDetachingAccount = accountId

            //Pack our payload
            var payload = {
                data: [
                    {
                        type: 'accounts',
                        id: accountId
                    }
                ]
            };

            //Send request to API
            this.$http.delete('users/'+this.id+'/relationships/accounts', payload)
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Account detached successfully.')

                    //Clear saving flag
                    this.isDetachingAccount = false

                    //Find account to be removed
                    var account = _.find(this.user.accounts, function (a) {
                        return a.id == accountId
                    })

                    //Remove account from collection
                    this.user.accounts = _.without(this.user.accounts, account)

                }, function (response) {

                    //Dispatch error message
                    this.$root.bus.$emit('error', _.first(response.data.errors).detail)

                    //Clear saving flag
                    this.isSettingDefaultAccount = false

                });
        },

        /**
         * Delete the user.
         */
        deleteUser: function ()
        {
            // Already deleting
            if (this.isDeleting) return

            // Show the delete password
            if (! this.showDeletePassword) {
                this.showDeletePassword = true
                Vue.nextTick(function () {
                    this.$refs.deletePasswordField.focus()
                }.bind(this))
                return
            }

            // No password provided
            if (! this.deletePassword) return

            // Set flag
            this.isDeleting = true

            // Send API request
            this.$http.delete('users/'+this.id, {
                body: {
                    password: this.deletePassword,
                    permanent: this.deletePermanent
                }
            })
                .then(function (response) {

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'User deleted')

                    // Redirect to listing
                    this.$router.push('/admin/users')
                    return

                }, function (response) {

                    // Password was incorrect
                    if (response.data.errors && response.data.errors[0].detail == 'Your password is incorrect.') {
                        this.$root.bus.$emit('error', 'Your password is incorrect, please try again')
                    }

                    // Dispatch error message
                    else {
                        this.$root.bus.$emit('error', 'User could not be deleted')
                    }

                    // Clear the flag
                    this.isDeleting = false

                })
        },

        /**
         * Cancel delete account process.
         */
        cancelDeleteUser: function ()
        {
            this.showDeletePassword = false
            this.deletePassword     = null
            this.deletePermanent    = false
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchUser()
        }

    }
})
