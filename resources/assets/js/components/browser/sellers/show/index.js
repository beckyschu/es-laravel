var Vue    = require('vue')
var _      = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['scope'],
    components: {
        'status-badge':    require('../../../global/statusBadge'),
        'platform-ident':  require('../../../global/platformIdent'),
        'handle-assigner': require('./handleAssigner')
    },
    data: function () {
        return {
            id: this.$route.params.id,
            seller: null,
            request: null,
            sellerStatuses: require('../../../helpers/sellerStatuses'),
            sellerStatusColors: require('../../../helpers/sellerStatusColors'),
            sellerFlags: require('../../../helpers/sellerFlags'),
            sellerFlagColors: require('../../../helpers/sellerFlagColors'),
            visibleSections: ['history']
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Seller - '+this.id)

        // Listen to events
        this.$root.bus.$on('handle.reassigned', this.onHandleReassigned)
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch seller data
        this.fetchSeller()
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('handle.reassigned', this.onHandleReassigned)
        this.$root.bus.$off('data.reload', this.onDataReload)
    },
    methods: {

        /**
         * Fetch seller for current id.
         */
        fetchSeller: function () {

            //Clear seller
            this.seller = null

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
            this.$http.get('sellers/'+this.id, {}, options)
                .then(function (response) {

                    //Grab seller
                    var seller = response.data.data;

                    //Push seller data
                    this.seller = _.extend(
                        {id: seller.id},
                        seller.attributes
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
         * Filter the discovery listing for the current seller.
         */
        filterForSeller: function () {
            this.scope.reset();
            this.scope.filters.seller = [this.id];
            this.$router.push('/browser/discoveries');
        },

        /**
         * Filter the discovery listing for the current seller.
         */
        filterForHandle: function (handleId) {
            this.scope.reset();
            this.scope.filters.handle = [handleId];
            this.$router.push('/browser/discoveries');
        },

        /**
         * Generate a short ID for the given UID.
         */
        shortId: function (uid) {
            return uid.substring(0, uid.indexOf('-'))
        },

        /**
         * Toggle the given section.
         */
        toggle: function (section)
        {
            if (this.sectionIsVisible(section)) {
                this.visibleSections = _.without(this.visibleSections, section)
            } else {
                this.visibleSections.push(section);
            }
        },

        /**
         * Returns whether or not the given section should be visible.
         */
        sectionIsVisible: function (section) {
            return _.contains(this.visibleSections, section);
        },

        /**
         * Handle reassigned event handler.
         */
        onHandleReassigned: function (handle) {

            //First, lets see if we already have it in the collection
            if (_.find(this.seller.handles, function (h) {
                return h.id == handle.id;
            })) return;

            //Append to collection
            this.seller.handles.push(handle);

        },

        /**
         * Data reload event handler.
         */
        onDataReload: function () {
            this.fetchSeller()
        }

    }
})
