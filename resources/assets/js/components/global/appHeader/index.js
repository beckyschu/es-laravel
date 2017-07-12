var Vue    = require('vue')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'account-switcher': require('../accountSwitcher'),
    },
    props: ['auth'],
    data: function () {
        return {
            profileImageCacheBuster: moment().valueOf()
        }
    },
    computed: {

        /**
         * Return users full name.
         */
        name: function () {
            return this.auth.user.first_name+' '+this.auth.user.last_name
        },

        /**
         * Generate a profile image URL.
         */
        profileImageUrl: function ()
        {
            if (this.auth.user.image) {
                return this.auth.user.image+'?cache='+this.profileImageCacheBuster
            }

            return '/images/profile_anon.jpg'
        }

    },
    created: function ()
    {
        // Listen to events
        this.$root.bus.$on('me.imageUpdated', this.onImageUpdated)
    },
    beforeDestroy: function()
    {
        // Unlisten to events
        this.$root.bus.$off('me.imageUpdated', this.onImageUpdated)
    },
    methods: {

        /**
         * Logout the current user and redirect to login.
         */
        logout: function () {

            //Reset storage data
            this.auth.reset();

            //Dispatch success message
            this.$root.bus.$emit('success', 'You have been logged out, see you soon!');

            //Redirect to login
            this.$router.push('/login');

        },

        /**
         * Fire an event to clear discovery and seller filters.
         */
        clearFilters: function () {
            this.$root.bus.$emit('clearFilters')
        },

        /**
         * Image updated event listener.
         */
        onImageUpdated: function () {
            this.profileImageCacheBuster = moment().valueOf()
        }

    }
})
