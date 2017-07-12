var Vue = require('vue')
var _ = require('underscore')

import VueMultiselect from 'vue-multiselect'

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        multiselect: VueMultiselect
    },
    data: function () {
        return {
            isImporting: false,
            isValidated: false,
            validationOutput: false,
            modal: null,
            file: null,
            platform: null,
            platforms: require('../../../helpers/platforms')
        }
    },
    computed: {
        platformOptions: function () {
            return _.map(this.platforms, function (label, id) {
                return {
                    id: id,
                    label: label
                }
            })
        }
    },
    mounted: function () {

        // Instantiate modal
        this.modal = $('[data-remodal-id=importer]').remodal({
            hashTracking: false
        })

        // Listen to events
        this.$root.bus.$on('discoveryImporter.open', this.onOpen)

    },
    beforeDestroy: function () {

        // Clear up the modal to force it to render again when we come back
        this.modal.destroy()

        // Unlisten to events
        this.$root.bus.$off('discoveryImporter.open', this.onOpen)

    },
    methods: {

        /**
         * Assign file pointer when selected.
         */
        onFileChange: function (event) {
            this.file = event.target.files[0] || event.dataTransfer.files[0];
            this.isValidated = false
            this.validationOutput = false
        },

        /**
         * Upload the selected CSV.
         */
        upload: function () {

            // Already uploading
            if (this.isImporting) return

            // File or platform has not been selected
            if (! this.file || ! this.platform) return

            // Set flag
            this.isImporting = true

            // Instantiate a form data object
            var formData = new FormData();

            // Add form data
            formData.append('platform', this.platform.id);
            formData.append('file', this.file);
            formData.append('action', this.isValidated ? 'import' : 'validate');

            // Send request to API
            this.$http.post('imports', formData)
                .then(function (response) {

                    // Clear flag
                    this.isImporting = false

                    /**
                     * Display validation message
                     * Backported from 8ff34c7 and cc950de
                     * @todo Needs cleaning up
                     */

                    if (! this.isValidated)
                    {
                        var response_data = response['data'];
                        var response_str  = '';
                        var valid_count   = 0;

                        if ($.isEmptyObject(response_data)) {
                            response_str = '<span style="color:red">No keywords found in this file.</span>';
                        } else {
                            for (var keyword in response_data) {
                                if (response_data[keyword]['found']) {
                                    valid_count++;
                                }

                                response_str += '<div style="display:inline-block;' +
                                    (response_data[keyword]['found'] ? 'color:green' : 'color:red') +
                                    '">' + keyword + ': ' + response_data[keyword]['count'] +
                                    ',&nbsp;&nbsp;&nbsp;&nbsp;</div>';
                            }

                            if (valid_count > 0) {
                                response_str += '<div>If you confirm to import, click the &#60;Import&#62; button, else click &#60;Cancel&#62; button.</div>'
                                this.isValidated = true
                            } else {
                                response_str += '<div>No valid keywords found in the file.</div>';
                            }
                        }

                        // Add validation output
                        this.validationOutput = response_str

                        return false;
                    }

                    // Hide validation output
                    this.validationOutput = false

                    // Clear flag
                    this.isValidated = true;

                    // Close modal
                    this.closeModal()

                    // Send success message
                    this.$root.bus.$emit('success', 'Your import has been uploaded. It will be processed in the next few minutes.')

                }, function (response) {

                    // Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    // Clear flag
                    this.isImporting = false

                });

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
        onOpen: function () {

            // Reset validation status
            this.isValidated = false

            // Hide validation output
            this.validationOutput = false

            // Open the modal
            this.modal.open();

        }

    }
})
