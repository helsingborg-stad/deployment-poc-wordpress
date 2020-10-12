FROM php:7.4-fpm AS base

# Download script to install PHP extensions and dependencies
ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync; \
      DEBIAN_FRONTEND=noninteractive apt-get update -q \
    && DEBIAN_FRONTEND=noninteractive apt-get install -qq -y \
      curl \
      git \
      zip unzip \
    && install-php-extensions \
      bcmath \
      bz2 \
      calendar \
      exif \
      gd \
      intl \
      ldap \
      memcached \
      mysqli \
      opcache \
      pdo_mysql \
      pdo_pgsql \
      pgsql \
      redis \
      soap \
      xsl \
      zip \
      sockets \
      pdo_sqlsrv \
      sqlsrv; \
      rm -rf /var/lib/apt/lists/*; \
      # Install NodeJS
      curl -sL https://deb.nodesource.com/setup_12.x | bash -; \
      apt-get install -y nodejs; \
      # Install Composer.
      curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
      && ln -s $(composer config --global home) /root/composer; \
      composer self-update --snapshot;

FROM base as app

ARG NPM_TOKEN

COPY app/ /var/www/html

RUN composer install --prefer-dist --no-progress --no-suggest; \
    echo "registry=https://npm.pkg.github.com/helsingborg-stad" >> ~/.npmrc; \
    echo "//npm.pkg.github.com/:_authToken=${NPM_TOKEN}" >> ~/.npmrc; \
    php build.php --cleanup; \
    rm ~/.npmrc;

VOLUME /var/www/html