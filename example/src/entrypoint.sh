#!/bin/sh

if ping discord-deliver -c 1 -W 10; then
    curl -X POST -H "Content-Type: application/json" -d '{"content":"Hello, world! (1)"}' "http://discord-deliver"
    curl -X POST -H "Content-Type: application/json" -d '{"content":"Hello, world! (2)"}' "http://discord-deliver/${DISCORD_CHANNEL_ID}"
fi
