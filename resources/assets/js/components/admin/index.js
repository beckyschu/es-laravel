var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['auth'],
    components: {
        'admin-subheader': require('./subheader'),
        'crawl-scheduler': require('./crawlers/crawlScheduler')
    }
})
