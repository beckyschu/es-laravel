var Vue   = require('vue')
var url   = require('url')
var _     = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['keyword'],
    data: function () {
        return {
            setting: null,
            modal: null,
            isUpdating: false,
            errors: []
        }
    },
    mounted: function() {

        // Instantiate modal
        this.modal = $('[data-remodal-id=parsehub_setting_editor]').remodal({
            hashTracking: false
        });

        // Register open event
        this.$root.bus.$on('parsehubSettingEditor.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy();

        // Unlisten to open event
        this.$root.bus.$off('parsehubSettingEditor.open', this.onOpen)

    },
    methods: {

        /**
         * Open modal.
         */
        openModal: function () {
            this.modal.open()
        },

        /**
         * Close modal
         */
        closeModal: function (event) {
            this.modal.close()
        },

        /**
         * Update the setting.
         */
        update: function ()
        {
            // Already updating, bail out
            if (this.isUpdating) return;

            // Set flag
            this.isUpdating = true

            // Setting doesnt exist yet, create it
            if (! this.setting.id) {
                this.$http.post('keywords/'+this.keyword.id+'/settings', this.setting)
                    .then(function (response) {

                        // Close modal
                        this.closeModal()

                        // Dispatch success message
                        this.$root.bus.$emit('success', 'Settings updated successfully.')

                        // Clear flag
                        this.isUpdating = false

                        // Update underlying data
                        this.$root.bus.$emit('data.reload')

                    }, function (response) {

                        // Clear flag
                        this.isUpdating = false

                    });

                return
            }

            // Setting already exists, update it
            this.$http.patch('keywords/'+this.keyword.id+'/settings/'+this.setting.id, this.setting)
                .then(function (response) {

                    // Close modal
                    this.closeModal()

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Settings updated successfully.')

                    // Clear flag
                    this.isUpdating = false

                    // Update underlying data
                    this.$root.bus.$emit('data.reload')

                }, function (response) {

                    // Clear flag
                    this.isUpdating = false

                });
        },

        /**
         * Open event listener.
         */
        onOpen: function (setting) {
            this.setting = JSON.parse(JSON.stringify(setting))
            this.openModal()
        }

    }
})
