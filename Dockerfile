FROM php:8.2-apache
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Asia/Damascus

RUN apt-get update && apt-get install -y \
    git zip netcat-openbsd unzip curl sudo libicu-dev libbz2-dev libpng-dev \
    libjpeg-dev libfreetype6-dev nano g++ libzip-dev tzdata \
    --no-install-recommends && rm -rf /var/lib/apt/lists/*

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) bz2 intl iconv bcmath opcache calendar pdo_mysql zip gd

RUN echo "date.timezone = Asia/Damascus" > /usr/local/etc/php/conf.d/timezone.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html



COPY --chown=www-data:www-data composer.json composer.lock ./


RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data . .

# Remove cached provider manifest so package:discover regenerates it without removed packages (e.g. spatie/laravel-activitylog)
RUN rm -f bootstrap/cache/services.php bootstrap/cache/packages.php

RUN composer dump-autoload --optimize \
    && chmod -R 775 storage bootstrap/cache \
    && git config --system --add safe.directory /var/www/html


VOLUME ["/var/www/html/storage"]
EXPOSE 80
ENV LOG_CHANNEL=stderr

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]