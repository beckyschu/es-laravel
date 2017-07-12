var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['user'],
    components: {
        'event-row': require('./eventRow'),
    },
    data: function () {
        return {
            initiated: false,
            loading: true,
            request: null, //Keep track of xhr so we can abort requests
            page: 1,
            hasMorePages: false,
            total: null,
            transactions: [],
            searchQuery: null
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'My Activity');

        // Listen to events
        this.$root.bus.$on('data.reload', this.onDataReload)
        this.$root.bus.$on('search', this.onSearch)

        // Initial data fetch
        this.fetchTransactions()
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('data.reload', this.onDataReload)
        this.$root.bus.$off('search', this.onSearch)
    },
    methods: {

        /**
         * Fetch transactions from the API.
         */
        fetchTransactions: function () {

            //Set loading flag
            this.loading = true

            //Clear transactions
            this.transactions = []

            //Build payload
            var payload = {
                page: this.page
            }

            // Append search query if set
            if (this.searchQuery) payload['filter[search]'] = this.searchQuery

            //Build request options
            var options = {
                beforeSend: function (request) {
                    if (this.request) {
                        this.request.cancel()
                    }

                    this.request = request;
                }
            }

            //Set API endpoint
            if (this.user) {
                var endpoint = 'users/'+this.user.id+'/events';
            } else {
                var endpoint = 'transactions';
            }

            //Make API request
            this.$http.get(endpoint, payload, options)
                .then(function (response) {

                    //Set data from meta
                    this.total = response.data.meta.total
                    this.page  = response.data.meta.page

                    //Set has more pages
                    this.hasMorePages = (null !== response.data.links.next)

                    //Loop through transactions
                    _.each(response.data.data, function (transaction) {

                        //Fetch user if found
                        var user = null
                        if (transaction.relationships.user && transaction.relationships.user.data)
                        {
                            var userId = transaction.relationships.user.data.id;
                            user = _.find(response.data.included, function (include) {
                                return 'users' == include.type && userId == include.id;
                            });
                        }

                        //Fetch account if found
                        var account = null
                        if (transaction.relationships.account && transaction.relationships.account.data)
                        {
                            var accountId = transaction.relationships.account.data.id;
                            account = _.find(response.data.included, function (include) {
                                return 'accounts' == include.type && accountId == include.id;
                            });
                        }

                        //Push transaction data on to stack
                        this.transactions.push(_.extend(
                            {
                                id: transaction.id,
                                user: user ? _.extend({
                                    id: user.id
                                }, user.attributes) : null,
                                account: account ? _.extend({
                                    id: account.id
                                }, account.attributes) : null
                            },
                            transaction.attributes
                        ));

                    }.bind(this))

                    //Clear loading flag
                    this.loading = false

                    //Set init flag
                    this.initiated = true

                    //Clear xhr
                    this.request = null

                }.bind(this), function (response) {

                    //Clear loading flag
                    this.loading = false

                    //Clear xhr
                    this.request = null

                    //Send generic error (if we actually sent the request)
                    if (0 < response.status) {
                        this.$root.bus.$emit('error');
                    }

                })

        },

        /**
         * Advance to next page.
         */
        nextPage: function () {

            //Increase page counter
            this.page++

            //Fetch new transactions
            this.fetchTransactions()

        },

        /**
         * Go back to previous page.
         */
        prevPage: function () {

            //Decrease page counter
            this.page--

            //Fetch new transactions
            this.fetchTransactions()

        },

        /**
         * Data reload event handler.
         */
        onDataReload: function () {
            this.fetchTransactions()
        },

        /**
         * Search event handler.
         */
        onSearch: function (query) {
            this.searchQuery = query
            this.fetchTransactions()
        }

    }
})
