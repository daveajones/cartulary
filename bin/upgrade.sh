#!/bin/bash

##: This script needs to be run with sudo
ROOT_UID="0"

#Check if run as root
if [ "$UID" -ne "$ROOT_UID" ] ; then
	echo "You must run this script with sudo."
	exit 1
fi

##: Which branch to upgrade from?
BRANCH="master"
if [ $# -gt 0 ] ; then
	BRANCH="$1"
fi

##: Get a datestamp
BAKDATE=`date +'%Y%m%d%s'`

##: Go to the temp folder
cd /tmp

##: Find cart install
export CARTROOT=`echo "<?echo rtrim(get_cfg_var('cartulary_conf'), '/');?>" | php`

##: Grab the current repo and extract it
clear
##: Check the hash on this upgrade script so we can detect changes
export UPDOLDHASH=`md5sum $CARTROOT/bin/upgrade.sh | awk '{ print $1 }'`

if [ -e "$BRANCH.zip" ] ; then
    rm $BRANCH.zip
fi
wget -nv https://github.com/daveajones/cartulary/archive/$BRANCH.zip

unzip -q $BRANCH.zip

##: Stop cron
service cron stop 

##: Kill running jobs
killall php >/dev/null 2>/dev/null

##: Back up the existing install
tar -zcf ~/cartulary-bak-$BAKDATE.tar.gz $CARTROOT

##: Get into the repo folder
cd cartulary-$BRANCH

##: Backup newuser sub list
cp $CARTROOT/www/newuser.opml /tmp

##: Put new files in place
cp -R aggrivate $CARTROOT/aggrivate
cp -R aggrivate/* $CARTROOT/aggrivate
cp -R bin/*.php $CARTROOT/bin
cp bin/cartlog $CARTLOG/bin
cp bin/upgrade.sh $CARTROOT/bin/new-upgrade.sh
cp -R includes/* $CARTROOT/includes
cp -R libraries/* $CARTROOT/libraries
cp -R scripts/* $CARTROOT/scripts
cp -R templates/* $CARTROOT/templates
cp -R releases $CARTROOT
cp -R www/* $CARTROOT/www

##: Set permissions
touch $CARTROOT/logs/error.log
touch $CARTROOT/logs/debug.log
touch $CARTROOT/logs/access.log
chown www-data $CARTROOT/logs >>/tmp/cartinstall.log 2>&1
chown www-data $CARTROOT/logs/* >>/tmp/cartinstall.log 2>&1
chown www-data $CARTROOT/spool >>/tmp/cartinstall.log 2>&1
chmod +x $CARTROOT/releases/v*-apt.sh

##: Restore newuser sub list
cp /tmp/newuser.opml $CARTROOT/www

##: Get out of the repo
cd ..

##: Kill it
rm -rf cartulary-$BRANCH/
rm $BRANCH.zip

##: Run confcheck
php $CARTROOT/bin/confcheck.php upgrade silent

##: Check the database version
php $CARTROOT/bin/dbcheck.php

##: Restart cron daemon
service cron start

##: Run any side-scripts that were shipped with this version
php $CARTROOT/bin/sidegrade.php

service apache2 restart

##: ----- Update this script -----
export UPDNEWHASH=`md5sum $CARTROOT/bin/new-upgrade.sh | awk '{ print $1 }'`

##: Check hash again for the upgrade script
echo "Update is finished."
if [ "$UPDOLDHASH" != "$UPDNEWHASH" ] ; then
    echo
    echo
    echo
    echo " **** A new version of this upgrade script was just installed.  You should run the upgrade again right now. **** "
    echo
    echo
    echo
fi
mv $CARTROOT/bin/new-upgrade.sh $CARTROOT/bin/upgrade.sh