var Vue       = require('vue')
var _         = require('underscore')
var jwtDecode = require('jwt-decode')

module.exports = {

    /**
     * Current JWT token.
     *
     * @type string
     */
    token: null,

    /**
     * Current user data.
     *
     * @type Object
     */
    user: null,

    /**
     * Current account data.
     *
     * @type Object
     */
    account: null,

    /**
     * Collection of accounts attached to this user.
     *
     * @type Array
     */
    accounts: [],

    /**
     * Array of permissions as allowed by the JWT token.
     *
     * @type Array
     */
    permissions: [],

    /**
     * Return whether or not the current user is authenticated.
     *
     * @return bool
     */
    isAuthenticated: function () {
        return this.user !== null
    },

    /**
     * Return whether or not the current user can switch accounts.
     *
     * @return bool
     */
    canSwitchAccount: function () {
        return 1 < this.accounts.length
    },

    /**
     * Switch account to the given ID.
     */
    switchAccount: function (id)
    {
        //Search for the given account
        var account = _.find(this.accounts, function (a) {
            return a.id == id;
        })

        //Could not find given account
        if (! account) return;

        //Switch account
        this.account = account;

        //Save to storage
        localStorage.setItem('auth.account', this.account.id)
    },

    /**
     * Return whether or not this session has an account selected.
     */
    hasAccount: function () {
        return this.account && this.account.id;
    },

    /**
     * Return full name for the current user.
     *
     * @return string
     */
    getName: function () {
        return this.user.first_name+' '+this.user.last_name
    },

    /**
     * Reset storage to logged out state.
     */
    reset: function () {

        //Clear local object
        this.token       = null
        this.user        = null
        this.account     = null
        this.accounts    = []
        this.permissions = []

        //Clear local storage
        localStorage.removeItem('auth.token')

    },

    /**
     * Decode the stored JWT token and return object.
     *
     * @return Object
     */
    decodeToken: function ()
    {
        if (! this.token) {
            return null;
        }

        return jwtDecode(this.token)
    },

    /**
     * Return whether or not the current user can perform the permission.
     *
     * @return Boolean
     */
    can: function (permission)
    {
        return _.contains(this.permissions, permission)
    },

    /**
     * Login user with provided email and password.
     *
     * @param  string email
     * @param  string password
     */
    login: function (email, password) {
        return new Promise(function (resolve, reject) {

            //Always reset before a login
            this.reset();

            //Build payload
            var payload = {
                email: email,
                password: password
            }

            Vue.http.post('auth', payload)
                .then(function (response) {

                    //Populate token
                    this.token = response.data.meta.token

                    //Save token to local storage
                    localStorage.setItem('auth.token', this.token)

                    //Decode the token and store permissions
                    this.permissions = this.decodeToken().per

                    //Grab user
                    var user = response.data.data

                    //Populate user
                    this.user = _.extend(
                        {id: user.id},
                        user.attributes
                    )

                    //Fetch accessible accounts
                    _.each(user.relationships.accessible_accounts.data, function (account) {

                        var entity = _.find(response.data.included, function (include) {
                            return 'accounts' == include.type && account.id == include.id;
                        })

                        this.accounts.push(_.extend(
                            {id: entity.id},
                            entity.attributes
                        ))

                    }.bind(this))

                    //We have a selected account
                    var accountId = localStorage.getItem('auth.account');
                    if (accountId) {
                        this.account = _.find(this.accounts, function (account) {
                            return account.id == accountId
                        })
                    }

                    //We still don't have an account, populate first account for active
                    if (! this.account) {
                        this.account = _.first(this.accounts)
                    }

                    //Save selected account to local storage
                    localStorage.setItem('auth.account', this.account.id)

                    //Resolve promise
                    resolve(this)

                }.bind(this), function (response) {

                    //Init blank error
                    var error = null

                    //Grab the first error from the response
                    if (response.data.errors) {
                        error = _.first(response.data.errors).detail
                    }

                    //Reject promise
                    reject(error)

                })

        }.bind(this))
    },

    /**
     * Fetch token from storage and re-populate user.
     */
    fetchFromStorage: function () {
        return new Promise(function (resolve, reject) {

            //Grab token from storage
            var token = localStorage.getItem('auth.token')

            //No token stored, bail out
            if (! token) {
                return reject('No token stored')
            }

            //Set token
            this.token = token;

            //Decode the token and store permissions
            this.permissions = this.decodeToken().per

            //Repopulate user data
            Vue.http.get('me').then(function (response) {

                //Grab user
                var user = response.data.data

                //Populate user
                this.user = _.extend(
                    {id: user.id},
                    user.attributes
                )

                //If user has admin access, allow access to global account
                if ('admin' == this.user.role) {
                    this.accounts.push({
                        id: null,
                        name: 'IP Shark (Global)'
                    })
                }

                //Fetch accessible accounts
                _.each(user.relationships.accessible_accounts.data, function (account) {

                    var entity = _.find(response.data.included, function (include) {
                        return 'accounts' == include.type && account.id == include.id;
                    })

                    this.accounts.push(_.extend(
                        {id: entity.id},
                        entity.attributes
                    ))

                }.bind(this))

                //We have a selected account
                var accountId = localStorage.getItem('auth.account')
                if (accountId) {
                    this.account = _.find(this.accounts, function (account) {
                        return account.id == accountId
                    })
                }

                //We still don't have an account, populate first account for active
                if (! this.account) {
                    this.account = _.first(this.accounts)
                }

                //Resolve promise
                return resolve(this)

            }.bind(this), function (response) {

                //Reject promise
                return reject()

            });

        }.bind(this))
    }

}
