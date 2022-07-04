#!/bin/sh

curl -X POST -H "Content-Type: application/json" -d '{"content":"Hello, world!"}' "http://discord-deliver/${DISCORD_CHANNEL_ID}"