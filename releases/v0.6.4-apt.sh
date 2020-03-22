#!/bin/bash

##: This script needs to be run with sudo
ROOT_UID="0"

#Check if run as root
if [ "$UID" -ne "$ROOT_UID" ] ; then
	echo "You must run this script with sudo."
	exit 1
fi
export CARTROOT=`echo "<?echo rtrim(get_cfg_var('cartulary_conf'), '/');?>" | php`

echo Side-loading packages up through version 0.6.4...

##: Refresh apt
apt-get update

##: Install any new modules needed for this release
apt-get install -y php5-imap
php5enmod imap