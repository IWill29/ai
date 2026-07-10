#!/bin/sh
set -e

if [ "${RUN_OPTIMIZE:-true}" = "true" ]; then
    php artisan optimize --ansi 2>/dev/null || true
fi

mkdir -p /tmp/nginx/client_body /tmp/nginx/proxy /tmp/nginx/fastcgi /tmp/nginx/uwsgi /tmp/nginx/scgi

php-fpm -D
exec nginx -c /var/www/html/docker/nginx/nginx.conf -g 'daemon off;'
