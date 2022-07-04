FROM php:8-alpine

RUN apk update
RUN apk upgrade

WORKDIR /app

COPY main.php .
COPY entrypoint.sh /app/
RUN chmod +x /app/entrypoint.sh

EXPOSE 80

ENTRYPOINT [ "/app/entrypoint.sh" ]