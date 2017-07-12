var Vue         = require('vue')
var VueRouter   = require('vue-router')
var VueResource = require('vue-resource')
var auth        = require('./authStore')
var _           = require('underscore')
var io          = require('socket.io-client')

import {routes} from './routes'

Vue.use(VueResource)
Vue.use(VueRouter)

Vue.http.options.root = '/api';

// Instiate the router
const router = new VueRouter({
    mode: 'history',
    routes
})

// Instantiate the event bus
const bus = new Vue()

// Authenticate non-guest routes
router.beforeEach((to, from, next) => {

    // This route requires authentication
    if (! to.matched.some(record => record.meta.allowsGuest) && ! auth.isAuthenticated()) {
        next('/login')
    }

    next()

})

// Add HTTP interceptors
Vue.http.interceptors.push((request, next) => {

    // Add authorization header if we have a token
    if (auth.token) {
        request.headers.set('Authorization', 'Bearer '+auth.token)
    }

    // Add account header if we have an account
    if (auth.account && auth.account.id) {
        request.headers.set('X-Account', auth.account.id)
    }

    next()

})

//Define the base app
const app = Vue.extend({
    data: function () {
        return {
            auth: auth,
            bus: bus,
            socket: null
        }
    },
    router,
    created: function ()
    {
        ///////////////////////
        // Socket connection //
        ///////////////////////

        this.socket = io(window.location.hostname+':6001', {
            reconnectionDelay: 10000 // Only reconnect every 10 seconds
        })

        this.socket.on('reconnecting', function () {
            this.bus.$emit('message', 'Attempting to reconnect to server...')
        }.bind(this))

        this.socket.on('connect', function () {
            this.bus.$emit('success', 'Connected to server')
        }.bind(this))

        /////////////////////////////
        // Listen to socket events //
        /////////////////////////////

        //Listen to socket subscribes
        this.bus.$on('subscribe', function (channel) {
            console.log('Subscribed to '+channel)
            this.socket.emit('subscribe', channel)
        }.bind(this))

        //Listen to socket unsubscribes
        this.bus.$on('unsubscribe', function (channel) {
            console.log('Unsubscribed from '+channel)
            this.socket.emit('unsubscribe', channel)
        }.bind(this))

        ///////////////////
        // Socket events //
        ///////////////////

        // Crawl attributes have been updated (broadcast on crawl.x)
        this.socket.on('App\\Events\\Broadcast\\CrawlWasUpdated', function (data) {
            console.log('App\\Events\\Broadcast\\CrawlWasUpdated', data)
            this.bus.$emit('App\\Events\\Broadcast\\CrawlWasUpdated', data)
        }.bind(this))

        // Crawl log has been updated (broadcast on crawl.x)
        this.socket.on('App\\Events\\Broadcast\\CrawlLogUpdated', function (data) {
            console.log('App\\Events\\Broadcast\\CrawlLogUpdated', data)
            this.bus.$emit('App\\Events\\Broadcast\\CrawlLogUpdated', data)
        }.bind(this))

        // Scan attributes have been updated (broadcast on scan.x)
        this.socket.on('App\\Events\\Broadcast\\ScanWasUpdated', function (data) {
            console.log('App\\Events\\Broadcast\\ScanWasUpdated', data)
            this.bus.$emit('App\\Events\\Broadcast\\ScanWasUpdated', data)
        }.bind(this))

        // Scan log has been updated (broadcast on scan.x)
        this.socket.on('App\\Events\\Broadcast\\ScanLogUpdated', function (data) {
            console.log('App\\Events\\Broadcast\\ScanLogUpdated', data)
            this.bus.$emit('App\\Events\\Broadcast\\ScanLogUpdated', data)
        }.bind(this))
    }
});

// First attempt to grab auth details from storage
auth.fetchFromStorage().then(function () {

    // User fetched, boot the app
    new app({ el: '#app' })

}, function () {

    // User not fetched, boot the app
    new app({ el: '#app' })

})
