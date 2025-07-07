FROM php:8.4-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmariadb-dev-compat \
    libmariadb-dev \
    default-mysql-client \
    postgresql-client \
    libc-client-dev \
    libkrb5-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libpng-dev \
    libbz2-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    libxslt1-dev \
    unixodbc \
    unixodbc-dev \
    libodbc1 \
    icu-devtools \
    libssh2-1-dev \
    vim \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql
RUN docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr
RUN docker-php-ext-install pdo_odbc
RUN docker-php-ext-install mysqli pgsql mbstring
RUN docker-php-ext-install calendar ftp pcntl shmop sockets
RUN docker-php-ext-install sysvmsg sysvsem sysvshm
RUN docker-php-ext-install xsl zip opcache
RUN docker-php-ext-install curl gd bcmath bz2
RUN docker-php-ext-install dom simplexml soap
RUN docker-php-ext-install intl

# Install PECL extensions
RUN pecl install memcache mongodb ssh2 && \
    docker-php-ext-enable memcache mongodb ssh2
RUN pecl install xmlrpc xdebug && \
    docker-php-ext-enable xdebug

# Set ServerName to suppress Apache warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache mods
RUN a2enmod rewrite

# Set recommended PHP settings
RUN mkdir -p /etc/php/8.4/apache2/conf.d && \
    echo "short_open_tag = On\n\
variables_order = \"EGPCS\"\n\
date.timezone = Europe/Lisbon\n\
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT\n\
\n\
upload_max_filesize = 150M\n\
post_max_size = 150M\n\
\n\
max_execution_time = 1000\n\
max_input_time = 360\n\
max_input_vars = 10000\n\
memory_limit = 1024M\n\
\n\
display_errors = On\n\
display_startup_errors = On\n\
log_errors = On\n\
error_log = /var/www/html/tmp/phpframework.log\n\
\n\
expose_php = Off\n\
mail.add_x_header = Off\n\
session.cookie_httponly = On\n\
session.cookie_secure = On\n\
session.use_strict_mode = On\n\
allow_url_fopen = Off\n\
allow_url_include = Off\n\
\n\
disable_functions = dl,pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,exec,shell_exec,passthru,system,proc_open,popen,parse_ini_file,show_source\n\
\n" > /usr/local/etc/php/conf.d/99-bloxtor.ini

# Enable AllowOverride in Apache vhost
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf && \
    sed -i '/<Directory \/var\/www\/html\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Ensure Options FollowSymLinks is enabled
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/Options .*/Options FollowSymLinks/' /etc/apache2/apache2.conf && \
    sed -i '/<Directory \/var\/www\/html\/>/,/<\/Directory>/ s/Options .*/Options FollowSymLinks/' /etc/apache2/apache2.conf

# Add custom Apache limits
RUN echo "\
LimitInternalRecursion 100\n\
LimitRequestBody 0\n\
LimitRequestFields 10000000\n\
LimitRequestFieldSize 10000000\n\
LimitRequestLine 10000000\n\
LimitXMLRequestBody 10000000\n" >> /etc/apache2/apache2.conf

# Make Apache listen on 8887, 8888, 8890 and 8892 because of internal request to the same port.
RUN echo "Listen 8887" >> /etc/apache2/ports.conf
RUN echo "Listen 8888" >> /etc/apache2/ports.conf
RUN echo "Listen 8890" >> /etc/apache2/ports.conf
RUN echo "Listen 8892" >> /etc/apache2/ports.conf

RUN echo '<VirtualHost *:8887 *:8888 *:8890 *:8892>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/8887_92.conf && \
    a2ensite 8887_92.conf

# Set document root
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Ensure tmp folder exists
RUN mkdir -p /var/www/html/tmp

# Remove tmp files if exists, otherwise it may contain old and local cache that may cause inconsistencies. This is important for the bloxtor demo.
RUN rm -rf /var/www/html/tmp/cache/
RUN rm -rf /var/www/html/tmp/phpframework.log

# Add the line 'other/authdb/' at the end of the /var/www/html/.gitignore file
RUN echo "" >> /var/www/html/.gitignore
RUN echo "other/authdb/" >> /var/www/html/.gitignore

RUN echo "<?php phpinfo(); ?>" > /var/www/html/info.php

# Step 5: Set permissions for Apache user
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose HTTP port
EXPOSE 80 8887 8888 8890 8892

# Print access info
RUN echo "--------------------------------------------------" \
 && echo "Bloxtor image is built!" \
 && echo "--------------------------------------------------"

RUN echo '#!/bin/bash' > /usr/local/bin/docker-entrypoint.sh && \
	echo '' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '#print env vars' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '/bin/echo "DOCKER_COMPOSE_DB_NAME: ${DOCKER_COMPOSE_DB_NAME}, DOCKER_COMPOSE_DB_USER: ${DOCKER_COMPOSE_DB_USER}"' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '#update global_variables.php' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'if [ -f "/var/www/html/app/config/global_variables.php" ]' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'then' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/\\$default_db_name\\s*:\\s*\\"[^\\"]*\\"/\\$default_db_name : \\"${DOCKER_COMPOSE_DB_NAME}\\"/g" /var/www/html/app/config/global_variables.php' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/\\$default_db_user\\s*:\\s*\\"[^\\"]*\\"/\\$default_db_user : \\"${DOCKER_COMPOSE_DB_USER}\\"/g" /var/www/html/app/config/global_variables.php' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/\\$default_db_pass\\s*:\\s*\\"[^\\"]*\\"/\\$default_db_pass : \\"${DOCKER_COMPOSE_DB_PASS}\\"/g" /var/www/html/app/config/global_variables.php' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/cat /var/www/html/app/config/global_variables.php' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'else' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	echo "Please open your browser and run the setup.php."' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '#update docker-compose.env' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'if [ -f "/var/www/html/docker-compose.env" ]' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'then' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/DOCKER_COMPOSE_DB_NAME=.*/DOCKER_COMPOSE_DB_NAME=${DOCKER_COMPOSE_DB_NAME}/g" /var/www/html/docker-compose.env' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/DOCKER_COMPOSE_DB_USER=.*/DOCKER_COMPOSE_DB_USER=${DOCKER_COMPOSE_DB_USER}/g" /var/www/html/docker-compose.env' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/DOCKER_COMPOSE_DB_PASS=.*/DOCKER_COMPOSE_DB_PASS=${DOCKER_COMPOSE_DB_PASS}/g" /var/www/html/docker-compose.env' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/sed -i "s/DOCKER_COMPOSE_DB_ROOT_PASS=.*/DOCKER_COMPOSE_DB_ROOT_PASS=${DOCKER_COMPOSE_DB_ROOT_PASS}/g" /var/www/html/docker-compose.env' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '	/bin/cat /var/www/html/docker-compose.env' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'fi' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '#remove cache files' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '/bin/rm -rf /var/www/html/tmp/cache' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '/bin/rm -rf /var/www/html/tmp/phpframework.log' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'echo ""' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'echo "--------------------------------------------------"' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'echo "Bloxtor is ready! Access it at: http://localhost:8887/setup.php or http://localhost:8888/setup.php or http://localhost:8890/setup.php or http://localhost:8892/setup.php"' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'echo "Or use your Docker host IP if not running locally."' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'echo "--------------------------------------------------"' >> /usr/local/bin/docker-entrypoint.sh && \
	echo '' >> /usr/local/bin/docker-entrypoint.sh && \
	echo 'exec apachectl -D FOREGROUND' >> /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set the entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Start Apache in foreground
CMD ["apachectl", "-D", "FOREGROUND"]
