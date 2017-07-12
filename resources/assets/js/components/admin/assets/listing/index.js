var Vue = require('vue')
var _   = require('underscore')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'asset-creator': require('../assetCreator'),
        'status-badge':  require('../../../global/statusBadge')
    },
    data: function () {
        return {
            isLoading: false,
            assets: [],
            statuses: require('../../../helpers/basicStatuses'),
            statusColors: require('../../../helpers/basicStatusColors')
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Assets');

        // Update listing when asset created
        this.$root.bus.$on('data.reload', this.onDataReload)

        // Fetch initial data
        this.fetchAssets()
    },
    beforeDestroy: function () {

        // Unlisten to reload event
        this.$root.bus.$off('data.reload', this.onDataReload)

    },
    methods: {

        /**
         * Fetch assets from API.
         */
        fetchAssets: function ()
        {
            //Set loading flag
            this.isLoading = true

            //Clear assets
            this.assets = []

            //Make API request
            this.$http.get('assets')
                .then(function (response) {

                    //Loop through assets
                    _.each(response.data.data, function (asset) {

                        //Fetch attached account
                        var account = _.find(response.data.included, function (include) {
                            return 'accounts' == include.type && asset.relationships.account.data.id == include.id;
                        })

                        //Push asset data on to stack
                        this.assets.push(_.extend(
                            {
                                id: asset.id,
                                account: _.extend(
                                    {id: account.id},
                                    account.attributes
                                )
                            },
                            asset.attributes
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
        openAssetCreator: function () {
            this.$root.bus.$emit('assetCreator.open');
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchAssets()
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
