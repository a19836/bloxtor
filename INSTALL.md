# Bloxtor installation steps

1. install apache, php 5.6 or higher (tested until PHP 8.4)

2. Then install confirm if you have the following php modules installed:
	`bcmath (is installed by default)`
	`bz2 (is installed by default)`
	`ctype (is installed by default)`
	`curl (is installed by default)`
	`dom (is installed by default)`
	`date (is installed by default)`
	`exif (is installed by default)`
	`fileinfo (is installed by default)`
	`filter (is installed by default)`
	`ftp (is installed by default)`
	`gd`
	`hash (is installed by default)`
	`imap (is installed by default)`
	`intl (is installed by default)`
	`json (is installed by default)`
	`libxml (is installed by default)`
	`mbstring (is installed by default)`
	`memcache`
	`mongodb`
	`mysqli (is installed by default)`
	`odbc (is installed by default)`
	`openssl (is installed by default)`
	`pcre (is installed by default)`
	`pdo`
	`pdo_mysql`
	`pdo_odbc`
	`pdo_pgsql`
	`pdo_sqlite`
	`pgsql`
	`posix (is installed by default - optional)`
	`reflection (is installed by default)`
	`session (is installed by default)`
	`simplexml (is installed by default)`
	`sqlite3`
	`soap (optional)`
	`ssh2`
	`tokenizer (is installed by default)`
	`xml (is installed by default)`
	`xmlreader (is installed by default)`
	`xmlrpc (is installed by default)`
	`xmlwriter (is installed by default)`
	`xsl (is installed by default)`
	`zend cache (is installed by default)`
	`zip (is installed by default)`
	`zlib (is installed by default)`

	If some module is missing you need to execute the command bellow in Linux to install the following packages:
		`sudo apt-get/yum install php-common php-cli php-bcmath php-curl php-gd php-mbstring php-mysql/php-mysqlnd php-pgsql php-xml php-ssh2 php-json`

	(optional) If you wish to install other extra packages please run: `sudo apt-get/yum install php-soap php-opcache php-dbg php-process php-odbc php-pdo php-fpm php-dba php-dbg`
	
	(optional) If you wish to connect to mssql-server, please install the "mssql-server" package. If you are not able to install this package on linux os, please follow the tutorials in order to install the odbc drivers for mssql-server:
	- https://docs.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server?view=sql-server-ver15
	- https://www.easysoft.com/developer/languages/php/sql_server_unix_tutorial.html
	- https://www.easysoft.com/products/data_access/odbc-sql-server-driver/manual/installation.html#852113
	After your odbc driver be installed, it should be present in the file: /etc/odbc.ini, otherwise add the following lines:
		`[ODBC Driver 17 for SQL Server]`
		`Description=Microsoft ODBC Driver 17 for SQL Server`
		`Driver=/opt/microsoft/msodbcsql17/lib64/libmsodbcsql-17.7.so.2.1`
		`UsageCount=1`
	Note that the Driver path should be to your driver.

3. Please be sure that your WebServer has the mod_rewrite enable and the php.ini files are well configured.

	In linux, to enable the mod_rewrite in apache, try to execute this command: 
		`sudo a2enmod rewrite`
	
	Note that you must have your apache (or web server) configured to read the htaccess settings, having the following options in your vhost:
		Options FollowSymLinks
		AllowOverride All
	
	(optional) For security and performance reasons, we recommend you to update your php.ini files with:

	`short_open_tag = On`
	`max_execution_time = 1000`
	`variables_order = "EGPCS"`
	`upload_max_filesize = 150M`
	`post_max_size = 150M`
	`date.timezone = Europe/Lisbon`

	`open_basedir = "<your cms installation dir>"`
	`sys_temp_dir = "<your cms installation dir>/tmp"`
	`upload_tmp_dir = "<your cms installation dir>/tmp"`
	`session.save_path = "<your cms installation dir>/tmp"`
	`soap.wsdl_cache_dir = "<your cms installation dir>/tmp"`

	`error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT`
	`display_errors = Off`
	`display_startup_errors = Off`
	`log_errors = On`

	`expose_php = Off`
	`mail.add_x_header = Off`
	`session.cookie_httponly = On`
	`session.cookie_secure = On`
	`session.use_strict_mode = On`
	`allow_url_fopen = Off`
	`allow_url_include = Off`

	`disable_functions = dl,pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,exec,shell_exec,passthru,system,proc_open,popen,parse_ini_file,show_source	`

	(optional) And if possible this too (but only if you get request body limit exceed):
		`max_input_time = 360`
		`memory_limit = 1024M`
		`max_input_vars = 10000`
		`suhosin.get.max_vars = 10000 (if apply)`
		`suhosin.post.max_vars = 10000 (if apply)`
		`suhosin.request.max_vars = 10000 (if apply)`

	Note that, if you decide to follow our recommendation, you should make these changes in the php.ini from apache and cli mode. Usually these files are located in:
	- /etc/php/apache2/php.ini
	- /etc/php/cli/php.ini

