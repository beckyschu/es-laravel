#!/usr/bin/env node
var exec = require('child_process').exec

exec('node ./socket.js', (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`)
        return
    }
    console.log(`stdout: ${stdout}`)
    console.log(`stderr: ${stderr}`)
})

exec('php artisan queue:listen --queue crawls --timeout 7200', (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`)
        return
    }
    console.log(`stdout: ${stdout}`)
    console.log(`stderr: ${stderr}`)
})

exec('php artisan queue:listen --queue scans --timeout 7200', (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`)
        return
    }
    console.log(`stdout: ${stdout}`)
    console.log(`stderr: ${stderr}`)
})

exec('php artisan queue:listen --queue submissions --timeout 7200', (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`)
        return
    }
    console.log(`stdout: ${stdout}`)
    console.log(`stderr: ${stderr}`)
})

exec('php artisan queue:listen --timeout 7200', (error, stdout, stderr) => {
    if (error) {
        console.error(`exec error: ${error}`)
        return
    }
    console.log(`stdout: ${stdout}`)
    console.log(`stderr: ${stderr}`)
})
