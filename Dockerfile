FROM webdevops/php-nginx:8.0-alpine

## Defining environment variables
ENV PROVISION_CONTEXT="production"
ENV WEB_DOCUMENT_ROOT=/app/public

## Mounting the application in the container
COPY . /app
WORKDIR /app/