var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth', 'scope'],
    components: {
        'status-badge':   require('../../../global/statusBadge'),
        'platform-ident': require('../../../global/platformIdent'),
    },
    data: function () {
        return {
            id: this.$route.params.id,
            discovery: null,
            request: null,
            discoveryStatuses: require('../../../helpers/discoveryStatuses'),
            discoveryStatusColors: require('../../../helpers/discoveryStatusColors'),
            sellerStatuses: require('../../../helpers/sellerStatuses'),
            sellerStatusColors: require('../../../helpers/sellerStatusColors'),
            assetStatuses: require('../../../helpers/basicStatuses')
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Discovery - '+this.id)

        // Listen to events
        this.$root.bus.$on('event.created', this.onEventCreated)
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch discovery data
        this.fetchDiscovery()
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('event.created', this.onEventCreated)
        this.$root.bus.$off('data.reload', this.onDataReload)
    },
    computed: {

        /**
         * Return a list of writeable statuses for this user.
         */
        writeableStatuses: function ()
        {
            if (this.auth.can('discoveries:write')) {
                return this.discoveryStatuses
            }

            if (this.auth.can('discoveries:write:limited')) {
                return _.pick(this.discoveryStatuses, ['discovered', 'enforce', 'authorized', 'rejected', 'inspect'])
            }

            return {}
        },

    },
    methods: {

        /**
         * Fetch discovery for current id.
         */
        fetchDiscovery: function () {

            //Clear discovery
            this.discovery = null

            //Build request options
            var options = {
                beforeSend: function (request) {
                    if (this.request) {
                        this.request.cancel()
                    }

                    this.request = request;
                }
            }

            //Make API request
            this.$http.get('discoveries/'+this.id, {}, options)
                .then(function (response) {

                    //Grab discovery
                    var discovery = response.data.data;

                    //Fetch asset
                    var asset_id = discovery.relationships.asset.data.id;
                    var asset = _.find(response.data.included, function (include) {
                        return 'assets' == include.type && asset_id == include.id;
                    });

                    //Fetch seller
                    var seller = null
                    if (discovery.relationships.seller.data) {
                        var seller_id = discovery.relationships.seller.data.id;
                        seller = _.find(response.data.included, function (include) {
                            return 'sellers' == include.type && seller_id == include.id;
                        });
                    }

                    //Fetch statuses
                    var statuses = []
                    _.each(discovery.relationships.statuses.data, function (status) {

                        var entity = _.find(response.data.included, function (include) {
                            return 'discoveryStatuses' == include.type && status.id == include.id;
                        })

                        statuses.push(_.extend(
                            {id: entity.id},
                            entity.attributes
                        ));

                    })

                    //Push discovery data
                    this.discovery = _.extend(
                        {
                            id: discovery.id,
                            asset: _.extend({id: asset.id}, asset.attributes),
                            seller: seller ? _.extend({id: seller.id}, seller.attributes) : null,
                            statuses: statuses
                        },
                        discovery.attributes
                    );

                    //Clear xhr
                    this.request = null

                }.bind(this), function (response) {

                    //Clear xhr
                    this.request = null

                    //Send generic error (if we actually sent the request)
                    if (0 < response.status) {
                        this.$root.bus.$emit('error');
                    }

                })

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
        },

        /**
         * Return a short ID from the given UID.
         */
        shortId: function (uid) {
            return uid.substring(0, uid.indexOf('-'))
        },

        /**
         * Filter the discovery listing for the current seller.
         */
        filterForSeller: function () {
            this.scope.reset();
            this.scope.filters.seller = [this.discovery.seller.id];
            this.$router.push('/browser/discoveries');
        },

        /**
         * Filter the discovery listing for the current seller.
         */
        filterForHandle: function () {
            this.scope.reset();
            this.scope.filters.handle = [this.discovery.handle.id];
            this.$router.push('/browser/discoveries');
        },

        /**
         * Return whether or not this status is eligible for writing.
         */
        statusWriteable: function ()
        {
            return _.has(this.writeableStatuses, this.discovery.status)
        },

        /**
         * Event created listener.
         */
        onEventCreated: function (type, id, event)
        {
            //Not for us, continue prop
            if ('discoveries' != type || id != this.id) return true;

            //Append to collection
            this.discovery.events.push(event)
        },

        /**
         * Data reloaded event listener.
         */
        onDataReload: function () {
            this.fetchDiscovery();
        }

    }
})
