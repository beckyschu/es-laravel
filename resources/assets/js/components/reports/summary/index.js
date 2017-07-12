var Vue    = require('vue')
var Chart  = require('chart.js')
var _      = require('underscore')
var moment = require('moment')
var auth   = require('../../../authStore')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'discoveries-timeline': require('../widgets/discoveriesTimeline'),
        'sellers-timeline':     require('../widgets/sellersTimeline'),
        'price-timeline':       require('../widgets/priceTimeline'),
        'top-sellers':          require('../widgets/topSellers'),
        'platform-bar':         require('../widgets/platformBar'),
        'location-pie':         require('../widgets/locationPie'),
    },
    data: function () {
        return {
            assets: [],
            asset: null,
            isGeneratingPdf: false
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Summary Report');

        // //Fetch reports whenever month is updated
        // this.$watch('month', function () {
        //     this.fetchReports()
        // })
        //
        // //Fetch reports whenever asset is updated
        // this.$watch('asset', function () {
        //     this.fetchReports()
        // })

        //Fill asset options
        // this.fillAssets()
    },
    methods: {

        /**
         * Fill the assets selector.
         */
        fillAssets: function ()
        {
            //Empty assets
            this.assets = []

            //Request assets from API
            this.$http.get('accounts/'+auth.account.id+'/assets')
                .then(function (response) {

                    //Loop through assets
                    _.each(response.data.data, function (asset) {

                        //Push asset data on to stack
                        this.assets.push(_.extend(
                            {id: asset.id},
                            asset.attributes
                        ));

                    }.bind(this))

                }.bind(this), function (response) {
                    this.$root.bus.$emit('error', 'Assets could not be loaded.');
                })
        },

        /**
         * Toggle the provided filter selector.
         */
        toggleFilter: function (filter)
        {
            if (filter == this.activeFilter) {
                this.activeFilter = null
                return
            }

            this.activeFilter = filter
        },

        /**
         * Set the current month.
         */
        setMonth: function (month)
        {
            this.month = month

            this.activeFilter = null
        },

        /**
         * Return whether or not the given month is selected.
         */
        isMonthActive: function (month)
        {
            return this.month == month;
        },

        /**
         * Set the current asset.
         */
        setAsset: function (asset)
        {
            this.asset = asset

            this.activeFilter = null
        },

        /**
         * Return whether or not the given asset is selected.
         */
        isAssetActive: function (asset)
        {
            return this.asset == asset;
        },

        /**
         * Generate and redirect to a PDF for the current dataset.
         */
        generatePdf: function ()
        {
            //Bail out if already generating
            if (this.isGeneratingPdf) return;

            //Set loading flag
            this.isGeneratingPdf = true

            //Build payload
            var payload = {
                'filter[month]': this.month.format('YYYY-MM')
            }

            //Append asset if selected
            if (this.asset) {
                payload['filter[asset]'] = this.asset.id;
            }

            //Make API request
            this.$http.get('reports/export', payload)
                .then(function (response) {

                    //Grab URL for generated PDF
                    var url = response.data.data

                    //Redirect to the PDF
                    window.location.href = url

                    //Clear loading flag
                    this.isGeneratingPdf = false

                }.bind(this), function (response) {

                    //Clear loading flag
                    this.isGeneratingPdf = false

                    //Dispatch generic error
                    this.$root.bus.$emit('error')

                })
        }
    }
})
