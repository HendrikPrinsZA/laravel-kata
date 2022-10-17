#!/usr/bin/env node

/**
 * Doesn't seem compatible with reaching outside of node
 *
 * Not the right too, but maybe a good reference so keeping
 */

'use strict'

const autocannon = require('autocannon')

// Before
const inst = autocannon({
    url: 'http://localhost/api/kata/after', // unable to reach
    // url: 'http://localhost:80/api/kata/after', // unable to reach
    // url: 'http://0.0.0.0:80/api/kata/after', // unable to reach
    duration: 10,
    connections: 10,
    pipelining: 1,
    headers: {
        'Content-Type': 'application/json'
    },
    debug: true,
    setupClient: setupClientEventHandlers,
}, (err, result) => {
    if (err) {
        console.error(err)
    }

    console.log(result)
});

let cnt = 0;
inst.on('reqError', e => console.log(`Request error (${cnt++}): ${e}`));

function setupClientEventHandlers (client) {
    client.on('connError', (error) => console.log('connection error', error))
    client.on('timeout', (error) => console.log('timeout'))
}
