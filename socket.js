#!/usr/bin/env node
/**
 * This file should be run using nodejs on the server at all times. It listens
 * to socket connections and broadcasts events from the Redis pub/sub system.
 *
 * Events like status updates, progress changes, notifications etc are sent
 * through this system and picked up by Vue.js on the frontend.
 */

// Include dependencies
var _        = require('underscore')
var cdr      = require('commander')
var http     = require('http')
var socketIo = require('socket.io')
var ioRedis  = require('ioredis')
var chokidar = require('chokidar')
var Path     = require('path')
require('dotenv').load()

// Define paths
var logPath = process.cwd() + '/storage/logs'

// Instantiate CLI program
var program = cdr
    .version('0.0.1')
    .option('-v, --verbose', 'Enable debug output')
    .parse(process.argv)

// Instantiate our http app
var app = http.createServer(function (req, res) {
    res.writeHead(200)
    res.end('')
})

// Instantiate the socket instance
var io = socketIo(app)

// Instantiate redis accessor
var redisHost = process.env.REDIS_HOST || '127.0.0.1'
var redisPort = process.env.REDIS_PORT || 6379
var redis = new ioRedis(redisPort, redisHost)

// Initiate file watcher
var watcher  = chokidar.watch(null, {
    atomic: 5000 // Wait 5 seconds before checking (prevents a tonne of events)
})
    .on('all', (event, path) => {

        // Only listen to add and change events
        if ('add' !== event && 'change' !== event) return

        // Path contains crawls
        if (path.indexOf('crawls') > -1)
        {
            // Calculate crawl ID from filename
            var crawlId = Path.basename(path, '.txt')

            // Set event parameters
            var message = {
                room:  'crawl.'+crawlId,
                event: 'App\\Events\\Broadcast\\CrawlLogUpdated',
                data:  {
                    crawl: {
                        id: crawlId
                    }
                }
            }
        }

        // Path contains scans
        if (path.indexOf('scans') > -1)
        {
            // Calculate scan ID from filename
            var scanId = Path.basename(path, '.txt')

            // Set event parameters
            var message = {
                room:  'scan.'+scanId,
                event: 'App\\Events\\Broadcast\\ScanLogUpdated',
                data:  {
                    scan: {
                        id: scanId
                    }
                }
            }
        }

        // Emit socket message
        io.to(message.room).emit(message.event, message.data)

        // Output debug message
        if (program.verbose) console.log('Event:', message.room, message.event, message.data)

    })

// Every 30 seconds, we check to ensure that we aren't watching any crawl logs
// that don't have an active room (i.e. nobody is subscribed to updates)
setInterval(() => {
    // Collect rooms and watchers arrays
    var rooms    = io.sockets.adapter.rooms
    var watchers = watcher.getWatched()

    ////////////
    // Crawls //
    ////////////

    // Compile a list of active crawls from the room list
    var activeCrawls = _.compact(_.map(rooms, (value, key) => {
        if ('crawl.' == key.substring(0, 6)) return key.replace('crawl.', '')
        return null
    }))

    // Compile a list of active crawl watchers from the watch list
    if (! watchers[logPath + '/crawls']) {
        var activeWatchers = []
    } else {
        var activeWatchers = _.map(watchers[logPath + '/crawls'], (path) => {
            return path.replace('.txt', '')
        })
    }

    // Output debug message
    if (program.verbose) console.log('Cleaning up crawl watchers...', activeCrawls, activeWatchers)

    // For each crawl ID that is in the room list but not in the watcher list,
    // add it as a watched file
    _.each(_.difference(activeCrawls, activeWatchers), function (crawlId) {
        watcher.add(logPath + '/crawls/' + crawlId + '.txt')
    })

    // For each crawl ID that is in the watcher list but not in the room list,
    // remove it from watched files
    _.each(_.difference(activeWatchers, activeCrawls), function (crawlId) {
        watcher.unwatch(logPath + '/crawls/' + crawlId + '.txt')
    })

    ///////////
    // Scans //
    ///////////

    // Compile a list of active scans from the room list
    var activeScans = _.compact(_.map(rooms, (value, key) => {
        if ('scan.' == key.substring(0, 5)) return key.replace('scan.', '')
        return null
    }))

    // Compile a list of active scan watchers from the watch list
    if (! watchers[logPath + '/scans']) {
        var activeWatchers = []
    } else {
        var activeWatchers = _.map(watchers[logPath + '/scans'], (path) => {
            return path.replace('.txt', '')
        })
    }

    // Output debug message
    if (program.verbose) console.log('Cleaning up scan watchers...', activeCrawls, activeWatchers)

    // For each scan ID that is in the room list but not in the watcher list,
    // add it as a watched file
    _.each(_.difference(activeScans, activeWatchers), function (scanId) {
        watcher.add(logPath + '/scans/' + scanId + '.txt')
    })

    // For each scan ID that is in the watcher list but not in the room list,
    // remove it from watched files
    _.each(_.difference(activeWatchers, activeScans), function (scanId) {
        watcher.unwatch(logPath + '/scans/' + scanId + '.txt')
    })
}, 30000)

// Listen on port 6001 for connection
app.listen(6001, function() {
    console.log('Server is running!') // Always output this message
})

// Event for new connections
io.on('connection', function(socket) {

    // Allow rooms to be joined with subscribe event
    socket.on('subscribe', function(room)
    {
        // Join the specified room
        socket.join(room)
        if (program.verbose) console.log(socket.id+' subscribed to '+room)

        // If this is a crawl room, make sure the log file is being watched
        if ('crawl.' == room.substring(0, 6)) {
            watcher.add(logPath + '/crawls/' + room.replace('crawl.', '') + '.txt')
        }

        // If this is a scan room, make sure the log file is being watched
        if ('scan.' == room.substring(0, 5)) {
            watcher.add(logPath + '/scans/' + room.replace('scan.', '') + '.txt')
        }
    })

    // Allow rooms to be left with unsubscribe event
    socket.on('unsubscribe', function(room) {
        socket.leave(room)
        if (program.verbose) console.log(socket.id+' unsubscribed from '+room)
    })

})

// Subscribe to all Redis pub/sub channels
redis.psubscribe('*', function(err, count) {
    //
})

// When Redis message sent, broadcast to listeners
redis.on('pmessage', function(subscribed, channel, message)
{
    message = JSON.parse(message)

    io.to(channel).emit(message.event, message.data)

    if (program.verbose) console.log('Event:', channel, message.event, message.data)
})
