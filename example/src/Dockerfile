FROM alpine:3.21

RUN apk update
RUN apk upgrade
RUN apk add curl

COPY entrypoint.sh /app/
RUN chmod +x /app/entrypoint.sh

ENTRYPOINT [ "/app/entrypoint.sh" ]