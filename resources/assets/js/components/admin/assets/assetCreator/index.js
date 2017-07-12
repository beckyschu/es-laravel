var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['account'],
    components: {
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            asset: {
                account: this.account,
                name: null,
                description: null,
                keywords: [],
                counter_keywords: [],
                status: 'active'
            },
            modal: null,
            isCreating: false,
            errors: [],
            assetStatuses: require('../../../helpers/basicStatuses'),
            accounts: []
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=asset_creator]').remodal({
            hashTracking: false
        });

        // Register filter selector open event
        this.$root.bus.$on('assetCreator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy();

        // Unlisten to open event
        this.$root.bus.$off('assetCreator.open', this.onOpen)

    },
    methods: {

        /**
         * Open modal.
         */
        openModal: function ()
        {
            //Fetch data if we need it
            if (! this.account && ! this.accounts.length) {
                this.fetchAccounts()
            }

            //Open the modal
            this.modal.open()
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Reset data for this creator.
         */
        reset: function () {
            this.asset = {
                account: this.account,
                name: null,
                description: null,
                keywords: [],
                counter_keywords: [],
                status: null
            }
        },

        /**
         * Fetch accounts for dropdown.
         */
        fetchAccounts: function ()
        {
            //Clear accounts
            this.accounts = []

            //Make API request
            this.$http.get('accounts')
                .then(function (response) {

                    //Loop through accounts
                    _.each(response.data.data, function (account) {

                        //Push account data on to stack
                        this.accounts.push(_.extend(
                            {id: account.id},
                            account.attributes
                        ));

                    }.bind(this))

                }.bind(this), function (response) {

                    //Close the modal
                    this.closeModal()

                    //Send generic error
                    this.$root.bus.$emit('error', 'We could not fetch the accounts list required to create an asset. Please try again later.')

                })
        },

        /**
         * Create the asset.
         */
        create: function ()
        {
            //Already creating, bail out
            if (this.isCreating) return;

            //Clear errors
            this.errors = []

            //Set flag
            this.isCreating = true

            //Pack our payload
            var payload = {
                data: {
                    type: 'assets',
                    attributes: {
                        name: this.asset.name,
                        description: this.asset.description,
                        keywords: this.asset.keywords,
                        counter_keywords: this.asset.counter_keywords,
                        status: this.asset.status
                    },
                    relationships: {
                        account: {
                            data: {
                                type: 'accounts',
                                id: this.asset.account
                            }
                        }
                    }
                }
            }

            //Send request to API
            this.$http.post('assets', payload)
                .then(function (response) {

                    //Close modal
                    this.closeModal()

                    //Reset data
                    this.reset()

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Asset created successfully.')

                    //Clear flag
                    this.isCreating = false

                    //Refresh data
                    this.$root.bus.$emit('data.reload')

                }, function (response) {

                    //Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    //Clear flag
                    this.isCreating = false

                });
        },

        /**
         * Open event listener.
         */
        onOpen: function () {
            this.openModal()
        },

        /**
         * Add keyword tag event.
         */
        addKeywordTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.asset.keywords, newTag)) return

            // Push tag into asset data
            this.asset.keywords.push(newTag)
        },

        /**
         * Add counter keyword tag event.
         */
        addCounterKeywordTag: function (newTag) {

            // Tag has already been added
            if (_.contains(this.asset.counter_keywords, newTag)) return

            // Push tag into asset data
            this.asset.counter_keywords.push(newTag)
        }

    }
})
