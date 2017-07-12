var Vue = require('vue')
var _   = require('underscore')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth'],
    components: {
        'event-browser': require('../../global/eventBrowser')
    },
    created: function ()
    {
        // Update subheader title
        this.$root.bus.$emit('subheader.updateTitle', 'Activity');
    }
})
