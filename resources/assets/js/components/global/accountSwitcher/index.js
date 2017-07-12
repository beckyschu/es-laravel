var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth'],
    data: function () {
        return {
            switcherOpen: false
        }
    },
    methods: {

        /**
         * Toggle the account switcher.
         */
        toggleSwitcher: function () {
            this.switcherOpen = !this.switcherOpen;
        },

        /**
         * Switch to the selected account.
         */
        switchAccount: function (id, redirect)
        {
            //Set default for redirect
            if ('undefined' == typeof redirect) {
                var redirect = '/dashboard/summary'
            }

            //Switch auth account
            this.auth.switchAccount(id)

            //Reroute to dashboard
            if (redirect !== this.$route.path) {
                this.$router.go(redirect)
            }

            //Else reload dashboard data
            else {
                this.$root.bus.$emit('data.reload')
            }

            //Close switcher
            this.switcherOpen = false
        },

        /**
         * Return accounts to switch to.
         */
        getSwitchableAccounts: function ()
        {
            return _.filter(this.auth.accounts, function (account) {
                return account.id != this.auth.account.id;
            }.bind(this))
        }

    }
})
