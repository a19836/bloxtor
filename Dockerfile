FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies (including MySQL server)
RUN apt-get update && \
    apt-get install -y software-properties-common lsb-release ca-certificates apt-transport-https wget gnupg2 && \
    add-apt-repository ppa:ondrej/php -y && \
    apt-get update && \
    apt-get install -y apache2 mysql-server php8.3 php8.3-cli php8.3-cgi php8.3-pgsql php8.3-mbstring php8.3-curl php8.3-gd php8.3-bcmath php8.3-bz2 php8.3-dom php8.3-imap php8.3-memcache php8.3-mongodb php8.3-mysqli php8.3-odbc php8.3-pdo php8.3-simplexml php8.3-soap php8.3-ssh2 php8.3-xmlrpc php8.3-intl php8.3-sqlite3 php8.3-zip && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mods
RUN a2enmod rewrite

# Set recommended PHP settings
RUN mkdir -p /etc/php/8.3/apache2/conf.d && \
    echo "short_open_tag = On\n\
variables_order = \"EGPCS\"\n\
date.timezone = Europe/Lisbon\n\
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT\n\
upload_max_filesize = 150M\n\
post_max_size = 150M\n\
max_execution_time = 1000\n\
max_input_time = 360\n\
max_input_vars = 10000\n\
memory_limit = 1024M\n" > /etc/php/8.3/apache2/conf.d/99-bloxtor.ini

# Step 3.1: MySQL recommended configuration
RUN echo "[mysqld]\nsql-mode=\"ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION\"\nmax_allowed_packet=250M\nwait_timeout=28800\nmysql_native_password=ON\n\n[mysqld_safe]\nmax_allowed_packet=100M\n\n[client]\nmax_allowed_packet=100M\n\n[mysql]\nmax_allowed_packet=100M\n\n[mysqldump]\nmax_allowed_packet=100M\n" > /etc/mysql/my.cnf

# Step 3.2: Set MySQL root password and authentication method
RUN service mysql start && \
    mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'bloxtor'; FLUSH PRIVILEGES;"

# Step 4.1: Enable AllowOverride in Apache vhost
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf && \
    sed -i '/<Directory \/var\/www\/html\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Step 4.2: Ensure Options FollowSymLinks is enabled
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/Options .*/Options FollowSymLinks/' /etc/apache2/apache2.conf && \
    sed -i '/<Directory \/var\/www\/html\/>/,/<\/Directory>/ s/Options .*/Options FollowSymLinks/' /etc/apache2/apache2.conf

# Step 4.3: Add custom Apache limits
RUN echo "\
LimitInternalRecursion 100\n\
LimitRequestBody 0\n\
LimitRequestFields 10000000\n\
LimitRequestFieldSize 10000000\n\
LimitRequestLine 10000000\n\
LimitXMLRequestBody 10000000\n" >> /etc/apache2/apache2.conf

# Set document root
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Ensure tmp folder exists
RUN mkdir -p /var/www/html/tmp

# Step 5: Set permissions for Apache user
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose HTTP port
EXPOSE 80

# Print access info
RUN echo "--------------------------------------------------" \
 && echo "Bloxtor is ready! Access it at: http://localhost:80/" \
 && echo "Or use your Docker host IP if not running locally." \
 && echo "--------------------------------------------------"

# Start Apache in foreground
CMD ["apachectl", "-D", "FOREGROUND"]