4. (optional) Then go to your /etc/mysql/my.cnf and add the following line:
	`[mysqld]`
	`#if mysql version < 8`
	`sql-mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"`

	`#if mysql version >= 8`
	`sql-mode="ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"`

	`max_allowed_packet=250M`
	`wait_timeout=28800`

	`[mysqld_safe]`
	`max_allowed_packet=100M`

	`[client]`
	`max_allowed_packet=100M`

	`[mysql]`
	`max_allowed_packet=100M`

	`[mysqldump]`
	`max_allowed_packet=100M`

5. (optional) Change the correspondent apache security configurations, if active (/etc/modsecurity/modsecurity.conf) - (Only if you get request body limit exceed)
	`#to 32MB`
	`SecRequestBodyLimit 32768000`

	`#to 640KB`
	`SecRequestBodyNoFilesLimit 655360`

	`#to 16MB`
	`SecRequestBodyInMemoryLimit 16384000`

	`#to 32MB`
	`SecResponseBodyLimit 32768000`

6. (optional) Change the following apache vhost configurations if apply (this is, inside of your virtual-host configuration add the following lines):
    `LimitInternalRecursion 100`
    `LimitRequestBody 0`
    `LimitRequestFields 10000000`
    `LimitRequestFieldSize 10000000`
    `LimitRequestLine 10000000`
    `LimitXMLRequestBody 10000000`

7. In CentOS is probably that the apache has the external network connections blocked which doesn't allow the mysql connect with the DBs. 
To check if this is OFF please type the following commands:
	`sudo getsebool -a | grep httpd_can_network`

	If the httpd_can_network_connect is OFF, you should enable them by typing:
		`sudo setsebool -P httpd_can_network_connect 1`

8. Then execute the script: `<absolute path to framework>/other/script/set_perms.sh <absolute path to framework>`

9. Configure apache document root to the <absolute path to framework>/app/ folder

10. Restart apache and open the setup.php (htttp://<your installation domain>/setup.php) file in your browser and follow instructions... The setup.php file is in the <absolute path to framework>/app/ folder.

11. Note that if you wish to have a local TMP folder, you can create a TMP folder inside of the dirname of the app/ folder and the system will detect it automatically, or you can always set another TMP folder in the global variables.

12. Then (if apply) for each project, add the cronjobs:
	`* * * * * sudo -u www-data php <absolute path to framework>/app/layer/presentation/<project_name>/webroot/script.php  --documentroot="<absolute path to framework>/" --url="http://<project_url>/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3`

	Example:
		`sudo crontab -e`

	   then:
	   	`* * * * * sudo -u www-data php /var/www/html/livingroop/default/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/default/" --url="http://jplpinto.localhost/condo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3`
	   
	   	`* * * * * sudo -u www-data php /var/www/html/livingroop/demo/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/demo/" --url="http://jplpinto.localhost/demo/condo/module/workerpool/run_worker_pool_script" --urlpath="module/workerpool/run_worker_pool_script" --loglevel=3`

			`0 2 * * * sudo -u www-data php /var/www/html/livingroop/default/app/layer/presentation/condo/webroot/script.php  --documentroot="/var/www/html/livingroop/default/" --url="http://jplpinto.localhost/condo/script/purge_old_data" --urlpath="script/purge_old_data" --loglevel=3`

13. Other Notes:
	This framework uses some external libraries (with LGPL licences) to improve their usability and add new functionalities, which means that if you wish to have all features, you should installed them, but this is optional.
	When you run the framework installation script (through the url: "<your domain>/setup.php"), you will be prompted to install them or not.

	The external libraries, with LGPL and GPL licences, are:
	- *phpjavascriptpacker*: that should be copied to the app/lig/vendor/ folder;
	- *phpmailer*: that should be copied to the app/lig/vendor/ folder;
	- *xsssanitizer*: that should be copied to the app/lig/vendor/ folder;

	- *ckeditor*: that should be copied to the app/__system/layer/presentation/common/webroot/vendor/ and app/layer/presentation/common/webroot/vendor/ folders;
	- *tinymce*: that should be copied to the app/__system/layer/presentation/common/webroot/vendor/ and app/layer/presentation/common/webroot/vendor/ folders;

	The external libraries, with GPL licences, are:
	- *wordpress* (wordpress.zip): that should be copied to the app/__system/layer/presentation/phpframework/webroot/vendor/ folder;

	
