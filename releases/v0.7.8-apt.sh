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

##: Do a user check pass to pick up any user level config needed
sudo php /opt/cartulary/bin/usercheck.php

echo Side-loading packages up through version 0.6.10...

##: Refresh apt
echo Updating apt-get repos...
apt-get update -qq

echo Installing node.js...
NODEVER=`node --version`
if [[ "$NODEVER" == *"v4"* ]] ; then
        echo "You're on an old version of node. Upgrading..."
        sudo apt-get remove -y node nodejs
        curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
        sudo apt-get install -y nodejs
fi
cd $CARTROOT/aggrivate
sudo rm -rf node_modules/
sudo rm package-lock.json
sudo npm -g install npm@latest
sudo npm -g install npm@latest
sudo npm install --unsafe-perm=true --allow-root

echo Correcting feed database...
echo "  This could take a while since it checks the whole feed table.  Please wait..."
sudo php /opt/cartulary/bin/clean_nonfqdn_feeds.php
sudo php /opt/cartulary/bin/clean_duplicate_feeds.php

echo Running a full aggregator pass...
echo "  This could take a while since it checks error/dead feeds too.  Please wait..."
cd $CARTROOT/aggrivate
sudo node ./aggrivate.js checkerror force
sudo node ./aggrivate.js checkdead force
suod node ./aggrivate.js force