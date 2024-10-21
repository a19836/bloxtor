#!/bin/bash
#execute this script with sudo otherwise the chown command won't work!
#	sudo bash /home/jplpinto/Desktop/phpframework/trunk/other/script/update_git_repo.sh /var/www/html/phpframeworkrepo/

DEST=$1
#DEST=`dirname $(dirname $(dirname "$0"))` #must be the trunk file with the subfolder: app

#Check if script is being running as root
if [ "$EUID" -ne 0 ]
	then echo "Please run as root"
	exit 1
fi

NOW=`/bin/date "+%Y-%m-%d %H:%M"`
#CURRENT_USERNAME=$(stat -c '%U' "$0")

if [ "$DEST" != "" ] && [ -d "$DEST/app" ]
then
	echo "Preparing Framework folder"
	/bin/chown -R $SUDO_USER:$SUDO_USER "$DEST"
	
	echo "Undo all previous changes"
	/bin/git checkout $DEST/*
	
	echo "Get all new changes"
	/bin/git pull $DEST
	
	echo "Setting permissions"
	/bin/bash "$DEST/other/script/set_perms.sh" "$DEST"
else
	NOW=`date`;
	echo "ERROR - Framework folder is mandatory and must exists - date: $NOW";
	exit 1;
fi

echo 

if [ $? != "0" ] 
then
	NOW=`date`;
	echo "ERROR - date: $NOW";
	echo $?
else
	echo "OK";
fi

echo
