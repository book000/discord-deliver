#!/bin/sh

export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-8}"

php -S 0.0.0.0:80 main.php
