var Vue    = require('vue')
var moment = require('moment')
var _      = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth', 'rowKey', 'discovery', 'selected', 'scope', 'show-images'],
    components: {
        'status-badge':   require('../../../../global/statusBadge'),
        'platform-ident': require('../../../../global/platformIdent'),
    },
    data: function () {
        return {
            statuses: require('../../../../helpers/discoveryStatuses'),
            statusColors: require('../../../../helpers/discoveryStatusColors'),
        }
    },
    computed: {

        /**
         * Return a list of writeable statuses for this user.
         */
        writeableStatuses: function ()
        {
            if (this.auth.can('discoveries:write')) {
                return this.statuses
            }

            if (this.auth.can('discoveries:write:limited')) {
                return _.pick(this.statuses, ['discovered', 'enforce', 'authorized', 'rejected', 'inspect'])
            }

            return {}
        },

        /**
         * Get last seen in a "from now" style.
         */
        lastSeenFromNow: function () {
            return moment(this.discovery.last_seen_at).fromNow();
        },

        /**
         * Get formatted last seen.
         */
        lastSeen: function () {
            return moment(this.discovery.last_seen_at).format('MM/DD/YY h:mma');
        },

        /**
         * Return a padded ID for easier clicking.
         */
        paddedId: function () {
            return ('0000' + this.discovery.id).substr(-4, 4)
        },

        /**
         * Return a truncated title if necessary.
         */
        title: function ()
        {
            var length = 80
            var title  = this.discovery.title

            if (! title || title.length < length) {
                return title
            }

            return title.substring(0, length) + '&hellip;';
        },

        /**
         * Return a truncated seller name if necessary.
         */
        seller: function ()
        {
            var length   = 20
            var username = this.discovery.seller.username

            if (! username || username.length < length) {
                return username
            }

            return username.substring(0, length) + '&hellip;';
        },

        /**
         * Return a truncated origin if necessary.
         */
        location: function ()
        {
            var length = 20
            var origin = this.discovery.origin

            if (! origin || origin.length < length) {
                return origin
            }

            return origin.substring(0, length) + '&hellip;';
        }

    },
    methods: {

        /**
         * Toggle current row.
         */
        toggle: function ()
        {
            this.$root.bus.$emit('rowToggled', this.discovery.id)
        },

        /**
         * Filter the listing to the current seller.
         */
        filterSeller: function ()
        {
            // Update seller scope
            this.scope.filters.seller = [{
                id:    this.discovery.seller.id,
                label: this.discovery.seller.username
            }]

            // Update scope from URI
            this.$root.bus.$emit('refreshFilterSelector')
        },

        /**
         * Return whether or not this status is eligible for writing.
         */
        statusWriteable: function ()
        {
            return _.has(this.writeableStatuses, this.discovery.status)
        }

    }
})
