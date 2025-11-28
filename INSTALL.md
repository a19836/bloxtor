# Bloxtor installation steps

## Table of Contents

- [Hardware Requirements](#hardware-requirements)
- [Install through Docker](#install-through-docker)
- [Install Manually on a Local PC](#install-manually-on-a-local-pc)
- [Install on a Shared Hosting Server](#install-on-a-shared-hosting-server)
- [Configure Cronjobs](#configure-cronjobs)

## Hardware Requirements

To ensure minimum performance, the following hardware specifications are recommended:

- **Processor**: One-core 1.0 GHz
- **Memory (RAM)**: 516 MB
- **Storage**: 300 MB of free space

Bloxtor is lightweight and runs seamlessly on a Raspberry Pi. Any hardware capable of running a web server (e.g., Apache, Nginx) and PHP can also run Bloxtor.

---

## Install through Docker

You have four installation options for Bloxtor using Docker:
* **A. Full Remote Installation with Prebuilt Image**: uses remote prebuilt images with all features and modules already installed on a MySQL database.
	- Ready-to-use installation, no setup required.
	- Using Docker Compose
	- Faster Process
* **B. Empty Remote Installation with Prebuilt Image**: uses remote prebuilt images but you need to run the setup from scratch on a fresh MySQL database.
	- Requires setup to be completed after installation.
	- Using Docker Compose
	- Faster Process
* **C. Empty Local Installation without Prebuilt Image**: builds a new local image from your current files using a MySQL database.
	- Requires setup to be completed after installation.
	- Using Docker Compose
	- Slower Process
* **D. Local Installation without Database**: builds a new local image from your current files without attaching a database.
	- Ideal for frontend-only testing. Setup required afterward.
	- Using Only the Dockerfile - Single Container
	- Slower Process

Note that installation through docker, doesn't install the mssql server and apache security addons.

### A. Full Remote Installation with Prebuilt Image
This setup creates two containers (the web server container and the MySQL server container).
To install a fully featured version — __with all__ modules and components preinstalled — based on the __latest available remote image__, run the following commands in your terminal:

1. Start all services:
```
env $(grep -v '^#' docker-compose.env | xargs) WEB_PORT=8892 DB_PORT=8893 docker compose -f docker-compose-remote-full.yml -p bloxtor_remote_full up --build
#or
env $(grep -v '^#' docker-compose.env | xargs) WEB_PORT=8892 DB_PORT=8893 docker-compose -f docker-compose-remote-full.yml -p bloxtor_remote_full up --build

#you can also add '--force-recreate' at the end of the above commands.
```

2. To access the framework, please open your browser and go to http://localhost:8892/__system/admin (or use your Docker host IP if not running locally).
	
	To login into Bloxtor framework please use user/pass: admin/admin.
	
	More info at [docker-compose-remote-full.yml](./docker-compose-remote-full.yml)

### B. Empty Remote Installation with Prebuilt Image
This setup creates two containers (the web server container and the MySQL server container).
To launch a fresh and empty installation — __with no__ modules, projects, or dependencies — based on the __latest available remote version__, run the following commands in your terminal:

1. Build and start all services:
```
env $(grep -v '^#' docker-compose.env | xargs) WEB_PORT=8890 DB_PORT=8891 docker compose -f docker-compose-remote-empty.yml -p bloxtor_remote_empty up --build
#or
env $(grep -v '^#' docker-compose.env | xargs) WEB_PORT=8890 DB_PORT=8891 docker-compose -f docker-compose-remote-empty.yml -p bloxtor_remote_empty up --build

#you can also add '--force-recreate' at the end of the above commands.
```

2. Then access the framework, by opening http://localhost:8890/setup.php (or use your Docker host IP if not running locally), and follow the correspondent instructions on **Step 9** below... 

	To login into Bloxtor framework please use user/pass: admin/admin.
	
	Mysql server info:
	- Host: mysql
	- DB Name, User and Pass: please check the [docker-compose.env](./docker-compose.env) file.
	
	More info at [docker-compose-remote-empty.yml](./docker-compose-remote-empty.yml)

### C. Empty Local Installation without Prebuilt Image
This setup creates two containers (the web server container and the MySQL server container).
To run a fresh, empty local installation — __with no__ preinstalled modules, projects, or dependencies — based on your __current local code__, execute the following commands in your terminal:

1. Build and start all services:
```
env $(grep -v '^#' docker-compose.env | xargs) WEB_PORT=8888 DB_PORT=8889 docker compose -p bloxtor_local up --build
#or
env $(grep -v '^#' docker-compose.env | xargs) WEB_PORT=8888 DB_PORT=8889 docker-compose -p bloxtor_local up --build

#you can also add '--force-recreate' at the end of the above commands.
```

2. Then access the framework, by opening http://localhost:8888/setup.php (or use your Docker host IP if not running locally), and follow the correspondent instructions on **Step 9** below... 

	To login into Bloxtor framework please use user/pass: admin/admin.
	
	Mysql server info:
	- Host: mysql
	- DB Name, User and Pass: please check the [docker-compose.env](./docker-compose.env) file.
	
	More info at [docker-compose.yml](./docker-compose.yml)

### D. Local Installation without Prebuilt Image and Database
This method builds and runs a single container (the web server only).
To proceed, execute the following commands in your terminal:

1. Build your Docker image:
```
docker build -t bloxtor .
#or
docker build --no-cache -t bloxtor .

#'--no-cache' is optional.
```

2. Run the container:
```
WEB_PORT=8887; docker run --name bloxtor-local-server -e WEB_PORT=$WEB_PORT -p $WEB_PORT:80 bloxtor
```

	If already created, just start it:
```
docker start bloxtor-local-server
```


3. Then access the framework, by opening http://localhost:8887/setup.php (or use your Docker host IP if not running locally), and follow the correspondent instructions on **Step 9** below...
	
	To login into Bloxtor please use user/pass: admin/admin.
	
	You will see the printed access info in the container logs.
	
	In this container there is no DB server, but you can connect your setup with your local DB or any other external DB, if apply.

---

## Install Manually on a Local PC

### 1. Install Web-server with PHP
Install web-server (eg: Apache) and PHP 5.6 or higher (Bloxtor was tested until PHP 8.4).
If you wish you can install also the Mysql and Postgres servers.

.
> If you are a sysadmin or are not sure what you are doing, please do not execute the optional steps in this tutorial.

.
#### PHP Modules
To install all php modules, here is an exemplary command to install PHP 8.4 on Ubuntu 22, after adding the ondrej ppa:
```
sudo apt install php8.4 php8.4-cli php8.4-cgi php8.4-pgsql php8.4-mbstring php8.4-curl php8.4-gd php8.4-bcmath php8.4-bz2 php8.4-dom php8.4-imap php8.4-mbstring php8.4-memcache php8.4-mongodb php8.4-mysqli php8.4-odbc php8.4-pdo php8.4-simplexml php8.4-soap php8.4-ssh2 php8.4-xmlrpc php8.4-intl php8.4-sqlite3 php8.4-zip
```
More info can be found in this [tutorial](https://askubuntu.com/questions/1484599/install-specific-php-version-on-ubuntu-22-04)

Then confirm if you have the following php modules installed:
- bcmath
- bz2 (optional)
- ctype (optional)
- curl
- dom
- date
- exif
- fileinfo
- filter
- ftp
- gd
- hash
- imap
- intl (optional)
- json
- libxml
- mbstring
- memcache (optional)
- mongodb (optional)
- mysqli (or mysql or mysqlnd - optional)
- odbc (optional)
- openssl
- pcre
- pdo (optional)
- pdo_mysql (optional)
- pdo_odbc (optional)
- pdo_pgsql (optional)
- pdo_sqlite (optional)
- pgsql
- posix
- reflection
- session
- simplexml
- sqlite3 (optional)
- soap (optional)
- ssh2
- tokenizer
- xml
- xmlreader (optional)
- xmlrpc (optional)
- xmlwriter (optional)
- xsl
- zend (op)cache (optional)
- zip
- zlib

If some module is missing you can execute in Linux a similar command like the command bellow, in order to install the missing or optional php modules:
```
sudo apt/apt-get/yum install php-bcmath php-curl php-gd php-mbstring php-pgsql php-xml php-ssh2 php-json
```
or
```
sudo apt/apt-get/yum install phpX.X-bcmath phpX.X-curl phpX.X-gd phpX.X-mbstring phpX.X-pgsql phpX.X-xml phpX.X-ssh2 phpX.X-json
```

.
#### (optional) Install MS SQL Server module
If you wish to connect to mssql-server, please install the "mssql-server" package. If you are not able to install this package on linux os, please follow the tutorials in order to install the odbc drivers for mssql-server:
- https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15
- https://www.easysoft.com/developer/languages/php/sql_server_unix_tutorial.html
- https://www.easysoft.com/products/data_access/odbc-sql-server-driver/manual/installation.html#852113

After your odbc driver be installed, it should be present in the file: /etc/odbc.ini, otherwise add the following lines:
```
[ODBC Driver 17 for SQL Server]
Description=Microsoft ODBC Driver 17 for SQL Server
Driver=/opt/microsoft/msodbcsql17/lib64/libmsodbcsql-17.7.so.2.1
UsageCount=1
```

Note that the Driver path should be to your driver.


.

### 2. Web Server configurations
#### Enable mod rewrite
Please be sure that your Web Server has the mod_rewrite enable and the php.ini files are well configured.

In linux, to enable the mod_rewrite in web-server (Apache), try to execute this command: 
```
sudo a2enmod rewrite
```
Please be sure that the rewrite mod is really enabled, otherwise the framework redirects in the .htaccess won't work.

.
#### Enable AllowOverride
Note that you must have your web-server configured to read the framework .htaccess files. 
Please be sure that you have the following settings in your vhost conf file:
```
AllowOverride All

#(optional) in case you have symbolic links in your root directory
Options FollowSymLinks
```

.
#### Set document root
Configure web-server document root to the absolute_path_to_framework/ folder in your vhost conf file, like the example below where absolute_path_to_framework is "/var/www/html/bloxtor/":
```
<VirtualHost *:80>
  #...
  
  DocumentRoot /var/www/html/bloxtor/
  
  #...
</VirtualHost>
```

.
#### (optional) Edit mod security settings
In case you have the web-server modsecurity active, change the correspondent configurations, in /etc/modsecurity/modsecurity.conf, but only if you get request body limit exceed error
```
#to 32MB
SecRequestBodyLimit 32768000

#to 640KB
SecRequestBodyNoFilesLimit 655360

#to 16MB
SecRequestBodyInMemoryLimit 16384000

#to 32MB
SecResponseBodyLimit 32768000
```

In case you get some denied requests when access the framework, you will also need to add the 'text/html' and 'text/plain' content-types in the '/usr/share/modsecurity-crs/modsecurity_crs_10_setup.conf' or '/usr/share/modsecurity-crs/base_rules/modsecurity_crs_30_http_policy.conf', this is, 
search for the following line:
```
setvar:tx.allowed_request_content_type=application/json|application/x-www-form-urlencoded|multipart/form-data"
```
and then add '|text/html' at the end, as shown below:
```
setvar:tx.allowed_request_content_type=application/json|application/x-www-form-urlencoded|multipart/form-data|text/html|text/plain"
```

Additionally, edit your vhost conf file and add the following lines:
```
LimitInternalRecursion 100
LimitRequestBody 0
LimitRequestFields 10000000
LimitRequestFieldSize 10000000
LimitRequestLine 10000000
LimitXMLRequestBody 10000000
```

if still the framework seems unstable with blocked or denied requests, you can always disable the mod security for the framework domain, by adding the following line to your vhost conf file:
```
#Disable ModSecurity
SecRuleEngine Off
```
.
#### Exemplary vhost conf file
Here is an exemplary conf file from an web-server vhost, in /etc/apache2/sites-enable/bloxtor.conf:
```
<VirtualHost *:80>
  ServerName bloxtor.local
  ServerAlias *.bloxtor.local

  DocumentRoot /var/www/html/bloxtor/

  <Directory /var/www/html/bloxtor/>
    AllowOverride All
    Options FollowSymLinks
  </Directory>

  #Uncomment the following lines if apache modsecurity is active and you get request body limit exceed error
  #LimitInternalRecursion 100
  #LimitRequestBody 0
  #LimitRequestFields 10000000
  #LimitRequestFieldSize 10000000
  #LimitRequestLine 10000000
  #LimitXMLRequestBody 10000000
  
  #Disable ModSecurity
  #SecRuleEngine Off
</VirtualHost>

```

.
#### Edit PHP.ini
Please update your php.ini files with:
```
short_open_tag = On
variables_order = "EGPCS"
date.timezone = Europe/Lisbon
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT

upload_max_filesize = 150M
post_max_size = 150M

#for better performance or in case you get request body limit exceed
max_execution_time = 1000
max_input_time = 360
max_input_vars = 10000

#set memory limit to 1G or more for better performance
memory_limit = 1024M

#if apply, uncomment following lines
#suhosin.get.max_vars = 10000
#suhosin.post.max_vars = 10000
#suhosin.request.max_vars = 10000
```
.
 
(optional) For security reasons, we recommend you to add these instructions too:
```
display_errors = Off
display_startup_errors = Off
log_errors = On

expose_php = Off
mail.add_x_header = Off
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
allow_url_fopen = Off
allow_url_include = Off

#restrict php to only access these folders, because of hackings
open_basedir = "<your cms installation dir>:/tmp"

#disable some functions that can be unsafe, because of hackings
disable_functions = dl,pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,exec,shell_exec,passthru,system,proc_open,popen,parse_ini_file,show_source
```
.

(optional) Additionally for security reasons also, if you have a tmp folder created inside of "your cms installation dir", we recommend you to update your php.ini files with:
```
sys_temp_dir = "<your cms installation dir>/tmp"
upload_tmp_dir = "<your cms installation dir>/tmp"
session.save_path = "<your cms installation dir>/tmp"
soap.wsdl_cache_dir = "<your cms installation dir>/tmp"
```
.

Note that, if you decide to follow our recommendation, you should make these changes in the php.ini from web-server, cli and cgi modes. Usually these files are located in:
- /etc/php/apache2/php.ini
- /etc/php/cli/php.ini

or
- /etc/php/X.X/apache2/php.ini
- /etc/php/X.X/cli/php.ini
- /etc/php/X.X/cgi/php.ini
.

.

(optional) Instead of editing some of these settings in the php.ini, you can do it in the vhost file, like the following exemplary conf file in /etc/apache2/sites-enable/bloxtor.conf:
```
<VirtualHost *:80>
  ServerName bloxtor.local
  ServerAlias *.bloxtor.local

  DocumentRoot /var/www/html/bloxtor/

  <Directory /var/www/html/bloxtor/>
    AllowOverride All
    Options FollowSymLinks
  </Directory>

  #uncomment the following lines if apache modsecurity is active and you get request body limit exceed error
  #LimitInternalRecursion 100
  #LimitRequestBody 0
  #LimitRequestFields 10000000
  #LimitRequestFieldSize 10000000
  #LimitRequestLine 10000000
  #LimitXMLRequestBody 10000000

  # Other php directives here
  php_admin_value open_basedir "/var/www/html/bloxtor/"
  php_admin_value sys_temp_dir "/var/www/html/bloxtor/tmp"
  php_admin_value upload_tmp_dir "/var/www/html/bloxtor/tmp"
  php_admin_value session.save_path "/var/www/html/bloxtor/tmp"
  php_admin_value soap.wsdl_cache_dir "/var/www/html/bloxtor/tmp"

  #php_admin_value display_errors Off
</VirtualHost>
```

.

### 3. If Mysql is installed
In case you have mysql installed, please go to your /etc/mysql/my.cnf and follow our recomendations:
```
#The idea is to remove the NO_ZERO_IN_DATE and NO_ZERO_DATE settings in the sql-mode. 
#Additionally give some enough memory to mysql server run properly.

[mysqld]
#if mysql version < 8
sql-mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"

#if mysql version >= 8
sql-mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"
	
max_allowed_packet=250M
wait_timeout=28800

# Enable mysql_native_password plugin if mysql version is 8 or bigger
mysql_native_password=ON

[mysqld_safe]
max_allowed_packet=100M

[client]
max_allowed_packet=100M

[mysql]
max_allowed_packet=100M

[mysqldump]
max_allowed_packet=100M
```

Don't forget to restart mysql server and check if was started without errors.


.

### 4. If your OS is CentOS
On CentOS, web-server probably has external network connections blocked, which doesn't allow MySQL to connect to databases.
To check if this is OFF, please type the following commands:
```
sudo getsebool -a | grep httpd_can_network
```

If the httpd_can_network_connect is OFF, you should enable them by typing:
```
sudo setsebool -P httpd_can_network_connect 1
```


.

### 5. Set permissions
Open script absolute_path_to_framework/other/script/set_perms.sh and edit the APACHE_USER var with the right user for your web-server.
Then execute this script:
```
sudo /bin/bash <absolute path to framework>/other/script/set_perms.sh <absolute path to framework>
```


.

### 6. (optional) TMP folder
By default, the framework already have a tmp folder in absolute_path_to_framework/tmp, which will be detected automatically.

If you wish to have a different TMP folder, you can create that folder and configure it in the global variables (absolute_path_to_framework/app/config/global_variables.php and/or absolute_path_to_framework/app/__system/config/global_variables.php). 
In this case please don't forget to give write permission to web-server for that folder.

Additionally please remove all the temporary files inside of your TMP folder. By default the TMP folder that is in absolute_path_to_framework/tmp is already empty. In case you are running the setup.php again, please erase the files inside of this folder, except the .htaccess file.


.

### 7. Other Notes:
This framework uses some external libraries (with LGPL licenses) to improve its usability and add new functionalities, which means that if you want to have all the features, you must install them, but this is optional, because Bloxtor has an incompatible license and therefore cannot be distributed together with these libraries.
When you run the framework installation script (through the url: "your.installation.domain/setup.php"), you will be prompted to install them or not.

The external libraries, with LGPL and GPL licences, are:
- *phpjavascriptpacker*: that should be copied to the app/lig/vendor/ folder;
- *phpmailer*: that should be copied to the app/lig/vendor/ folder;
- *xsssanitizer*: that should be copied to the app/lig/vendor/ folder;

- *ckeditor*: that should be copied to the app/__system/layer/presentation/common/webroot/vendor/ and app/layer/presentation/common/webroot/vendor/ folders;
- *tinymce*: that should be copied to the app/__system/layer/presentation/common/webroot/vendor/ and app/layer/presentation/common/webroot/vendor/ folders;

The external libraries, with GPL licences, are:
- *wordpress* (wordpress.zip): that should be copied to the app/__system/layer/presentation/phpframework/webroot/vendor/ folder;
- *phpmyadmin* (phpmyadmin.zip): that should be copied to the app/__system/layer/presentation/common/webroot/cms/ folder;


.

### 8. Restart web-server
Restart web-server and check if it was started without errors.


.

### 9. Open the setup.php in your browser
Open your browser, type htttp://your.installation.domain/setup.php, then follow the correspondent instructions...

> The setup.php file is in the absolute_path_to_framework/app/ folder, but the absolute_path_to_framework/.htaccess file will redirect the 'htttp://your.installation.domain/setup.php' to the app folder, so don't worry... If this doesn't happen, it means you didn't activate the web-server rewrite mod.

Follow the installation steps in the [Bloxtor tutorial](https://bloxtor.com/onlineitframeworktutorial/):
- [1st phase](https://bloxtor.com/onlineitframeworktutorial/?block_id=video/advanced#tutorial_setup): Complete setup;
- [2nd phase](https://bloxtor.com/onlineitframeworktutorial/?block_id=video/advanced#tutorial_modules_installation): Install modules in your Bloxtor;
- 3rd phase: Watch other videos and follow correspondent steps.

---

## Install on a Shared Hosting Server

### Requirements:
- Linux server with cPanel
- MySQL
- Apache
- PHP

### Steps:
1. Log in to your cPanel and open the File Manager application.
2. Copy the contents of the `Bloxtor` folder into the `public_html` or `www` directory of your remote server.
3. Select the PHP version, activate the required PHP modules, and configure the `php.ini` file according to steps 1 and 2 described above.
4. Confirm that cPanel correctly modified the `public_html/.htaccess` file. Otherwise, apply the necessary changes. Here is an example:
```
# BEGIN cPanel-generated php ini directives
<IfModule php7_module>
   php_flag short_open_tag On
   php_value max_execution_time 1000
   php_value variables_order "EGPCS"
   php_value upload_max_filesize 200M
   php_value post_max_size 200M
   php_value date.timezone "Europe/Lisbon"
   #php_value error_reporting "E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT"
   php_value error_reporting 22519
   php_flag display_errors On
   php_flag display_startup_errors Off
   php_flag log_errors On
   php_flag mail.add_x_header Off
   php_value session.cookie_httponly "1"
   php_value session.cookie_secure "1"
   php_flag session.use_strict_mode On
   php_value max_input_time 360
   php_value memory_limit 1024M
   php_value max_input_vars 10000
</IfModule>
<IfModule lsapi_module>
   php_flag short_open_tag On
   php_value max_execution_time 1000
   php_value variables_order "EGPCS"
   php_value upload_max_filesize 200M
   php_value post_max_size 200M
   php_value date.timezone "Europe/Lisbon"
   #php_value error_reporting "E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT"
   php_value error_reporting 22519
   php_flag display_errors On
   php_flag display_startup_errors Off
   php_flag log_errors On
   php_flag mail.add_x_header Off
   php_value session.cookie_httponly "1"
   php_value session.cookie_secure "1"
   php_flag session.use_strict_mode On
   php_value max_input_time 360
   php_value memory_limit 1024M
   php_value max_input_vars 10000
</IfModule>
# END cPanel-generated php ini directives

# BEGIN cPanel-generated handler
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php72___lsphp .php .php7 .phtml
</IfModule>
# END cPanel-generated handler

<IfModule mod_rewrite.c>
   RewriteEngine on
   
   RewriteRule ^$ app/ [L,NC]
   
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteRule (.*) app/$1 [L,NC]
</IfModule>
```
5. Confirm that cPanel correctly modified the `public_html/php.ini` file. Otherwise, apply the necessary changes. Here is an example:
```
; cPanel-generated php ini directives
short_open_tag = On
max_execution_time = 1000
variables_order = "EGPCS"
upload_max_filesize = 200M
post_max_size = 200M
date.timezone = "Europe/Lisbon"
error_reporting = "E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT"
#error_reporting = 22519
#error_reporting = "E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT"
display_errors = On
display_startup_errors = Off
log_errors = On
expose_php = Off
mail.add_x_header = Off
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
allow_url_fopen = Off
allow_url_include = Off
max_input_time = 360
memory_limit = 1024M
max_input_vars = 10000
suhosin.get.max_vars = 10000
suhosin.post.max_vars = 10000
suhosin.request.max_vars = 10000
```
6. Confirm that cPanel correctly modified the `public_html/user.ini` file. Otherwise, apply the necessary changes. Here is an example:
```
; cPanel-generated php ini directives
short_open_tag = On
max_execution_time = 1000
variables_order = "EGPCS"
upload_max_filesize = 200M
post_max_size = 200M
date.timezone = "Europe/Lisbon"
error_reporting = "E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT"
#error_reporting = 22519
display_errors = On
display_startup_errors = Off
log_errors = On
mail.add_x_header = Off
session.cookie_httponly = "1"
session.cookie_secure = "1"
session.use_strict_mode = On
max_input_time = 360
memory_limit = 1024M
max_input_vars = 10000
```
7. Bloxtor allows installation without a database, but for a full setup, you should create one. Log in to cPanel, navigate to the Databases section, and create a new database and a corresponding user. Alternatively, if your existing database user has root permissions, you can skip manual creation; Bloxtor will create the database automatically during the setup if selected.
8. Open your web browser and navigate to `http://your.installation.domain/setup.php`. Follow the on-screen instructions, which correspond to the setup step `9` described above.
9. To unlock **Bloxtor's full potential**, please install the **Modules available in our Store**. 
The **User module** is crucial, as it allows you to convert any web-app with private access.

	**To Install the Modules:**
	- Log in to Bloxtor.
	- Click Settings (top-right corner).
	- Select Manage Modules.
	- Click Install New Module.
	- Select all modules, and finally, press the Install button in the top-right corner.


---

## Configure Cronjobs

### On Local PC

If apply, for each project, add the following cronjobs (in case you have the workerpool module installed):
```
* * * * * sudo -u www-data php <absolute path to framework>/app/layer/presentation/<project_name>/webroot/script.php  --documentroot="<absolute path to framework>/" --url="http://<project_url>/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3
```

Example:
```
sudo crontab -e
```

Then:
```
* * * * * sudo -u www-data php /var/www/html/livingroop/default/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/default/" --url="http://jplpinto.localhost/condo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3

* * * * * sudo -u www-data php /var/www/html/livingroop/demo/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/demo/" --url="http://jplpinto.localhost/demo/condo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3

0 2 * * * sudo -u www-data php /var/www/html/livingroop/default/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/default/" --url="http://jplpinto.localhost/condo/script/purge_old_data" --urlpath="script/purge_old_data" --loglevel=3
```

### On Shared Hosting Server

Go to cPanel and add the cronjobs:
```
#with php.ini
* * * * * /usr/local/bin/php -c /home/xxx/public_html/php.ini /home/xxx/public_html/bloxtor/app/layer/presentation/project_yyy/webroot/script.php --documentroot="/home/xxx/public_html/bloxtor/" --scriptname="/app/layer/presentation/project_yyy/webroot/index.php" --url="https://client_xxx.clients.bloxtor.com/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3

#without php.ini
* * * * * php /home/xxx/public_html/bloxtor/app/layer/presentation/project_yyy/webroot/script.php --documentroot="/home/xxx/public_html/bloxtor/" --scriptname="/app/layer/presentation/project_yyy/webroot/index.php" --url="https://client_xxx.clients.bloxtor.com/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3

#here is an example:
/usr/local/bin/php -c /home/diferma1/public_html/php.ini /home/diferma1/public_html/bloxtor/app/layer/presentation/eerp/webroot/script.php --documentroot="/home/diferma1/public_html/bloxtor/" --scriptname="/app/layer/presentation/eerp/webroot/index.php" --url="https://ipcm.clients.bloxtor.com/eerp/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3

#here is another example:
php /home/diferma1/public_html/bloxtor/app/layer/presentation/eerp/webroot/script.php  --documentroot="/home/diferma1/public_html/bloxtor/" --scriptname="/app/layer/presentation/eerp/webroot/index.php" --url="http://ipcm.clients.bloxtor.com/eerp/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3
```


