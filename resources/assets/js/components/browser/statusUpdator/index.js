var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: {
        scope: {type: Object, required: true},
        selected: {type: Array, required: true},
        total: {required: Number, required: true},
        type: {type: String, default: 'discoveries'},
    },
    data: function () {
        return {
            isUpdating: false,
            modal: null,
            status: null,
            flags: require('../../helpers/sellerFlags')
        }
    },
    mounted: function () {

        // Instantiate modal
        this.modal = $('[data-remodal-id=status]').remodal({
            hashTracking: false
        })

        // Listen to events
        this.$root.bus.$on('statusUpdator.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to events
        this.$root.bus.$off('statusUpdator.open', this.onOpen)

    },
    methods: {

        /**
         * Send the update request.
         */
        update: function ()
        {
            //We have no status selected
            if (! this.status) return

            //We are already updating
            if (this.isUpdating) return

            //Set loading flag
            this.isUpdating = true

            // Build payload
            let payload = {}
            if ('discoveries' == this.type) {
                payload = {
                    status: this.status
                }
            } else {
                payload = {
                    flag: this.status
                }
            }

            // Build filter params
            let filterParams = {}
            if (this.selected.length) {
                filterParams = {
                    'filter[id]': this.selected.join(',')
                }
            } else {
                filterParams = this.scope.buildPayload()
            }

            //Send request to API
            this.$http.patch(this.type, payload, {
                params: filterParams
            })
                .then(function (response)
                {
                    //Clear updating flag
                    this.isUpdating = false

                    //Loop response data
                    _.each(response.data.data, function (entity) {

                        //Fire update event so that status badges update
                        this.$root.bus.$emit(
                            'status.update',
                            entity.type,
                            entity.id,
                            this.status,
                            ('discoveries' == this.type) ? 'status' : 'flag'
                        )

                    }.bind(this))

                    //Capture transaction if provided
                    var options = {}
                    var headers = response.headers
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    //Dispatch success message
                    if ('discoveries' == this.type) {
                        this.$root.bus.$emit('success', 'Discovery statuses updated successfully.', options)
                    } else {
                        this.$root.bus.$emit('success', 'Seller flags updated successfully.', options)
                    }

                    //Dispatch complete event
                    this.$root.bus.$emit('statusUpdator.complete', {
                        count:  this.selected.length ? this.selected.length : this.total,
                        status: this.status,
                        enforceCount: 'discoveries' == this.type ? response.data.meta.enforceCount : null
                    })

                    //Close the modal
                    this.closeModal()

                }.bind(this), function (response) {

                    //Clear updating flag
                    this.isUpdating = false

                    //Dispatch generic error
                    this.$root.bus.$emit('error')

                    //Close the modal
                    this.closeModal()

                }.bind(this));
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Open event listener.
         */
        onOpen: function (status) {
            this.status = status
            this.modal.open()
        }

    }
})
