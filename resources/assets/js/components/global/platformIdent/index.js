var Vue = require('vue')

module.exports = Vue.extend({
    template: require('./template.html'),
    props: ['platform'],
    computed: {

        //Return capitalised platform name
        name: function () {
            return this.platform.charAt(0).toUpperCase() + this.platform.slice(1);
        }

    }
})
