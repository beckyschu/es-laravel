var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    components: {
        'reports-subheader': require('./subheader')
    }
})
