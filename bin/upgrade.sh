#!/bin/bash

##: This script needs to be run with sudo

##: Get a datestamp
BAKDATE=`date +'%Y%m%d%s'`

##: Go to the temp folder
cd $TEMP

##: Grab the current repo and extract it
wget https://github.com/daveajones/cartulary/archive/master.zip
unzip master.zip

##: Back up the existing install
tar -zcvf ~/cartulary-bak-$BAKDATE.tar.gz /opt/cartulary

##: Get into the repo folder
cd cartulary-master

##: Put new files in place
cp -R bin/* /opt/cartulary/bin
cp -R includes/* /opt/cartulary/includes
cp -R libraries/* /opt/cartulary/libraries
cp -R scripts/* /opt/cartulary/scripts
cp -R templates/* /opt/cartulary/templates
cp -R www/* /opt/cartulary/www

##: Get out of the repo
cd ..

##: Kill it
rm -rf cartulary-master/
rm master.zip

