FROM php:7.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libxml2-dev \
        libxslt1-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        soap \
        sockets \
        xsl \
    && printf "\n" | pecl install mcrypt-1.0.4 \
    && docker-php-ext-enable mcrypt \
    && rm -rf /var/lib/apt/lists/*

RUN { \
      echo "date.timezone=Europe/Berlin"; \
      echo "memory_limit=512M"; \
      echo "max_execution_time=1800"; \
      echo "realpath_cache_size=4096K"; \
      echo "realpath_cache_ttl=600"; \
    } > /usr/local/etc/php/conf.d/magento.ini
