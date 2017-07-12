var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'user-creator': require('../userCreator'),
        'status-badge': require('../../../global/statusBadge')
    },
    data: function () {
        return {
            isLoading: false,
            users: [],
            statuses: require('../../../helpers/basicStatuses'),
            statusColors: require('../../../helpers/basicStatusColors'),
            searchQuery: null
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Users')

        // Listen to events
        this.$root.bus.$on('data.reload', this.onDataReload)
        this.$root.bus.$on('search', this.onSearch)

        //Fetch initial data
        this.fetchUsers()
    },
    beforeDestroy: function () {

        // Unlisten to events
        this.$root.bus.$off('data.reload', this.onDataReload)
        this.$root.bus.$off('search', this.onSearch)

    },
    methods: {

        /**
         * Fetch accounts from API.
         */
        fetchUsers: function ()
        {
            //Set loading flag
            this.isLoading = true

            //Clear users
            this.users = []

            //Build payload
            var payload = {}
            if (this.searchQuery) payload['filter[search]'] = this.searchQuery

            //Make API request
            this.$http.get('users', payload)
                .then(function (response) {

                    //Loop through users
                    _.each(response.data.data, function (user) {

                        //Push user data on to stack
                        this.users.push(_.extend(
                            {id: user.id},
                            user.attributes
                        ));

                    }.bind(this))

                    //Clear loading flag
                    this.isLoading = false

                }.bind(this), function (response) {

                    //Clear loading flag
                    this.isLoading = false

                    //Send generic error
                    this.$root.bus.$emit('error');

                })
        },

        /**
         * Return role with first letter capitalised.
         */
        formatRole: function (role) {
            return role.charAt(0).toUpperCase() + role.slice(1)
        },

        /**
         * Get given datetime in a "from now" style.
         */
        fromNow: function (datetime)
        {
            if (! datetime) {
                return 'Never'
            }

            return moment(datetime).fromNow()
        },

        /**
         * Send an event to open the create modal.
         */
        openUserCreator: function () {
            this.$root.bus.$emit('userCreator.open');
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchUsers()
        },

        /**
         * Search event listener.
         */
        onSearch: function (query) {
            this.searchQuery = query
            this.fetchUsers()
        }

    }
})
