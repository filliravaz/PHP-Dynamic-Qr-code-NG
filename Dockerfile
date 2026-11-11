FROM php:8.4-apache

# Download script to install PHP extensions and dependencies
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions

RUN DEBIAN_FRONTEND=noninteractive apt-get update -q \
    && DEBIAN_FRONTEND=noninteractive apt-get upgrade -qq -y \
    && DEBIAN_FRONTEND=noninteractive apt-get install -qq -y --no-install-recommends \
      ca-certificates
      ca-certificates \
      curl \
      libzip-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libfreetype6-dev \
      procps \
      tar \
      tini \
      zip unzip \
# iconv, mbstring and pdo_sqlite are omitted as they are already installed
    && PHP_EXTENSIONS=" \
      bcmath \
      bz2 \
      calendar \
      exif \
      gd \
      gettext \
      imagick \
      intl \
      mysqli \
      opcache \
      pdo_mysql \
      zip \
    " \
    && install-php-extensions $PHP_EXTENSIONS \
    && a2enmod rewrite headers \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && echo "LimitRequestBody 10485760" > /etc/apache2/conf-available/limits.conf \
    && a2enconf limits \
    # Hide Apache/PHP version banners
    && sed -ri -e 's!^ServerTokens .*!ServerTokens Prod!' -e 's!^ServerSignature .*!ServerSignature Off!' /etc/apache2/conf-available/security.conf \
    && { \
        echo "upload_max_filesize = 5M"; \
        echo "post_max_size = 10M"; \
    } > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini \
    && rm -rf /var/lib/apt/lists/*

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

RUN rm -rf /var/www/html/.git 2>/dev/null || true

RUN chown -R root:root /var/www/html \
    && chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/html/saved_qrcode

# Run as an unprivileged user. Non-root processes cannot bind to ports < 1024,
# so Apache listens on 8080 inside the container (map it with e.g. "80:8080").
RUN sed -ri -e 's!^Listen 80$!Listen 8080!' /etc/apache2/ports.conf \
    && sed -ri -e 's!<VirtualHost \*:80>!<VirtualHost *:8080>!' /etc/apache2/sites-available/000-default.conf \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

USER www-data

EXPOSE 8080
CMD ["apache2-foreground"]
