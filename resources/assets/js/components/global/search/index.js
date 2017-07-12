var Vue  = require('vue')
var _    = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    data () {
        return {
            query: null
        }
    },
    computed: {
        queryArr () {
            return _.map(this.query.split(','), query => query.trim())
        }
    },
    created: function () {

        // Listen for searchUpdate events
        this.$root.bus.$on('searchUpdate', queryArr => this.query = queryArr.join(', '))

    },
    methods: {
        search () {

            // Dispatch the search event
            this.$root.bus.$emit('search', this.queryArr)

        }
    }
})
