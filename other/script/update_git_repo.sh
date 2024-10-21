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
	#lists changed files from git
	WERE_FILES_CHANGED=`/bin/git ls-files -m | wc -l`
	CONTINUE=1
	
	if [ $WERE_FILES_CHANGED -gt 0 ]
	then
		echo "Some of the system files were changed by you. If you continue the following files will be replaced by the original files:";
		/bin/git ls-files -m
		
		echo
		read -p "Do you wish to continue? Y/N (N): " ANSWER;
		ANSWER=$(echo $ANSWER | tr 'a-z' 'A-Z')
		
		if [ "$ANSWER" != "Y" ] && [ "$ANSWER" != "YES" ]
		then
			CONTINUE=0
			echo "Update stopped!";
		fi
	fi
	
	if [ $CONTINUE -eq 1 ]
	then
		echo
		echo "* START UPDATING... *";
		
		echo "  - Preparing Framework folder Permissions"
		/bin/chown -R $SUDO_USER:$SUDO_USER "$DEST"
		
		echo "  - Undo all previous changes"
		/bin/git checkout $DEST/*
		
		echo "  - Get all new changes"
		/bin/git pull $DEST
		
		echo "  - Preparing Framework folder Permissions"
		#note that all updated files will be root owner be the git commands were executed with sudo user. so we need to set all files owner to original owner
		/bin/chown -R $SUDO_USER:$SUDO_USER "$DEST"
		
		echo "  - Setting permissions"
		/bin/bash "$DEST/other/script/set_perms.sh" "$DEST"
		
		echo "* END UPDATING... *";
	fi
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
