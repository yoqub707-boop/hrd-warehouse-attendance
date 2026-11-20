FROM php:8.3-fpm-alpine

# Kerakli paketlarni o'rnatish
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev

# PHP kengaytmalarini sozlash va o'rnatish
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        intl \
        opcache

# Opcache va upload sozlamalari
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'upload_max_filesize=32M'; \
        echo 'post_max_size=32M'; \
        echo 'memory_limit=256M'; \
        echo 'date.timezone=Asia/Tashkent'; \
    } > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Huquqlarni sozlash
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
