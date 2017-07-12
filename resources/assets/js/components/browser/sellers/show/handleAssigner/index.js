var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'platform-ident': require('../../../../global/platformIdent'),
    },
    props: {
        seller: {type: String, required: true}
    },
    data: function () {
        return {
            query: null,
            isSearching: false,
            isReassigning: false,
            selected_handle: null, //The handle selected for reassignment
            handles: []
        }
    },
    methods: {

        /**
         * Search handles directory for given query and show results.
         */
        search: function () {

            //Already searching, bail out
            if (this.isSearching) return;

            //No query provided
            if (! this.query) return;

            //Clear handles
            this.handles = []

            //Set searching flag
            this.isSearching = true;

            //Construct payload
            var payload = {
                'filter[username]': this.query
            }

            //Send request to API
            this.$http.get('handles', payload)
                .then(function (response) {

                    //No results found
                    if (! response.data.data.length) {

                        //Send an error
                        this.$root.bus.$emit('error', 'No handles were found with the username "'+this.query+'".')

                        //Clear searching flag
                        this.isSearching = false;

                        return
                    }

                    //Fetch handles from data
                    _.each(response.data.data, function (handle) {

                        //Fetch seller
                        var seller_id = handle.relationships.seller.data.id;
                        var seller = _.find(response.data.included, function (include) {
                            return 'sellers' == include.type && seller_id == include.id;
                        });

                        //Push handle data on to stack
                        this.handles.push(_.extend(
                            {
                                id: handle.id,
                                seller: _.extend({id: seller.id}, seller.attributes),
                            },
                            handle.attributes
                        ))

                    }.bind(this))

                    //Clear searching flag
                    this.isSearching = false;

                }, function (response) {

                    //Clear searching flag
                    this.isSearching = false;

                    //Dispatch generic error
                    this.$root.bus.$emit('error');

                });

        },

        /**
         * Reassign the given handle with the propped seller.
         */
        reassign: function (handle) {

            //Set selection and flag
            this.selected_handle = handle
            this.isReassigning = true

            //Get handle entity from results
            var entity = _.find(this.handles, function (h) {
                return h.id == handle;
            });

            //Build payload
            var payload = {
                data: {
                    type: 'seller',
                    id: this.seller
                }
            }

            //Update relationship
            this.$http.patch('handles/'+handle+'/relationships/seller', payload)
                .then(function (response) {

                    //If we have includes, ping status update events for them
                    //Dan: includes are usually attached when updating this
                    //status resulted in some other status being updated
                    if (response.data.included) {
                        _.each(response.data.included, function (include) {
                            if (include.attributes && include.attributes.status) {
                                this.$root.bus.$emit('status.update', include.type, include.id, include.attributes.status);
                            }
                        }.bind(this));
                    }

                    //Dispatch event for listing update
                    this.$root.bus.$emit('handle.reassigned', entity);

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Handle successfully reassigned.');

                    //Reset state
                    this.reset();

                }, function (response) {

                    //Dispatch generic error
                    this.$root.bus.$emit('error');

                });

        },

        /**
         * Generate a short ID for the given UID.
         */
        shortId: function (uid) {
            return uid.substring(0, uid.indexOf('-'))
        },

        /**
         * Reset the assigner state.
         */
        reset: function () {
            this.query = null
            this.handles = []
            this.selected_handle = null
        }

    }
})
