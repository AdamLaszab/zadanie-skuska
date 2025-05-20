FROM php:8.3-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y ca-certificates curl gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && NODE_MAJOR=20 \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update

RUN apt-get install -y \
    nodejs \
    build-essential \
    libpng-dev libjpeg-dev libfreetype6-dev \
    locales zip unzip git \
    libzip-dev libpq-dev libonig-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

RUN apt-get install -y python3 python3-pip python3-venv

RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-interaction --no-progress --no-scripts

COPY package.json package-lock.json ./
RUN npm install

COPY . .
RUN npm run build

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY pdf_python/requirements.txt ./pdf_python/
RUN python3 -m venv /var/www/pdf_python/venv && \
    /var/www/pdf_python/venv/bin/pip install --no-cache-dir -r pdf_python/requirements.txt

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN chown -R www-data:www-data \
    /var/www/storage \
    /var/www/bootstrap/cache \
    /var/www/public \
    /var/www/vendor

USER www-data
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]