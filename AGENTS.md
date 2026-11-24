# AGENTS.md

## Overview

discord-deliver is a small web server that forwards JSON `POST` requests to the Discord Bot API (`/channels/{id}/messages`). It is a single PHP script (`src/main.php`, PHP 8 built-in server, no dependencies) run in Docker. See `README.md` for configuration, endpoints and error codes.

## Language

- Deliverables are written in English: commit descriptions, PR/Issue titles and bodies, review comments, code comments, documentation, and this file.
- Runtime messages (logs, error responses) are English.
- Exceptions: quotations, technical identifiers, generated files, external source text.
- Never rewrite Git history to change the language of past commits.

## Conventions

- Commits follow [Conventional Commits](https://www.conventionalcommits.org/). Branches follow [Conventional Branch](https://conventional-branch.github.io) short form (`feat/...`, `fix/...`, `docs/...`).
- Default branch is `master`.
- Do not push commits to PRs created by Renovate.
- Never commit secrets. `*.env` files (`discord-deliver.env`, `example/example.env`) are git-ignored; keep `DISCORD_TOKEN` and the channel ID out of code, logs and responses.
- Keep the project dependency-free (PHP standard functions only) and lightweight.

## Structure

- `src/main.php`: the whole server logic. Validates method, `Content-Type`, `DISCORD_TOKEN`, channel ID and body, then calls the Discord API and relays its status code.
- `src/Dockerfile`, `src/entrypoint.sh`: `php:8-alpine` image; the entrypoint runs `php -S 0.0.0.0:80 main.php`.
- `docker-compose.yml`: compose file using the published image `book000/discord-deliver`.
- `example/`: compose setup with a curl client that posts two messages (needs `discord-deliver.env` and `example.env`).
- `.github/workflows/`: `docker-image.yml` (build check on push and PR), `shell-ci.yml` (ShellCheck, excludes SC2086 and SC2001), `docker-publish.yml` (multi-arch image push to Docker Hub on release).

## Commands

There is no `package.json` or linter config.

```bash
docker build src/ -t discord-deliver   # same as CI build check
shellcheck -e SC2086,SC2001 src/entrypoint.sh example/src/entrypoint.sh
php -l src/main.php                    # syntax check, if PHP is installed
php tests/channel-id.php               # channel ID regression tests
cd example && docker compose up        # manual end-to-end check against real Discord
```

## Development notes

- When endpoints, env vars, request body or error responses change, update `README.md`, `docker-compose.yml` and `example/` together.
- Error responses use the `{"status": false, "message": ...}` shape; call `http_response_code()` before `exit`.
- Discord API status codes are passed through to the client. Do not break this.
