FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . .

RUN sed -i 's|/var/www/html|/var/www/html/webpages|g' /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN echo '<Directory /var/www/html/webpages>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/duoqueue.conf \
    && a2enconf duoqueue

RUN cp -r /var/www/html/assets /var/www/html/webpages/assets

RUN find /var/www/html/webpages -name "*.php" -exec sed -i 's|../assets/|assets/|g' {} \;

EXPOSE 80