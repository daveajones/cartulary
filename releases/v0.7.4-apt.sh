#!/bin/bash

##: This file should be EXEcutable!

##: This script needs to be run with sudo
ROOT_UID="0"

#Check if run as root
if [ "$UID" -ne "$ROOT_UID" ] ; then
	echo "You must run this script with sudo."
	exit 1
fi
export CARTROOT=`echo "<?echo rtrim(get_cfg_var('cartulary_conf'), '/');?>" | php`

echo Side-loading packages up through version 0.6.10...

##: Stop aggrivate
killall node

##: Refresh apt
echo Updating apt-get repos...
apt-get update

##: Install any new modules needed for this release
echo Installing php5 imap library...
apt-get install -y php5-imap
apt-get install -y php-imap
php5enmod imap
phpenmod imap

echo Installing php5 xsl library...
apt-get install -y php5-xsl
apt-get install -y php-xsl
php5enmod xsl
phpenmod xsl

echo Installing node.js...
curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -
apt-get install -y nodejs
cd $CARTROOT/aggrivate
sudo npm -g install npm@latest
sudo npm -g install npm@latest
npm install

echo Correcting feed database prior to upgrade...
echo "  This could take a while.  Please wait..."
sudo php /opt/cartulary/bin/clean_nonfqdn_feeds.php
sudo php /opt/cartulary/bin/clean_duplicate_feeds.php

echo Running a full aggregator pass...
echo "  This could take a while.  Please wait..."
cd $CARTROOT/aggrivate
sudo node ./aggrivate.js checkall checkdead force
