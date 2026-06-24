FROM friendica:2026.01-apache

# Overlay UDP Friendica fork files on top of the official base image.
#
# The base image already has:
#   - All PHP extensions (imagick, redis, memcached, apcu, etc.)
#   - vendor/ with Composer dependencies from the official 2026.05 release
#   - entrypoint.sh, cron.sh, setup_msmtp.sh
#
# We replace the application source files (src/, view/, static/, mod/, etc.)
# while leaving vendor/ and the entrypoint scripts untouched. The entrypoint
# rsyncs /usr/src/friendica → /var/www/html on first boot as usual.

RUN apt-get update && apt-get install -y --no-install-recommends ffmpeg && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data . /usr/src/friendica/
