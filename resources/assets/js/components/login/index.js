var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'messenger': require('../global/messenger')
    },
    props: ['auth'],
    data: function () {
        return {
            loading: false,
            email: null,
            password: null
        }
    },
    created: function () {

        //Reset form input
        this.email    = null
        this.password = null

    },
    methods: {

        /**
         * Attempt login with the given credentials.
         */
        login: function () {

            //Already loading, bail out
            if (this.loading) return

            //Set loading flag
            this.loading = true

            //Attempt login
            this.auth.login(this.email, this.password).then(function (auth) {

                //Clear loading flag
                this.loading = false;

                //Redirect to dashboard
                this.$router.push('/')

            }.bind(this), function (error) {

                //Clear loading flag
                this.loading = false;

                //Dispatch error event
                this.$root.bus.$emit('error', error);

            }.bind(this));

        }

    }
})
