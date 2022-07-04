# discord-deliver

Web server to send messages to Discord for use with Docker

## Configuration

`discord-deliver.env`

- `DISCORD_TOKEN`: Discord Bot Token

## Post

### docker-compose.yml

```yml
version: '3.8'

services:
  discord-deliver:
    image: book000/discord-deliver:v1.1.0
    env_file:
      - discord-deliver.env

  app:
    build:
      context: .
```

## http

```http
POST http://discord-deliver/597611705052299291
Content-Type: application/json

{
    "content": "abc"
}
```

## Warning / Disclaimer

The developer is not responsible for any problems caused by the user using this project.

## License

The license for this project is [MIT License](LICENSE).
