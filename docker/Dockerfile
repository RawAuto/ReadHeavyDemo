FROM php:8.2-fpm-alpine

# Install any extensions if needed (e.g. redis)
# RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]



