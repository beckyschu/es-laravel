var Vue    = require('vue')
var moment = require('moment')
var _      = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['seller', 'selected', 'scope'],
    components: {
        'status-badge':   require('../../../../global/statusBadge'),
        'platform-ident': require('../../../../global/platformIdent'),
    },
    data: function () {
        return {
            statuses: require('../../../../helpers/sellerStatuses'),
            statusColors: require('../../../../helpers/sellerStatusColors'),
            flags: require('../../../../helpers/sellerFlags'),
            flagColors: require('../../../../helpers/sellerFlagColors')
        }
    },
    computed: {

        /**
         * Get last seen in a "from now" style.
         */
        lastSeenFromNow: function () {
            return moment(this.seller.last_seen_at).fromNow();
        },

        /**
         * Get formatted last seen.
         */
        lastSeen: function () {
            return moment(this.seller.last_seen_at).format('MM/DD/YY h:mma');
        },

        /**
         * Return a padded ID for easier clicking.
         */
        paddedId: function () {
            return ('0000' + this.seller.id).substr(-4, 4)
        }

    },
    methods: {

        /**
         * Toggle current row.
         */
        toggle: function ()
        {
            this.$root.bus.$emit('rowToggled', this.seller.id)
        },

        /**
         * Filter discoveries listing to current seller.
         */
        filterDiscoveries: function ()
        {
            // Build filter object
            let filters = {
                seller: [{
                    id:    this.seller.id,
                    label: this.seller.name + ' (' + this.seller.platform + ')'
                }]
            }

            // Update scope
            this.scope.filters = filters

            // Redirect to discovery listing
            this.$router.push({
                path: '/browser/discoveries',
                hash: JSON.stringify(filters)
            })
        }

    }
})
