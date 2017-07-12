var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['scope', 'standalone'],
    computed: {
        classStr: function () {
            let classStr = 'data-summary'

            if (this.standalone) {
                classStr += ' data-summary--standalone'
            }

            return classStr
        }
    },
    methods: {

        /**
         * Return whether or not this listing is filtered.
         *
         * @return bool
         */
        isFiltered: function () {
            if (_.filter(this.scope.filters, function (value, key) {
                return value && value.length;
            }).length) return true;

            if (_.filter(this.scope.ranges, function (value, key) {
                return value.from || value.to;
            }).length) return true;

            return false;
        },

        /**
         * Return a summary for the filters applied to this listing.
         *
         * @return string
         */
        getFilterSummary: function () {

            // Instantiate our sumamries array
            let summaries = [];

            // Loop through filters
            _.each(this.scope.filters, function (values, key) {

                // This filter doesn't have any applied values
                if (! values || ! values.length) return;

                // Loop through values and map labels
                let labels = _.map(values, function (v) {
                    if (_.isObject(v)) return v.label
                    return v
                })

                // Push labels on to summaries
                summaries.push('<i class="fa fa-' + this.scope.icons[key] + '"></i> ' + labels.join(', '))

            }.bind(this))

            // Loop through ranges
            _.each(this.scope.ranges, function (value, key) {

                // This filter doesn't have any applied values
                if (! value.from && ! value.to) return;

                // Set from value
                let min = value.from ? value.from : 0;

                // Set initial string
                let str = 'From ' + min;

                // Append to value
                if (value.to) str += ' to ' + value.to;

                // Push label on to summaries
                summaries.push('<i class="fa fa-'+this.scope.icons[key]+'"></i> ' + str);

            }.bind(this));

            return summaries.join('&nbsp;&nbsp;&nbsp;');

        }

    }
})
