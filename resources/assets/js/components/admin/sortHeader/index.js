var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['sort', 'sortKey', 'label', 'styles'],
    computed: {

        /**
         * Return the applicable classes for this header.
         */
        classes: function () {
            return {
                'table__sort-header': true,
                'table__sort-header--ASC': 'asc' == this.direction,
                'table__sort-header--DESC': 'desc' == this.direction,
            }
        },

        /**
         * Return the set direction for the current column.
         */
        direction: function ()
        {
            //This key is not selected
            if (this.sortKey !== this.sort.key) return null

            return this.sort.dir
        }

    },
    methods: {

        /**
         * Toggle the direction for this column.
         */
        toggle: function () {

            //We have a direction applied, toggle the opposite
            if (this.direction) {
                var dir = ('asc' == this.direction) ? 'desc' : 'asc'
            } else {
                var dir = 'asc';
            }

            this.sort.key = this.sortKey
            this.sort.dir = dir

        }

    }
})
