var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth'],
    components: {
        'me-subheader': require('./subheader')
    }
})
