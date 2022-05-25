FROM php:fpm
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install filter
RUN mkdir /images/
RUN chown -R www-data:www-data /images/