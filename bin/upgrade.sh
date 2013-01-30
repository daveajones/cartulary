#!/bin/bash

##: This script needs to be run with sudo

##: Get a datestamp
BAKDATE=`date +'%Y%m%d%s'`

##: Go to the temp folder
cd /tmp

##: Grab the current repo and extract it
clear
echo
echo '############################################################'
echo '##----------------------------------------------------------'
echo '##                                                          '
echo '##  Grabbing the current release.                           '
echo '##                                                          '
echo '##----------------------------------------------------------'
echo '############################################################'
echo
wget https://github.com/daveajones/cartulary/archive/master.zip
unzip master.zip

##: Back up the existing install
tar -zcvf ~/cartulary-bak-$BAKDATE.tar.gz /opt/cartulary

##: Get into the repo folder
cd cartulary-master

##: Backup newuser sub list
cp /opt/cartulary/www/newuser.opml /tmp

##: Put new files in place
cp -R bin/* /opt/cartulary/bin
cp -R includes/* /opt/cartulary/includes
cp -R libraries/* /opt/cartulary/libraries
cp -R scripts/* /opt/cartulary/scripts
cp -R templates/* /opt/cartulary/templates
cp -R www/* /opt/cartulary/www

##: Restore newuser sub list
cp /tmp/newuser.opml /opt/cartulary/www

##: Get out of the repo
cd ..

##: Kill it
rm -rf cartulary-master/
rm master.zip

##: Run confcheck
clear
echo
echo '############################################################'
echo '##----------------------------------------------------------'
echo '##                                                          '
echo '##  Upgrade cartulary.conf file.                            '
echo '##                                                          '
echo '##  - If you mess up here, just run:                        '
echo '##    > sudo php /opt/cartulary/bin/confcheck.php upgrade   '
echo '##    after the upgrade finishes.                           '
echo '##                                                          '
echo '##----------------------------------------------------------'
echo '############################################################'
echo
php /opt/cartulary/bin/confcheck.php upgrade

##: Restart cron daemon
echo
restart cron
