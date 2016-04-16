#!/bin/bash

##: This script needs to be run with sudo
ROOT_UID="0"

#Check if run as root
if [ "$UID" -ne "$ROOT_UID" ] ; then
	echo "You must run this script with sudo."
	exit 1
fi
export CARTROOT=`echo "<?echo rtrim(get_cfg_var('cartulary_conf'), '/');?>" | php`

echo Side-loading packages up through version 0.6.10...

##: Refresh apt
echo Updating apt-get repos...
apt-get update

##: Install any new modules needed for this release
echo Installing php5 imap library...
apt-get install -y php5-imap
php5enmod imap

echo Installing php5 xsl library...
apt-get install -y php5-xsl
php5enmod xsl

echo Installing node.js...
curl -sL https://deb.nodesource.com/setup | sudo bash -
apt-get install -y nodejs
cd $CARTROOT/aggrivate
sudo npm -g install npm@latest
sudo npm -g install npm@latest
npm install


##: This file should be EXEcutable!