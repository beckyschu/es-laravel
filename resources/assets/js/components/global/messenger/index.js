var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            state: 'closed',
            type: 'info',
            message: null,
            interval: null,
            timer: null,
            transaction: null,
            isUndoing: false
        }
    },
    created: function () {
        this.$root.bus.$on('message', this.onMessage)
        this.$root.bus.$on('error', this.onError)
        this.$root.bus.$on('success', this.onSuccess)
    },
    beforeDestroy: function () {
        this.$root.bus.$off('message', this.onMessage);
        this.$root.bus.$off('error', this.onError);
        this.$root.bus.$off('success', this.onSuccess);
    },
    computed: {
        icon: function () {
            if ('info' == this.type)    return 'info-circle';
            if ('success' == this.type) return 'check';
            if ('error' == this.type)   return 'exclamation-circle';
            return 'info-circle';
        },
        classStr: function () {
            let classStr = 'message-overlay message-overlay--' + this.type

            if ('open' == this.state) {
                classStr += ' message-overlay--open'
            }

            return classStr
        },
        iconClassStr: function () {
            return 'fa fa-' + this.icon
        }
    },
    methods: {

        /**
         * Close the message.
         */
        close: function ()
        {
            //Clear timer if set
            this.clearInterval();

            //Close the actual message
            this.state = 'closed';
        },

        /**
         * Open message with given options.
         */
        open: function (message, type, options)
        {
            //Clear existing interval if set
            this.clearInterval();

            //Set defaults
            if ('undefined' == typeof message) {
                message = 'Looks like something went wrong there. Please try again later.'
            }
            if ('undefined' == typeof options) {
                options = {}
            }

            //Set message and type
            this.message = message;
            this.type    = type;

            //Set transaction data
            this.transaction = null
            if (options.transaction) this.transaction = options.transaction

            //Set timer
            this.timer = 5
            if (options.event && options.event.can_undo) this.timer = 20
            if (options.timer) this.timer = options.timer

            //Open the dialog
            this.state = 'open'

            //Set the timer interval
            this.interval = window.setInterval(this.tick, 1000)
        },

        //Decrement the timer and close the message if at zero
        tick: function ()
        {
            //Decrement the timer int
            this.timer--;

            //This timer is at zero, close the message
            if (! this.timer) {
                this.close();
            }
        },

        /**
         * Clear the interval object if we have one set.
         */
        clearInterval: function ()
        {
            if (this.interval) {
                window.clearInterval(this.interval);
            }

            this.interval = null
            this.timer    = null
        },

        /**
         * Whether or not the attached event can be undone.
         */
        canUndo: function () {
            return this.transaction && this.transaction.canUndo;
        },

        /**
         * Undo the attached event.
         */
        undo: function ()
        {
            //This action cannot be undone, tough luck
            if (! this.canUndo()) return;

            //We are already trying to undo, leave us alone godammit
            if (this.isUndoing) return;

            //First, clear the interval so message doesn't close in our face
            this.clearInterval();

            //Wave the flag
            this.isUndoing = true

            //Yell at the API
            this.$http.post('transactions/'+this.transaction.id+'/undo')
                .then(function (response) {

                    //Dispatch a new message
                    this.open('Phew, that was lucky. Your action has been undone.', 'success');

                    //Force data to reload
                    this.$root.bus.$emit('data.reload');

                    //Tear down that flag
                    this.isUndoing = false

                }, function (response) {

                    //Dispatch the terrible news
                    this.$root.bus.$emit('error', 'An error occured when trying to undo. Sorry about that.')

                    //Tear down that flag
                    this.isUndoing = false

                });
        },

        /**
         * General message event handler.
         */
        onMessage: function (message, options) {
            this.open(message, 'info', options)
        },

        /**
         * Error message event handler.
         */
        onError: function (message, options) {
            this.open(message, 'error', options)
        },

        /**
         * Success message event handler.
         */
        onSuccess: function (message, options) {
            this.open(message, 'success', options)
        },
    }
})
