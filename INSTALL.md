# Bloxtor installation steps

## 1. Install Web-server with PHP
Install web-server (eg: Apache) and PHP 5.6 or higher (Bloxtor was tested until PHP 8.4).
If you wish you can install also the Mysql and Postgres servers.

.
> If you are a sysadmin or are not sure what you are doing, please do not execute the optional steps in this tutorial.

.
### PHP Modules
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
### (optional) Install MS SQL Server module
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

---

## 2. Web Server configurations
### Enable mod rewrite
Please be sure that your Web Server has the mod_rewrite enable and the php.ini files are well configured.

In linux, to enable the mod_rewrite in web-server (Apache), try to execute this command: 
```
sudo a2enmod rewrite
```
Please be sure that the rewrite mod is really enabled, otherwise the framework redirects in the .htaccess won't work.

.
### Enable AllowOverride
Note that you must have your web-server configured to read the framework .htaccess files. 
Please be sure that you have the following settings in your vhost conf file:
```
AllowOverride All

#(optional) in case you have symbolic links in your root directory
Options FollowSymLinks
```

.
### Set document root
Configure web-server document root to the absolute_path_to_framework/ folder in your vhost conf file, like the example below where absolute_path_to_framework is "/var/www/html/bloxtor/":
```
<VirtualHost *:80>
  #...
  
  DocumentRoot /var/www/html/bloxtor/
  
  #...
</VirtualHost>
```

.
### (optional) Edit mod security settings
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
### Exemplary vhost conf file
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
### Edit PHP.ini
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

---

## 3. If Mysql is installed
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

---

## 4. If your OS is CentOS
On CentOS, web-server probably has external network connections blocked, which doesn't allow MySQL to connect to databases.
To check if this is OFF, please type the following commands:
```
sudo getsebool -a | grep httpd_can_network
```

If the httpd_can_network_connect is OFF, you should enable them by typing:
```
sudo setsebool -P httpd_can_network_connect 1
```

---

## 5. Set permissions
Open script absolute_path_to_framework/other/script/set_perms.sh and edit the APACHE_USER var with the right user for your web-server.
Then execute this script:
```
sudo /bin/bash <absolute path to framework>/other/script/set_perms.sh <absolute path to framework>
```

---

## 6. (optional) TMP folder
By default, the framework already have a tmp folder in absolute_path_to_framework/tmp, which will be detected automatically.

If you wish to have a different TMP folder, you can create that folder and configure it in the global variables (absolute_path_to_framework/app/config/global_variables.php and/or absolute_path_to_framework/app/__system/config/global_variables.php). 
In this case please don't forget to give write permission to web-server for that folder.

Additionally please remove all the temporary files inside of your TMP folder. By default the TMP folder that is in absolute_path_to_framework/tmp is already empty. In case you are running the setup.php again, please erase the files inside of this folder, except the .htaccess file.

---

## 7. Other Notes:
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

---

## 8. Restart web-server
Restart web-server and check if it was started without errors.

---

## 9. Open the setup.php in your browser
Open your browser, type htttp://your.installation.domain/setup.php, then follow the correspondent instructions...

> The setup.php file is in the absolute_path_to_framework/app/ folder, but the absolute_path_to_framework/.htaccess file will redirect the 'htttp://your.installation.domain/setup.php' to the app folder, so don't worry... If this doesn't happen, it means you didn't activate the web-server rewrite mod.

---

## 10. (optional) Cronjobs
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
