FROM base AS builder

ARG NPM_TOKEN
ARG ACF

COPY app/ /app

RUN   set -x; \
      curl -fsSL https://deb.nodesource.com/setup_14.x | bash -; \
      apt-get update; \
      apt-get install -y nodejs tar gcc g++ make unzip git wget; \
      # Install Composer.
      composer self-update --2; \
      composer install --prefer-dist --no-progress; \
      echo "registry=https://npm.pkg.github.com/helsingborg-stad" >> ~/.npmrc; \
      echo "//npm.pkg.github.com/:_authToken=${NPM_TOKEN}" >> ~/.npmrc; \
      php build.php --cleanup; \
      rm ~/.npmrc; \
      wget -O acp.zip "${ACF}" ; \
      unzip acp.zip -d wp-content/plugins/ ; \
      rm acp.zip; \
      chown -R www-data:www-data /app;

FROM base as app

COPY  --from=builder /app /app

VOLUME /var/www/html