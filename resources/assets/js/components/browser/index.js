var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    name: 'Browser',
    template: require('./template.html'),
    props: ['auth'],
    components: {
        'discoveries-subheader': require('./subheader')
    },
    data: function () {
        return {

            // Set listing mode
            mode: null,

            // Listing update timeout for watcher
            listingUpdateTimeout: null,

            // Filter scope values
            scope: {
                icons: {
                    asset: 'tag',
                    platform: 'shopping-bag',
                    category: 'bookmark',
                    status: 'check-circle-o',
                    seller: 'user-secret',
                    price: 'usd',
                    origin: 'globe',
                    query: 'search',
                },
                filterOptions: {
                    asset: [],    // Pulled from search
                    category: [], // Pulled from search
                    seller: [],   // Pulled from search
                    origin: [],   // Pulled from search
                    query: [],    // User defined tags
                    platform: [
                        {id: 'alibaba', label: 'Alibaba'},
                        {id: 'aliexpress', label: 'Aliexpress'},
                        {id: 'dhgate', label: 'DHgate'},
                        {id: 'ebay', label: 'Ebay'},
                        {id: 'wish', label: 'Wish'},
                        {id: 'lelong', label: 'Lelong'},
                        {id: 'taobao', label: 'Taobao'},
                        {id: 'made_in_china', label: 'Made-in-China'},
                        {id: 'bukalapak', label: 'Bukalapak'},
                        {id: 'amazon', label: 'Amazon'},
                        {id: '1688', label: '1688'},
                        {id: 'lazada', label: 'Lazada'},
                        {id: 'everychina', label: 'Everychina'},
                        {id: 'dyitrade', label: 'DIY Trade'},
                        {id: 'fasttech', label: 'FastTech'},
                        {id: 'instagram', label: 'Instagram'},
                        {id: 'website', label: 'Website'}
                    ],
                    status: [
                        {id: 'discovered', label: 'Discovered'},
                        {id: 'enforce', label: 'Enforce'},
                        {id: 'pending', label: 'Pending'},
                        {id: 'closed', label: 'Closed'},
                        {id: 'authorized', label: 'Authorized'},
                        {id: 'flagged', label: 'Flagged'},
                        {id: 'price', label: 'Price flag'},
                        {id: 'resubmit', label: 'Resubmit'},
                        {id: 'rejected', label: 'Rejected'},
                        {id: 'inspect', label: 'Inspect'},
                        {id: 'regressed', label: 'Regressed'},
                        {id: 'consumer', label: 'Consumer'}
                    ]
                },
                filters: {
                    asset:    [],
                    platform: [],
                    category: [],
                    status:   [],
                    seller:   [],
                    origin:   [],
                    query:    []
                },
                ranges: {
                    price: {
                        from: null,
                        to: null
                    }
                },

                reset: function () {
                    this.filters = {
                        asset:    [],
                        platform: [],
                        category: [],
                        status:   [],
                        seller:   [],
                        origin:   [],
                        query:    []
                    }
                    this.ranges = {
                        price: {
                            from: null,
                            to: null
                        }
                    }
                },

                buildPayload: function ()
                {
                    // Init payload
                    let payload = {}

                    // Append filters
                    _.each(this.filters, function (value, key)
                    {
                        // No value assigned
                        if (! value || ! value.length) return

                        // Fetch value ID or raw string
                        let values = _.map(value, function (v)
                        {
                            // Grab the value
                            v = (_.isObject(v)) ? v.id : v

                            // Enclose in quotes if contains comma
                            if (_.isString(v) && v.includes(',')) v = '"' + v + '"'

                            return v
                        })

                        // Append to payload
                        payload['filter['+key+']'] = values.join(',');
                    })

                    // Append ranges
                    _.each(this.ranges, function (value, key) {
                        if (value.from) payload['filter['+key+'_from]'] = value.from;
                        if (value.to)   payload['filter['+key+'_to]']   = value.to;
                    })

                    // Return payload
                    return payload
                }
            }

        }
    },
    created: function ()
    {
        // Update scope from query
        this.updateScopeFromQuery()

        // Watch for filter changes
        this.$watch(function () {
            return JSON.stringify(_.extend(
                {},
                this.scope.filters,
                this.scope.ranges
            ))
        }, function (newVal, oldVal)
        {
            // Cancel existing timeout
            if (this.listingUpdateTimeout) {
                window.clearTimeout(this.listingUpdateTimeout);
                this.listingUpdateTimeout = null;
            }

            // Update listing after 1 second
            this.listingUpdateTimeout = window.setTimeout(function () {
                this.$root.bus.$emit('data.reload')
            }.bind(this), 1000);

            // Sync query params
            this.syncQueryWithScope()
        })
    },
    methods: {

        /**
         * Update scope from query params.
         */
        updateScopeFromQuery: function ()
        {
            // No hash defined
            if (! this.$route.hash) return

            // Decode filter params
            let filters = JSON.parse(this.$route.hash.substring(1))

            // Loop filter parameters
            _.each(filters, function (value, key) {

                // This matches a filter
                if (_.has(this.scope.filters, key)) {
                    this.scope.filters[key] = value
                }

                // This matches a range
                if (_.has(this.scope.ranges, key)) {
                    if (value.from) this.scope.ranges[key].from = value.from
                    if (value.to)   this.scope.ranges[key].to   = value.to
                }

            }.bind(this))
        },

        /**
         * Sync query params with current scope.
         */
        syncQueryWithScope: function ()
        {
            var query = {}

            //Loop filters
            _.each(this.scope.filters, function (values, key) {
                if (values && values.length) {
                    query[key] = values
                }
            })

            //Loop ranges
            _.each(this.scope.ranges, function (value, key)
            {
                let obj = {}

                if (value.from) obj.from = value.from
                if (value.to)   obj.to   = value.to

                if (! _.isEmpty(obj)) query[key] = obj
            })

            this.$router.push({
                path: this.$route.path,
                hash: _.isEmpty(query) ? null : JSON.stringify(query)
            })
        }

    }
})
