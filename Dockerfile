# syntax=docker/dockerfile:1

# === Install dependencies === #
FROM composer:latest as composer-build
WORKDIR /build

COPY /composer.json /composer.lock ./
RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-progress --no-interaction --no-dev --no-autoloader
RUN composer dump-autoload --optimize --apcu --no-dev

# === Copy app === #
FROM php:8.1-rc-cli-alpine
WORKDIR /app

ENV OWL_DATA=/data

# TODO: RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=composer-build /build/vendor /app/vendor
COPY /src /app/src
COPY docker/healthcheck.sh /healthcheck.sh

# Make log file
#RUN touch /var/log/cron.log
RUN ln -sf /dev/stdout /var/log/cron.log

# Setup cron job
RUN echo "*/1 * * * * /usr/local/bin/php /app/src/owl-tkn.php >> /var/log/cron.log" >> /etc/crontabs/root

# Run cron in foreground
CMD crond -l 8 -f

HEALTHCHECK --start-period=1m --interval=5m CMD /healthcheck.sh
VOLUME ["/data"]