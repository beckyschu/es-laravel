var Vue  = require('vue')
var tpl  = require('./modal.html')
var _    = require('underscore')
var auth = require('../../../authStore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: {
        states: {type: Object, required: true},
        colors: {type: Object},
        code: {required: true},
        inline: {type: Boolean},
        type: {type: String},
        id: {},
        field: {type: String, default: 'status'},
        info: {},
        fill: {type: Boolean}
    },
    data: function () {
        return {
            mode: 'badge',
            original_state: this.code ? this.code : 'null',
            state: this.code ? this.code : 'null',
            modal: null,
            saving: false
        }
    },
    created: function () {

        // Listen to events
        this.$root.bus.$on('status.update', this.onStatusUpdate)

    },
    beforeDestroy: function () {

        // Unlisten to events
        this.$root.bus.$off('status.update', this.onStatusUpdate)

    },
    watch: {

        /**
         * Watch for changes to the state value and run the onSelect handler.
         *
         * @type void
         */
        state: {
            immediate: true,
            handler: function (new_value, old_value) {

                //Don't do anything if we aren't in select mode, this was programmatic
                if ('select' != this.mode) return;

                //Run the onSelect handler
                this.onSelect(new_value, old_value);

            }
        }

    },
    computed: {

        /**
         * Return inline styles for the badge.
         */
        inlineStyles: function ()
        {
            let styles = {}

            if (this.colors && this.colors[this.state]) {
                styles['background-color'] = this.colors[this.state]
            }

            if (this.fill) {
                _.extend(styles, {
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    'line-height': '30px',
                    'border-radius': 0
                })
            }

            return styles
        }

    },
    methods: {

        /**
         * Return the label for the current state.
         *
         * @return string
         */
        getLabel: function ()
        {
            if (this.states[this.state]) {
                var label = this.states[this.state];
            } else {
                var label = this.state
            }

            if (this.info) {
                label += ' '+this.info
            }

            return label
        },

        /**
         * Return whether or not the component should be clickable.
         *
         * @return bool
         */
        isClickable: function () {
            return 'badge' == this.mode && this.inline && this.type && this.id && ! this.saving;
        },

        /**
         * Handler for badge clicks.
         *
         * @return void
         */
        onClick: function () {

            //Don't do anything if the component shouldn't be clickable
            if (! this.isClickable()) return;

            //Switch mode
            this.mode = 'select';

        },

        /**
         * Handler for state selections.
         *
         * @param  string new_value
         * @param  string old_value
         * @return void
         */
        onSelect: function (new_value, old_value) {

            //This change does not need confirming, auto confirm
            if (
                'handles' != this.type ||
                ('authorized' != new_value && 'authorized' != old_value)
            ) {
                return this.confirm();
            }

            //Switch mode
            this.mode = 'confirm';

            //Generate the modal template
            var modal = this.$interpolate(tpl);

            //Instantiate the confirm modal
            this.modal = $(modal).remodal({
                hashTracking: false,
                closeOnOutsideClick: false,
                closeOnEscape: false
            });

            //Bind modal events
            $(document).one('confirmation', this.modal, this.onConfirm);
            $(document).one('cancellation', this.modal, this.onCancel);

            //Open the modal
            this.modal.open();

        },

        /**
         * Handler for modal confirmations.
         *
         * @param  Event event
         * @return void
         */
        onConfirm: function (event) {

            //Stop bubbling
            event.stopPropagation();

            //Confirm selection
            this.confirm();

        },

        /**
         * Modal cancellation handler.
         *
         * @param  Event event
         * @return void
         */
        onCancel: function (event) {

            //Stop bubbling
            event.stopPropagation();

            //Cancel selection
            this.cancel();

        },

        /**
         * Confirm the current state by sending it the API.
         *
         * @return void
         */
        confirm: function () {

            //Switch mode back to badge
            this.mode = 'badge';

            //Set saving flag
            this.saving = true;

            //Pack our payload
            var payload = {
                data: {
                    type: this.type,
                    id: this.id,
                    attributes: {}
                }
            };

            //Set update field
            payload.data.attributes[this.field] = ('null' !== this.state) ? this.state : null;

            //Send request to API
            this.$http.patch(this.type+'/'+this.id, payload)
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

                    //Capture transaction if provided
                    var options = {}
                    var headers = response.headers;
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    //Dispatch update event
                    this.$root.bus.$emit('status.updated', {
                        type:      this.type,
                        id:        this.id,
                        newStatus: this.state,
                        oldStatus: this.original_state
                    })

                    //Dispatch success message
                    this.$root.bus.$emit('success', 'Status updated successfully.', options)

                    //Clear saving flag
                    this.saving = false

                    //Update our original state so that a future cancellation
                    //rollback returns to last saved
                    this.original_state = this.state

                }.bind(this), function (response) {

                    //Dispatch generic error
                    this.$root.bus.$emit('error', 'An error occured when trying to update that status. Please try again later.')

                    //Clear saving flag
                    this.saving = false

                    //Cancel state
                    this.cancel()

                }.bind(this));

        },

        /**
         * Cancel the current selection by changing state back to original
         * and destroy any active modals.
         *
         * @return void
         */
        cancel: function () {

            //Fix scoping
            var that = this;

            //We have an active modal
            if (this.modal) {

                //Destroy the modal when closed
                $(document).one('closed', this.modal, function () {
                    that.modal.destroy();
                    that.modal = null;
                });

                //Close the modal
                this.modal.close();

            }

            //Set state back to original
            this.state = this.original_state;

            //Switch mode back to badge
            this.mode = 'badge';

        },

        /**
         * Status update event listener.
         */
        onStatusUpdate: function (type, id, status, field) {

            //Set field to default status
            if ('undefined' == typeof field) {
                var field = 'status'
            }

            //This does not match, continue propagation
            if (type !== this.type || id != this.id || field !== this.field) {
                return true
            }

            //Update status
            this.state = status

            //Always continue propagation
            return true

        }

    }
})
