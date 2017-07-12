var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'user-attacher': require('../userAttacher'),
        'status-badge':  require('../../../global/statusBadge'),
        'asset-creator': require('../../assets/assetCreator'),
    },
    data: function () {
        return {
            id: this.$route.params.id,
            account: null,
            basicStatuses: require('../../../helpers/basicStatuses'),
            basicStatusColors: require('../../../helpers/basicStatusColors'),
            statuses: require('../../../helpers/accountStatuses'),
            statusColors: require('../../../helpers/accountStatusColors'),
            visibleSections: ['details'],
            isSavingDetails: false,
            isSavingAddress: false,
            isSettingPrimaryUser: false,
            isDetachingUser: false,
            detailErrors: [],
            addressErrors: [],

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
        this.$root.bus.$emit('subheader.updateTitle', 'Accounts - ' + this.id)

        // Listen to data reload event
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch data
        this.fetchAccount()
    },
    beforeDestroy: function ()
    {
        /// Unlisten to data reload event
        this.$root.bus.$off('data.reload', this.onDataReload)
    },
    methods: {

        /**
         * Fetch account for current id.
         */
        fetchAccount: function () {

            //Clear account
            this.account = null

            //Make API request
            this.$http.get('accounts/'+this.id)
                .then(function (response) {

                    //Grab account
                    var account = response.data.data

                    //Fetch assets
                    var assets = []
                    _.each(account.relationships.assets.data, function (asset) {

                        var entity = _.find(response.data.included, function (include) {
                            return 'assets' == include.type && asset.id == include.id;
                        })

                        assets.push(_.extend(
                            {id: entity.id},
                            entity.attributes
                        ))

                    })

                    //Fetch users
                    var users = []
                    _.each(account.relationships.users.data, function (user) {

                        var entity = _.find(response.data.included, function (include) {
                            return 'users' == include.type && user.id == include.id;
                        })

                        users.push(_.extend(
                            {id: entity.id},
                            entity.attributes
                        ))

                    })

                    //Push discovery data
                    this.account = _.extend(
                        {
                            id: account.id,
                            assets: assets,
                            users: users
                        },
                        account.attributes
                    )

                    // Update subheader title
                    this.$root.bus.$emit('subheader.updateTitle', 'Accounts - '+this.account.name)

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
                this.visibleSections.push(section)
            }
        },

        /**
         * Returns whether or not the given section should be visible.
         */
        sectionIsVisible: function (section) {
            return _.contains(this.visibleSections, section);
        },

        /**
         * Update the details for this account.
         */
        updateDetails: function ()
        {
            //Already saving
            if (this.isSavingDetails) return

            //Set saving flag
            this.isSavingDetails = true

            //Clear errors
            this.detailErrors = []

            //Pack our payload
            var payload = {
                data: {
                    type: 'accounts',
                    id: this.id,
                    attributes: {
                        name: this.account.name,
                        status: this.account.status
                    }
                }
            };

            //Send request to API
            this.$http.patch('accounts/'+this.id, payload)
                .then(function (response) {

                    //Capture transaction if provided
                    var options = {}
                    var headers = response.headers;
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Account details updated successfully.', options)

                    //Clear saving flag
                    this.isSavingDetails = false

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.detailErrors.push(error.detail)
                    }.bind(this));

                    //Clear saving flag
                    this.isSavingDetails = false

                });
        },

        /**
         * Update the address for this account.
         */
        updateAddress: function ()
        {
            //Already saving
            if (this.isSavingAddress) return

            //Set saving flag
            this.isSavingAddress = true

            //Clear errors
            this.addressErrors = []

            //Pack our payload
            var payload = {
                data: {
                    type: 'accounts',
                    id: this.id,
                    attributes: {
                        address_line1:   this.account.address_line1,
                        address_line2:   this.account.address_line2,
                        address_city:    this.account.address_city,
                        address_state:   this.account.address_state,
                        address_zip:     this.account.address_zip,
                        address_country: this.account.address_country,
                    }
                }
            };

            //Send request to API
            this.$http.patch('accounts/'+this.id, payload)
                .then(function (response) {

                    //Capture transaction if provided
                    var options = {}
                    var headers = response.headers;
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Account address updated successfully.', options)

                    //Clear saving flag
                    this.isSavingAddress = false

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.addressErrors.push(error.detail)
                    }.bind(this));

                    //Clear saving flag
                    this.isSavingAddress = false

                });
        },

        /**
         * Delete the account.
         */
        deleteAccount: function ()
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
            this.$http.delete('accounts/'+this.id, {
                body: {
                    password:  this.deletePassword,
                    permanent: this.deletePermanent
                }
            })
                .then(function (response) {

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Account deleted')

                    // Redirect to listing
                    this.$router.push('/admin/accounts')
                    return

                }, function (response) {

                    // Password was incorrect
                    if (response.data.errors && response.data.errors[0].detail == 'Your password is incorrect.') {
                        this.$root.bus.$emit('error', 'Your password is incorrect, please try again')
                    }

                    // Dispatch error message
                    else {
                        this.$root.bus.$emit('error', 'Account could not be deleted')
                    }

                    // Clear the flag
                    this.isDeleting = false

                })
        },

        /**
         * Cancel delete account process.
         */
        cancelDeleteAccount: function ()
        {
            this.showDeletePassword = false
            this.deletePassword     = null
            this.deletePermanent    = false
        },

        /**
         * Open the asset creator.
         */
        openAssetCreator: function () {
            this.$root.bus.$emit('assetCreator.open');
        },

        /**
         * Send an event to open the user attacher.
         */
        openUserAttacher: function () {
            this.$root.bus.$emit('userAttacher.open');
        },

        /**
         * Set the primary user for this account.
         */
        setPrimaryUser: function (userId)
        {
            //Already saving
            if (this.isSettingPrimaryUser) return

            //Set saving flag
            this.isSettingPrimaryUser = userId

            //Pack our payload
            var payload = {
                data: {
                    type: 'accounts',
                    id: this.id,
                    attributes: {
                        primary_user: userId,
                    }
                }
            };

            //Send request to API
            this.$http.patch('accounts/'+this.id, payload)
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Primary user updated successfully.')

                    //Clear saving flag
                    this.isSettingPrimaryUser = false

                    //Update primary user for account
                    this.account.primary_user = response.data.data.attributes.primary_user;

                }, function (response) {

                    //Dispatch error message
                    this.$root.bus.$emit('error', _.first(response.data.errors).detail)

                    //Clear saving flag
                    this.isSettingPrimaryUser = false

                });
        },

        /**
         * Detach the provided user from the current account.
         */
        detachUser: function (userId)
        {
            //Already saving
            if (this.isDetachingUser) return

            //Set saving flag
            this.isDetachingUser = userId

            //Pack our payload
            var payload = {
                data: [
                    {
                        type: 'users',
                        id: userId
                    }
                ]
            };

            //Send request to API
            this.$http.delete('accounts/'+this.id+'/relationships/users', {
                body: payload
            })
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'User detached successfully.')

                    //Clear saving flag
                    this.isDetachingUser = false

                    //Find user to be removed
                    var user = _.find(this.account.users, function (u) {
                        return u.id == userId
                    })

                    //Remove user from collection
                    this.account.users = _.without(this.account.users, user)

                }, function (response) {

                    //Dispatch error message
                    this.$root.bus.$emit('error', _.first(response.data.errors).detail)

                    //Clear saving flag
                    this.isDetachingUser = false

                });
        },

        /**
         * Data reload listener.
         */
        onDataReload: function () {
            this.fetchAccount()
        }

    }
})
