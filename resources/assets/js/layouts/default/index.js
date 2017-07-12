var Vue = require('vue')

module.exports = Vue.extend({
    name: 'DefaultLayout',
    template: require('./template.html'),
    props: ['auth'],
    components: {
        'app-header': require('../../components/global/appHeader'),
        'release-bar': require('../../components/global/releaseBar'),
        // 'app-footer': require('../../components/global/appFooter'),
        'messenger':  require('../../components/global/messenger'),
        'downloader':  require('../../components/global/downloader'),
    }
})
