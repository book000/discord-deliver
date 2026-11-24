# discord-deliver

Web server to send messages to Discord for use with Docker

## Configuration

`discord-deliver.env`

- `DISCORD_TOKEN`: Discord Bot Token (required)
- `DISCORD_CHANNEL_ID`: Default Discord Channel Id (optional)
  - This value is used if the channel ID is not specified in the URL.

Container environment (optional):

- `PHP_CLI_SERVER_WORKERS`: Number of worker processes of the PHP built-in server (default `8`). Set `1` to restore single-worker behavior.

ref: [example directory](example/)

## Usage

The container listens on port 80. Send a JSON `POST` request:

- `POST /`: sends to the channel in `DISCORD_CHANNEL_ID`
- `POST /{channel_id}`: sends to the channel given in the URL (a single path segment containing decimal digits only). An invalid path is rejected. A URL with a query string uses `DISCORD_CHANNEL_ID` instead

```bash
curl -X POST -H "Content-Type: application/json" -d '{"content":"Hello, world!"}' "http://discord-deliver/{channel_id}"
```

Request body (JSON, `Content-Type` must be `application/json`, optionally with parameters such as `charset=utf-8`):

- `content`: plain text message (string)
- `embed`: a single Discord embed object

The body must be a JSON object with non-empty `content` or a non-empty `embed`. When present, `content` must be a string and `embed` must be an object. Embed fields are validated by Discord.

The response is `{"status": true, "response": ...}` on success. On failure it is `{"status": false, "message": ...}` with one of these HTTP status codes:

- 400: empty or invalid request body, invalid field types, or no non-empty message
- 404: the URL path is invalid, or no valid channel ID is available from the URL or `DISCORD_CHANNEL_ID`
- 405: method other than `POST`
- 415: `Content-Type` is not `application/json`
- 500: `DISCORD_TOKEN` is not set
- 502: The Discord API could not be reached or returned an invalid HTTP status
- 504: The Discord API sent no data for 10 seconds (the timeout applies to each wait, not to the whole request). The message may still have been delivered by Discord
- Any other status returned by the Discord API (such as 401, 403, 429) is passed through, with the Discord response in `response`.

## Warning / Disclaimer

The developer is not responsible for any problems caused by the user using this project.

## License

The license for this project is [MIT License](LICENSE).
