var Vue    = require('vue')
var moment = require('moment')

module.exports = Vue.extend({
    template: require('./template.html'),
    methods: {
        shouldShow: function () {
            return 'admin.ipshark.com' !== document.location.hostname
        }
    }
})
