#!/bin/sh

if ping discord-deliver -c 1 -W 10; then
    curl -X POST -H "Content-Type: application/json" -d '{"content":"Hello, world!"}' "http://discord-deliver/${DISCORD_CHANNEL_ID}"
fi
