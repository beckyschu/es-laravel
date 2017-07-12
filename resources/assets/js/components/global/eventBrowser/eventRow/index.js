var Vue    = require('vue')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['transaction', 'showUser'],
    components: {
        'status-badge': require('../../../global/statusBadge')
    },
    data: function () {
        return {
            discoveryStatuses: require('../../../helpers/discoveryStatuses'),
            discoveryStatusColors: require('../../../helpers/discoveryStatusColors'),
            isUndoing: false
        }
    },
    computed:
    {
        /**
         * Get date in a "from now" style.
         */
        fromNow: function () {
            return moment(this.transaction.created_at).fromNow()
        },

        /**
         * Get created as a formatted date.
         */
        formattedDate: function () {
            return moment(this.transaction.created_at).format('D MMM YYYY h:mma')
        }
    },
    methods:
    {
        /**
         * Undo this event.
         */
        undo: function ()
        {
            //Already undoing, bail out
            if (this.isUndoing) return;

            //Wave the flag
            this.isUndoing = true

            //Call the API
            this.$http.post('transactions/'+this.transaction.id+'/undo')
                .then(function (response) {

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Phew, that was lucky. Your action has been undone.');

                    //Force data to reload
                    this.$root.bus.$emit('data.reload');

                    //Tear down that flag
                    this.isUndoing = false

                }, function (response) {

                    //Dispatch the terrible news
                    that.$root.bus.$emit('error', 'An error occured when trying to undo. Sorry about that.')

                    //Tear down that flag
                    this.isUndoing = false

                });
        }
    }
})
