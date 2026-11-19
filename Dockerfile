FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile --network-timeout 600000
COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN yarn build

FROM dunglas/frankenphp:1-php8.3
RUN install-php-extensions pdo_mysql gd zip intl bcmath opcache pcntl exif ldap
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && chmod +x docker/start.sh \
    && mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
CMD ["docker/start.sh"]
