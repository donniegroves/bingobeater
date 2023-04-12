FROM php:8.1-apache
WORKDIR /var/www
COPY . .

# Define a build argument for the environment
ARG ENVIRONMENT

# Install any needed PHP extensions and Xdebug if in development
RUN docker-php-ext-install mysqli pdo_mysql \
    && if [ "$ENVIRONMENT" = "development" ]; then \
        pecl install xdebug \
        && docker-php-ext-enable xdebug \
        && echo 'xdebug.mode=debug,develop' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo 'xdebug.start_with_request=yes' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo 'xdebug.client_host=host.docker.internal' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && echo 'xdebug.log=/tmp/xdebug.log' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
        && touch /tmp/xdebug.log && chown www-data:www-data /tmp/xdebug.log; \
    fi

# Install Composer, 
# Install unzip utility, 
# Run Composer install to install the dependencies, 
# Set the document root to the public_html folder,
# Enable the Apache mod_rewrite module,
# Setting permissions on /var/www so accessible
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get update && apt-get install -y unzip \
    && composer install --no-interaction --no-progress --prefer-dist \
    && sed -i 's|DocumentRoot.*|DocumentRoot /var/www/public|' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory.*|<Directory /var/www/public>|' /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && chown -R www-data:www-data /var/www

# Update Apache configuration to allow .htaccess overrides
RUN { \
        echo '<Directory /var/www/public>'; \
        echo '    Options Indexes FollowSymLinks'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } >> /etc/apache2/sites-available/000-default.conf

# Install Node.js,
# Enable Apache mod_rewrite
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs \
    && a2enmod rewrite

# Install Node.js dependencies and run build process, create tables and start queue
COPY package.json ./
RUN npm i && npm run build 
