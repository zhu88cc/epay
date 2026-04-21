FROM php:8.2-apache

# 让 .htaccess 的 rewrite 规则生效
RUN a2enmod rewrite headers \
  && docker-php-ext-install pdo pdo_mysql

# 允许站点目录使用 .htaccess
COPY docker/apache-epay.conf /etc/apache2/conf-available/epay.conf
RUN a2enconf epay

WORKDIR /var/www/html
COPY . /var/www/html

# 容器启动时根据环境变量写入 config.php
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
