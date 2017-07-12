var Vue = require('vue')
var _   = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    props: ['auth'],
    template: require('./template.html'),
    components: {
        'account-creator': require('../accountCreator'),
        'status-badge':    require('../../../global/statusBadge')
    },
    data: function () {
        return {
            isLoading: false,
            accounts: [],
            statuses: require('../../../helpers/accountStatuses'),
            statusColors: require('../../../helpers/accountStatusColors')
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Accounts');

        // Listen to reload event
        this.$root.bus.$on('data.reload', this.onDataReload);

        // Fetch initial data
        this.fetchAccounts()
    },
    beforeDestroy: function ()
    {
        // Unlisten to reload event
        this.$root.bus.$off('data.reload', this.onDataReload);
    },
    methods: {

        /**
         * Fetch accounts from API.
         */
        fetchAccounts: function ()
        {
            //Set loading flag
            this.isLoading = true

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
         * Send an event to open the create modal.
         */
        openAccountCreator: function () {
            this.$root.bus.$emit('accountCreator.open');
        },

        /**
         * Data reload listener.
         */
        onDataReload: function () {
            this.fetchAccounts()
        },

        /**
         * Generate a "from now" timestamp.
         */
        fromNow: function (date) {
            return moment(date).fromNow();
        },

        /**
         * Get formatted dates.
         */
        formatDate: function (date) {
            return moment(date).format('MM/DD/YY h:mma');
        }

    }
})
