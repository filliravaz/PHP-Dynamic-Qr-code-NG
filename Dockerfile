FROM php:8.4-apache

RUN if [ "$(grep '^VERSION_ID=' /etc/os-release | cut -d '=' -f 2 | tr -d '"')" -eq "9" ]; then \
        sed -i -e 's/deb.debian.org/archive.debian.org/g' \
               -e 's/security.debian.org/archive.debian.org/g' \
               -e '/stretch-updates/d' /etc/apt/sources.list; \
    fi

# Download script to install PHP extensions and dependencies
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions

RUN DEBIAN_FRONTEND=noninteractive apt-get update -q \
    && DEBIAN_FRONTEND=noninteractive apt-get install -qq -y \
      curl \
      dnsutils \
      git \
      iputils-ping \
      less \
      libzip-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libfreetype6-dev \
      nano \
      net-tools \
      procps \
      tar \
      tini \
      zip unzip \
# iconv, mbstring and pdo_sqlite are omitted as they are already installed
    && PHP_EXTENSIONS=" \
      amqp \
      bcmath \
      bz2 \
      calendar \
      event \
      exif \
      gd \
      gettext \
      imagick \
      intl \
      ldap \
      memcached \
      mysqli \
      opcache \
      pcntl \
      pdo_mysql \
      pdo_pgsql \
      pgsql \
      redis \
      soap \
      sockets \
      xsl \
      zip \
    " \
    && case "$PHP_VERSION" in \
      5.6.*) PHP_EXTENSIONS="$PHP_EXTENSIONS mcrypt mysql";; \
      7.0.*|7.1.*) PHP_EXTENSIONS="$PHP_EXTENSIONS mcrypt";; \
    esac \
    && install-php-extensions $PHP_EXTENSIONS \
    && a2enmod rewrite headers \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Install Composer.
ENV PATH=$PATH:/root/composer/vendor/bin \
  COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_HOME=/root/composer
RUN cd /opt \
  # Download installer and check for its integrity.
  && curl -sSL https://getcomposer.org/installer > composer-setup.php \
  && curl -sSL https://composer.github.io/installer.sha384sum > composer-setup.sha384sum \
  && sha384sum --check composer-setup.sha384sum \
  # Install Composer 2.
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer --2 \
  # Remove installer files.
  && rm /opt/composer-setup.php /opt/composer-setup.sha384sum

WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction
COPY ./src ./

ARG APP_CHANNEL="LTS"
ARG APP_VERSION="2.3.0"
ARG APP_COMMIT=""

ENV APP_CHANNEL=${APP_CHANNEL} \
    APP_VERSION=${APP_VERSION} \
    APP_COMMIT=${APP_COMMIT}

RUN printf '{"channel":"%s","version":"%s","commit":"%s"}\n' "$APP_CHANNEL" "$APP_VERSION" "$APP_COMMIT" > /var/www/html/version.json

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
