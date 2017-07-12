var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    name: 'KeywordShow',
    template: require('./template.html'),
    components: {
        'platform-ident': require('../../../global/platformIdent'),
        'setting-editor': require('../parsehubSettingEditor'),
        'schedule-editor': require('../scheduleEditor'),
    },
    data: function () {
        return {
            id:         this.$route.params.id,
            assetId:    this.$route.params.asset,
            keyword:    null,
            isUpdating: false,
            errors:     [],
            schedules: [
                {value: null, label: 'Never'},
                {value: 'daily', label: 'Every day'},
                {value: '2days', label: 'Every other day'},
                {value: '3days', label: 'Every 3 days'},
                {value: '4days', label: 'Every 4 days'},
                {value: '5days', label: 'Every 5 days'},
            ]
        }
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Keyword - ' + this.id)

        // Listen to data reload events
        this.$root.bus.$on('data.reload', this.onDataReload);

        // Fetch data
        this.fetchKeyword()
    },
    beforeDestroy: function ()
    {
        // Unlisten to data reload event
        this.$root.bus.$off('data.reload', this.onDataReload);
    },
    methods: {

        /**
         * Fetch keyword for current id.
         */
        fetchKeyword: function () {

            // Clear keyword
            this.keyword = null

            // Make API request
            this.$http.get('keywords/'+this.id)
                .then(function (response) {

                    // Grab data
                    this.keyword = response.data

                    // Update subheader title
                    this.$root.bus.$emit('subheader.updateTitle', 'Keywords - '+this.keyword.keyword)

                }.bind(this), function (response) {

                    // Send generic error
                    this.$root.bus.$emit('error')

                })

        },

        /**
         * Update this keyword.
         */
        update: function ()
        {
            // Already saving
            if (this.isUpdating) return

            // Set flag
            this.isUpdating = true

            // Clear errors
            this.errors = []

            // Send request to API
            this.$http.patch('keywords/'+this.id, this.keyword)
                .then(function (response) {

                    // Capture transaction if provided
                    var options = {}
                    var headers = response.headers;
                    if (headers.has('x-transaction')) {
                        options.transaction = {
                            id: headers.get('x-transaction'),
                            canUndo: headers.has('x-undo')
                        }
                    }

                    // Dispatch success message
                    this.$root.bus.$emit('success', 'Keyword updated successfully.', options)

                    // Clear saving flag
                    this.isUpdating = false

                }, function (response) {

                    // Loop errors and add to collection
                    _.each(response.data.errors, function (error) {
                        this.errors.push(error.detail)
                    }.bind(this));

                    // Clear flag
                    this.isUpdating = false

                });
        },

        /**
         * Data reload event listener.
         */
        onDataReload: function () {
            this.fetchKeyword()
        },

        /**
         * Return HTML indicator for isset.
         */
        getIssetHtml: function (value) {
            return value ? "Configured <i class=\"fa fa-check\"></i>" : "Default <i class=\"fa fa-times\"></i>"
        },

        /**
         * Return schedule label.
         */
        getScheduleLabel: function (schedule)
        {
            let label = _.findWhere(this.schedules, {value: schedule})

            if (label) {
                return label.label
            }

            return 'Unknown'
        },

        /**
         * Open setting modal for the given params.
         */
        openSettingsModal: function (setting)
        {
            this.$root.bus.$emit('parsehubSettingEditor.open', setting);
        },

        /**
         * Open schedule modal for the given params.
         */
        openScheduleModal: function (schedule)
        {
            this.$root.bus.$emit('scheduleEditor.open', schedule);
        }

    }
})
