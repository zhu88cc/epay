FROM php:8.2-apache

RUN a2enmod rewrite headers \
  && docker-php-ext-install pdo pdo_mysql \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

# 安装脚本会生成 config.php，这里提供一个运行时覆盖能力
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]

