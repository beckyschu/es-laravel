var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    data: function () {
        return {
            title: null
        }
    },
    created: function ()
    {
        // Listen to events
        this.$root.bus.$on('subheader.updateTitle', this.onUpdateTitle)
    },
    beforeDestroy: function ()
    {
        // Unlisten to events
        this.$root.bus.$off('subheader.updateTitle', this.onUpdateTitle)
    },
    methods: {

        /**
         * Update title event listener.
         */
        onUpdateTitle: function (title) {
            this.title = title
        }

    }
})
