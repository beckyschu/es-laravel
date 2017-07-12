var Vue = require('vue')

module.exports = Vue.extend({
    name: 'Dashboard',
    template: require('./template.html'),
    components: {
        'dashboard-subheader': require('./subheader')
    }
})
