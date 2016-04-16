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

##: Check the timestamp on this upgrade script so we can detect changes
UPDSTARTDATE=`stat -c %Y $CARTROOT/bin/upgrade.sh | sed 's/upgrade.sh//'`

##: Get a datestamp
BAKDATE=`date +'%Y%m%d%s'`

##: Go to the temp folder
cd /tmp

##: Find cart install
export CARTROOT=`echo "<?echo rtrim(get_cfg_var('cartulary_conf'), '/');?>" | php`

##: Grab the current repo and extract it
clear
echo
echo '############################################################'
echo '##----------------------------------------------------------'
echo '##                                                          '
echo "##  Grabbing the current $BRANCH release.                   "
echo '##                                                          '
echo '##----------------------------------------------------------'
echo '############################################################'
echo
rm $BRANCH.zip
wget https://github.com/daveajones/cartulary/archive/$BRANCH.zip
unzip $BRANCH.zip

##: Stop cron
stop cron

##: Kill running jobs
killall php

##: Back up the existing install
tar -zcvf ~/cartulary-bak-$BAKDATE.tar.gz $CARTROOT

##: Get into the repo folder
cd cartulary-$BRANCH

##: Backup newuser sub list
cp $CARTROOT/www/newuser.opml /tmp

##: Put new files in place
cp -R aggrivate $CARTROOT/aggrivate
cp -R aggrivate/* $CARTROOT/aggrivate
cp -R bin/* $CARTROOT/bin
cp -R includes/* $CARTROOT/includes
cp -R libraries/* $CARTROOT/libraries
cp -R scripts/* $CARTROOT/scripts
cp -R templates/* $CARTROOT/templates
cp -R releases/* $CARTROOT/releases
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

##: Run supplemental version scripts
$CARTROOT/releases/

##: Run confcheck
clear
echo
echo '############################################################'
echo '##----------------------------------------------------------'
echo '##                                                          '
echo '##  Upgrade cartulary.conf file.                            '
echo '##                                                          '
echo '##  - If you mess up here, just run:                        '
echo "##    > sudo php $CARTROOT/bin/confcheck.php upgrade        "
echo '##    after the upgrade finishes.                           '
echo '##                                                          '
echo '##----------------------------------------------------------'
echo '############################################################'
echo
php $CARTROOT/bin/confcheck.php upgrade silent

##: Check the database version
php $CARTROOT/bin/dbcheck.php

##: Restart cron daemon
echo
start cron

##: Run any side-scripts that were shipped with this version
php $CARTROOT/bin/sidegrade.php

service apache2 restart

echo
echo 'Upgrade is finished.'

##: Check timestamp again for the upgrade script
UPDENDDATE=`stat -c %Y $CARTROOT/bin/upgrade.sh | sed 's/upgrade.sh//'`
if [ "$UPDENDDATE" -ne "$UPDSTARTDATE" ] ; then
    echo "A new version of this upgrade script was just installed.  You should run the upgrade again right now."
fi