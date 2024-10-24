#!/bin/bash
#sudo /bin/bash /var/www/html/phpframework/trunk/other/script/set_perms.sh /var/www/html/phpframework/trunk/

TRUNK_PATH=$1
APACHE_USER=www-data

#change users if this script runs inside of the CentOS-VM in the Ruption VirtualBox
if id "apache" &>/dev/null
then
	APACHE_USER=apache
fi

if [ "$TRUNK_PATH" != "" ] && [ -d "$TRUNK_PATH" ] && [ -d "$TRUNK_PATH/app" ]
then
	if [ ! -d "$TRUNK_PATH/app/__system/layer/presentation/phpframework/webroot/__system/" ]
	then
		mkdir "$TRUNK_PATH/app/__system/layer/presentation/phpframework/webroot/__system/"
	fi
	
	if [ ! -d "$TRUNK_PATH/app/__system/layer/presentation/test/webroot/__system/" ]
	then
		mkdir "$TRUNK_PATH/app/__system/layer/presentation/test/webroot/__system/"
	fi
	
	chmod -f 777 $TRUNK_PATH/app/config/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/layer/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/lib/vendor/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/config/global_settings.php 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/config/global_variables.php 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/common/src/module/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/common/webroot/module/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/common/webroot/__system/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/common/webroot/vendor/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/phpframework/src/config/authentication.php
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/phpframework/webroot/vendor/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/phpframework/webroot/__system/ 2>&1
	chmod -f 777 $TRUNK_PATH/app/__system/layer/presentation/test/webroot/__system/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/dao/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/codeworkfloweditor/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/codeworkfloweditor/task/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/layoutuieditor/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/layoutuieditor/widget/ 2>&1
	chmod -f 777 $TRUNK_PATH/vendor/testunit/ 2>&1
	chmod -f 777 $TRUNK_PATH/other/authdb/ 2>&1
	chmod -f 777 $TRUNK_PATH/other/workflow/ 2>&1
	chmod -f 777 $TRUNK_PATH/tmp/ 2>&1
	chmod -f 777 $TRUNK_PATH/files/ 2>&1
	
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/config/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/layer/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/layer/.htaccess 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/other/authdb/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/other/workflow/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/__system/layer/presentation/common/src/module/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/__system/layer/presentation/common/webroot/module/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/__system/layer/presentation/phpframework/webroot/__system/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/__system/layer/presentation/test/webroot/__system/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/vendor/dao/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/vendor/codeworkfloweditor/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/vendor/layoutuieditor/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/vendor/testunit/* 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/tmp/* 2>&1
	
	#dependecies
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/__system/layer/presentation/common/webroot/vendor/ckeditor/ 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/__system/layer/presentation/common/webroot/vendor/tinymce/ 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/lib/vendor/phpjavascriptpacker/ 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/lib/vendor/phpmailer/ 2>&1
	chown -fR $APACHE_USER:$APACHE_USER $TRUNK_PATH/app/lib/vendor/xsssanitizer/ 2>&1
	
	#delete tmp files in /tmp if exists
	rm -rf /tmp/phpframework/ 2>&1
	
	#delete cache of trunk path if exists
	rm -rf $TRUNK_PATH/tmp/cache/ 2>/dev/null
	rm -rf $TRUNK_PATH/app/__system/layer/presentation/phpframework/webroot/__system/* 2>/dev/null
	rm -rf $TRUNK_PATH/app/__system/layer/presentation/test/webroot/__system/* 2>/dev/null

	#delete cache in centos
	rm -rf /var/www/html/phpframeworksrc/*/tmp/cache/ 2>/dev/null

	#delete cache in ubuntu
	rm -rf /var/www/html/livingroop/*/tmp/cache/ 2>/dev/null
else
	echo "ERROR: TRUNK_PATH invalid!";
fi

