<?php
//########################################################################################
// API for managing database schema
//########################################################################################


//A list of database schema updates for each version
$cg_database_version = 47;
$cg_database_updates = array();


//----------------------------------------------------------------------------------------------------------------
// Database change statements ------------------------------------------------------------------------------------
//Version 0 to 1 -------------------------------------------------------------------------------------------------
$cg_database_updates[0][] = <<<CGDB0001
 CREATE TABLE IF NOT EXISTS `dbversion` (
  `version` int(11) NOT NULL DEFAULT '0' COMMENT 'The current version.',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When it was applied.',
  PRIMARY KEY (`version`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Track the database schema.'
CGDB0001;
$cg_database_updates[0][] = <<<CGDB0002
 INSERT INTO `dbversion` ( `version` ) VALUES ( '1' )
CGDB0002;
//----------------------------------------------------------------------------------------------------------------

//Version 1 to 2 -------------------------------------------------------------------------------------------------
$cg_database_updates[1][] = <<<CGDB0003
 ALTER TABLE `prefs` ADD `cartinriver` TINYINT NOT NULL DEFAULT '0' COMMENT 'Show cartulized articles in a modal.'
CGDB0003;
$cg_database_updates[1][] = <<<CGDB0004
 INSERT INTO `dbversion` ( `version` ) VALUES ( '2' )
CGDB0004;
//----------------------------------------------------------------------------------------------------------------

//Version 2 to 3 -------------------------------------------------------------------------------------------------
$cg_database_updates[2][] = <<<CGDB0005
 ALTER TABLE `flags` ADD PRIMARY KEY ( `name` )
CGDB0005;
$cg_database_updates[2][] = <<<CGDB0006
 INSERT INTO `dbversion` ( `version` ) VALUES ( '3' )
CGDB0006;
//----------------------------------------------------------------------------------------------------------------

//Version 3 to 4 -------------------------------------------------------------------------------------------------
$cg_database_updates[3][] = <<<CGDB0007
 ALTER TABLE `prefs` ADD `staticarticles` TINYINT NOT NULL DEFAULT '0' COMMENT 'Store a static version of the article?'
CGDB0007;
$cg_database_updates[3][] = <<<CGDB0008
 INSERT INTO `dbversion` ( `version` ) VALUES ( '4' )
CGDB0008;
//----------------------------------------------------------------------------------------------------------------

//Version 4 to 5 -------------------------------------------------------------------------------------------------
$cg_database_updates[4][] = <<<CGDB0009
 ALTER TABLE `catalog` ADD `staticurl` VARCHAR( 767 ) NOT NULL COMMENT 'Url of a static version of the article.'
CGDB0009;
$cg_database_updates[4][] = <<<CGDB0010
 INSERT INTO `dbversion` ( `version` ) VALUES ( '5' )
CGDB0010;
//----------------------------------------------------------------------------------------------------------------

//Version 5 to 6 -------------------------------------------------------------------------------------------------
$cg_database_updates[5][] = <<<CGDB0011
 ALTER TABLE `nfitems` ADD `origin` VARCHAR( 767 ) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL COMMENT 'The origin guid of this item, if any?'
CGDB0011;
$cg_database_updates[5][] = <<<CGDB0012
 INSERT INTO `dbversion` ( `version` ) VALUES ( '6' )
CGDB0012;
//----------------------------------------------------------------------------------------------------------------

//Version 6 to 7 -------------------------------------------------------------------------------------------------
$cg_database_updates[6][] = <<<CGDB0013
 ALTER TABLE `microblog` ADD `origin` VARCHAR( 767 ) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL COMMENT 'Track the origin of each post.'
CGDB0013;
$cg_database_updates[6][] = <<<CGDB0014
 INSERT INTO `dbversion` ( `version` ) VALUES ( '7' )
CGDB0014;
//----------------------------------------------------------------------------------------------------------------

//Version 7 to 8 -------------------------------------------------------------------------------------------------
$cg_database_updates[7][] = <<<CGDB0015
 ALTER TABLE `nfcatalog` ADD `fulltext` TINYINT NOT NULL DEFAULT '0' COMMENT 'Should this feed be full text in the river?'
CGDB0015;
$cg_database_updates[7][] = <<<CGDB0016
 INSERT INTO `dbversion` ( `version` ) VALUES ( '8' )
CGDB0016;
//----------------------------------------------------------------------------------------------------------------

//Version 8 to 9 -------------------------------------------------------------------------------------------------
$cg_database_updates[8][] = <<<CGDB0017
 ALTER TABLE `nfitemprops` ADD `fulltext` TINYINT NOT NULL DEFAULT '0' COMMENT 'Should this item be full text in the river?'
CGDB0017;
$cg_database_updates[8][] = <<<CGDB0018
 INSERT INTO `dbversion` ( `version` ) VALUES ( '9' )
CGDB0018;
//----------------------------------------------------------------------------------------------------------------

//Version 9 to 10 ------------------------------------------------------------------------------------------------
$cg_database_updates[9][] = <<<CGDB0019
 ALTER TABLE `prefs` ADD `collapseriver` TINYINT NOT NULL DEFAULT '0' COMMENT 'Show duplicate origin items in a threaded view?'
CGDB0019;
$cg_database_updates[9][] = <<<CGDB0020
 INSERT INTO `dbversion` ( `version` ) VALUES ( '10' )
CGDB0020;
//----------------------------------------------------------------------------------------------------------------

//Version 10 to 11 -----------------------------------------------------------------------------------------------
$cg_database_updates[10][] = <<<CGDB0021
 ALTER TABLE `sopmlfeeds` ADD `link` VARCHAR( 700 ) NOT NULL COMMENT 'The feed link element.'
CGDB0021;
$cg_database_updates[10][] = <<<CGDB0022
 INSERT INTO `dbversion` ( `version` ) VALUES ( '11' )
CGDB0022;
//----------------------------------------------------------------------------------------------------------------

//Version 11 to 12 -----------------------------------------------------------------------------------------------
$cg_database_updates[11][] = <<<CGDB0023
 CREATE TABLE IF NOT EXISTS `servers` (
  `guid` varchar(64) NOT NULL DEFAULT '' COMMENT 'A globally unique guid.',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT 'When it was applied.',
  PRIMARY KEY (`guid`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A list of known sopml servers.'
CGDB0023;
$cg_database_updates[11][] = <<<CGDB0024
 ALTER TABLE `servers` CHANGE `guid` `guid` VARCHAR( 64 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '' COMMENT 'A globally unique guid.'
CGDB0024;
$cg_database_updates[11][] = <<<CGDB0025
 INSERT INTO `dbversion` ( `version` ) VALUES ( '12' )
CGDB0025;
//----------------------------------------------------------------------------------------------------------------

//Version 12 to 13 -----------------------------------------------------------------------------------------------
$cg_database_updates[12][] = <<<CGDB0026
 ALTER TABLE `sopmlitems` ADD `position` INT NOT NULL DEFAULT '0' COMMENT 'The item''s position in the document.',
 ADD `level` INT NOT NULL DEFAULT '0' COMMENT 'The item''s indentation level.',
 ADD `purge` TINYINT NOT NULL DEFAULT '0' COMMENT 'Is this item no longer in the outline?'
CGDB0026;
$cg_database_updates[12][] = <<<CGDB0027
 INSERT INTO `dbversion` ( `version` ) VALUES ( '13' )
CGDB0027;
//----------------------------------------------------------------------------------------------------------------

//Version 13 to 14 -----------------------------------------------------------------------------------------------
$cg_database_updates[13][] = <<<CGDB0028
 ALTER TABLE `prefs` CHANGE `publicdefault` `publicdefault` TINYINT( 4 ) NOT NULL DEFAULT '1' COMMENT 'Should articles be public by default?',
 CHANGE `maxlist` `maxlist` INT( 11 ) NOT NULL DEFAULT '20' COMMENT 'Max number of entries to show by default.',
 CHANGE `riverhours` `riverhours` INT( 11 ) NOT NULL DEFAULT '30' COMMENT 'Range of hours to display in river',
 CHANGE `maxriversize` `maxriversize` INT( 11 ) NOT NULL DEFAULT '125' COMMENT 'Maximum number of river items.',
 CHANGE `maxriversizemobile` `maxriversizemobile` INT( 11 ) NOT NULL DEFAULT '75' COMMENT 'Maximum number of river items on mobile.',
 CHANGE `cartinriver` `cartinriver` TINYINT( 4 ) NOT NULL DEFAULT '1' COMMENT 'Show cartulized articles in a modal.',
 CHANGE `collapseriver` `collapseriver` TINYINT( 4 ) NOT NULL DEFAULT '1' COMMENT 'Show duplicate origin items in a threaded view?'
CGDB0028;
$cg_database_updates[13][] = <<<CGDB0029
 INSERT INTO `dbversion` ( `version` ) VALUES ( '14' )
CGDB0029;
//----------------------------------------------------------------------------------------------------------------

//Version 14 to 15 -----------------------------------------------------------------------------------------------
$cg_database_updates[14][] = <<<CGDB0030
 ALTER TABLE `nfitems` ADD `media` TINYINT NOT NULL DEFAULT '0' COMMENT 'Does this item contain attached media?'
CGDB0030;
$cg_database_updates[14][] = <<<CGDB0031
 UPDATE `nfitems` SET media = 1 WHERE OCTET_LENGTH( enclosure ) > 10
CGDB0031;
$cg_database_updates[14][] = <<<CGDB0032
 ALTER TABLE `nfitems` MODIFY column timeadded int(11)
CGDB0032;
$cg_database_updates[14][] = <<<CGDB0033
 ALTER TABLE `nfitems` MODIFY column timestamp int(11)
CGDB0033;
$cg_database_updates[14][] = <<<CGDB0034
 INSERT INTO `dbversion` ( `version` ) VALUES ( '15' )
CGDB0034;
//----------------------------------------------------------------------------------------------------------------

//Version 15 to 16 -----------------------------------------------------------------------------------------------
$cg_database_updates[15][] = <<<CGDB0035
 ALTER TABLE `prefs` ADD `hideme` TINYINT NOT NULL DEFAULT '0' COMMENT 'Hide this user from directory searches?'
CGDB0035;
$cg_database_updates[15][] = <<<CGDB0036
 INSERT INTO `dbversion` ( `version` ) VALUES ( '16' )
CGDB0036;
//----------------------------------------------------------------------------------------------------------------

//Version 16 to 17 -----------------------------------------------------------------------------------------------
$cg_database_updates[16][] = <<<CGDB0037
 ALTER TABLE `newsfeeds` CHANGE  `lastcheck`  `lastcheck` INT NOT NULL COMMENT  'Last time this feed was scanned.'
CGDB0037;
$cg_database_updates[16][] = <<<CGDB0038
 ALTER TABLE `newsfeeds` CHANGE  `lastupdate`  `lastupdate` INT NOT NULL COMMENT  'Last time the feed had a new item.'
CGDB0038;
$cg_database_updates[16][] = <<<CGDB0039
 ALTER TABLE `newsfeeds` CHANGE  `createdon`  `createdon` INT NOT NULL COMMENT  'When did the feed enter the system.'
CGDB0039;
$cg_database_updates[16][] = <<<CGDB0040
 ALTER TABLE `newsfeeds` CHANGE  `lastmod`  `lastmod` INT NOT NULL COMMENT  'Last modified time in head check.'
CGDB0040;
$cg_database_updates[16][] = <<<CGDB0041
 INSERT INTO `dbversion` ( `version` ) VALUES ( '17' )
CGDB0041;
//----------------------------------------------------------------------------------------------------------------

//Version 17 to 18 -----------------------------------------------------------------------------------------------
$cg_database_updates[17][] = <<<CGDB0042
 ALTER TABLE `prefs` ADD `pubrivertemplate` VARCHAR( 128 ) NOT NULL COMMENT 'Template url for public rivers.',
 ADD `opensubs` TINYINT NOT NULL DEFAULT '0' COMMENT 'Allow open subscriptions',
 ADD `publicriver` TINYINT NOT NULL DEFAULT '0' COMMENT 'Make river public?',
 ADD `pubriverfile` VARCHAR( 32 ) NOT NULL DEFAULT 'river.html' COMMENT 'The file name to use for public river',
 ADD `pubrivertitle` VARCHAR( 128 ) NOT NULL DEFAULT 'My Public River' COMMENT 'The public river title.'
CGDB0042;
$cg_database_updates[17][] = <<<CGDB0043
 INSERT INTO `dbversion` ( `version` ) VALUES ( '18' )
CGDB0043;
//----------------------------------------------------------------------------------------------------------------

//Version 18 to 19 -----------------------------------------------------------------------------------------------
$cg_database_updates[18][] = <<<CGDB0044
 ALTER TABLE `prefs` ADD `rivercolumns` INT NOT NULL DEFAULT '0' COMMENT 'Number of columns on river page.'
CGDB0044;
$cg_database_updates[18][] = <<<CGDB0045
 INSERT INTO `dbversion` ( `version` ) VALUES ( '19' )
CGDB0045;
//----------------------------------------------------------------------------------------------------------------

//Version 19 to 20 -----------------------------------------------------------------------------------------------
$cg_database_updates[19][] = <<<CGDB0046
 ALTER TABLE `microblog` ADD `target` VARCHAR( 700 ) NOT NULL COMMENT 'Url of target individual for this post.'
CGDB0046;
$cg_database_updates[19][] = <<<CGDB0047
 ALTER TABLE `nfitems` ADD `target` VARCHAR( 700 ) NOT NULL COMMENT 'Url of target individual for this post.'
CGDB0047;
$cg_database_updates[19][] = <<<CGDB0048
 INSERT INTO `dbversion` ( `version` ) VALUES ( '20' )
CGDB0048;
//----------------------------------------------------------------------------------------------------------------

//Version 20 to 21 -----------------------------------------------------------------------------------------------
$cg_database_updates[20][] = <<<CGDB0049
 UPDATE `prefs` SET stylesheet = '' WHERE 1
CGDB0049;
$cg_database_updates[20][] = <<<CGDB0050
 INSERT INTO `dbversion` ( `version` ) VALUES ( '21' )
CGDB0050;
//----------------------------------------------------------------------------------------------------------------

//Version 21 to 22 -----------------------------------------------------------------------------------------------
$cg_database_updates[21][] = <<<CGDB0051
 ALTER TABLE `prefs` CHANGE `cartinriver` `cartinriver` TINYINT( 4 ) NOT NULL DEFAULT '0' COMMENT 'Show cartulized articles in a modal.'
CGDB0051;
$cg_database_updates[21][] = <<<CGDB0052
 INSERT INTO `dbversion` ( `version` ) VALUES ( '22' )
CGDB0052;
//----------------------------------------------------------------------------------------------------------------

//Version 22 to 23 -----------------------------------------------------------------------------------------------
$cg_database_updates[22][] = <<<CGDB0053
 ALTER TABLE `users` ADD `totpseed` VARCHAR( 40 ) NOT NULL COMMENT 'Seed for totp calculation.'
CGDB0053;
$cg_database_updates[22][] = <<<CGDB0054
 ALTER TABLE `prefs` ADD `usetotp` TINYINT NOT NULL DEFAULT '0' COMMENT 'Enable TOTP challenge at login?'
CGDB0054;
$cg_database_updates[22][] = <<<CGDB0055
 ALTER TABLE `sessions` ADD `type` INT NOT NULL DEFAULT '0' COMMENT 'What type of session is this?'
CGDB0055;
$cg_database_updates[22][] = <<<CGDB0056
 INSERT INTO `dbversion` ( `version` ) VALUES ( '23' )
CGDB0056;
//----------------------------------------------------------------------------------------------------------------

//Version 23 to 24 -----------------------------------------------------------------------------------------------
$cg_database_updates[23][] = <<<CGDB0057
 CREATE TABLE IF NOT EXISTS `banned` (
  `ip` varchar(15) NOT NULL COMMENT 'IP address to ban.',
  `added` int(11) NOT NULL COMMENT 'Time the ban was added.',
  `reason` int(11) NOT NULL COMMENT 'Reason code for the ban.',
  `expires` int(11) NOT NULL COMMENT 'Time the ban expires.',
  PRIMARY KEY (`ip`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A list of ip addresses that are not allowed.'
CGDB0057;
$cg_database_updates[23][] = <<<CGDB0058
 CREATE TABLE IF NOT EXISTS `registration` (
  `ip` varchar(15) NOT NULL COMMENT 'IP address to ban.',
  `attempts` int(11) NOT NULL COMMENT 'How many attempts so far.',
  `lastattempt` int(11) NOT NULL COMMENT 'Last time registration was attempted.',
  PRIMARY KEY (`ip`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Track registration attempts for shenanigans.'
CGDB0058;
$cg_database_updates[23][] = <<<CGDB0059
 INSERT INTO `dbversion` ( `version` ) VALUES ( '24' )
CGDB0059;
//----------------------------------------------------------------------------------------------------------------

//Version 24 to 25 -----------------------------------------------------------------------------------------------
$cg_database_updates[24][] = <<<CGDB0060
 ALTER TABLE `prefs` ADD `hidesublist` TINYINT NOT NULL DEFAULT '0' COMMENT 'No sub list on social outline?'
CGDB0060;
$cg_database_updates[24][] = <<<CGDB0061
 INSERT INTO `dbversion` ( `version` ) VALUES ( '25' )
CGDB0061;
//----------------------------------------------------------------------------------------------------------------

//Version 25 to 26 -----------------------------------------------------------------------------------------------
$cg_database_updates[25][] = <<<CGDB0062
 ALTER TABLE `nfitemprops` DROP FOREIGN KEY `nfitemprops_ibfk_2`
CGDB0062;
$cg_database_updates[25][] = <<<CGDB0063
 CREATE TABLE nfitems_new LIKE nfitems
CGDB0063;
$cg_database_updates[25][] = <<<CGDB0064
 ALTER TABLE nfitems_new ADD newid BIGINT FIRST
CGDB0064;
$cg_database_updates[25][] = <<<CGDB0065
 ALTER TABLE nfitems_new CHANGE `newid` `newid` BIGINT NOT NULL AUTO_INCREMENT, ADD UNIQUE INDEX(newid)
CGDB0065;
$cg_database_updates[25][] = <<<CGDB0066
 INSERT INTO nfitems_new SELECT NULL, t.* FROM nfitems t ORDER BY t.timeadded
CGDB0066;
$cg_database_updates[25][] = <<<CGDB0067
 RENAME TABLE nfitems TO nfitems_old
CGDB0067;
$cg_database_updates[25][] = <<<CGDB0068
 RENAME TABLE nfitems_new TO nfitems
CGDB0068;
$cg_database_updates[25][] = <<<CGDB0069
 ALTER TABLE nfitemprops ADD newitemid BIGINT FIRST
CGDB0069;
$cg_database_updates[25][] = <<<CGDB0070
 UPDATE nfitemprops,nfitems SET nfitemprops.newitemid = nfitems.newid WHERE nfitemprops.itemid = nfitems.id
CGDB0070;
$cg_database_updates[25][] = <<<CGDB0071
 ALTER TABLE nfitemprops CHANGE `newitemid` `newitemid` BIGINT NOT NULL, ADD INDEX(newitemid)
CGDB0071;
$cg_database_updates[25][] = <<<CGDB0072
 DROP INDEX `id` ON nfitems
CGDB0072;
$cg_database_updates[25][] = <<<CGDB0073
 ALTER TABLE nfitems DROP COLUMN `id`
CGDB0073;
$cg_database_updates[25][] = <<<CGDB0074
 alter table nfitems change `newid` `id` BIGINT NOT NULL AUTO_INCREMENT, ADD UNIQUE INDEX(id)
CGDB0074;
$cg_database_updates[25][] = <<<CGDB0075
 drop index `newid` on nfitems
CGDB0075;
//$cg_database_updates[25][] = <<<CGDB0076
// drop index `itemid` on nfitemprops
//CGDB0076;
$cg_database_updates[25][] = <<<CGDB0077
 drop index `PRIMARY` on nfitemprops
CGDB0077;
$cg_database_updates[25][] = <<<CGDB0078
 alter table nfitemprops DROP COLUMN `itemid`
CGDB0078;
$cg_database_updates[25][] = <<<CGDB0079
 alter table nfitemprops change `newitemid` `itemid` BIGINT NOT NULL, ADD INDEX(itemid)
CGDB0079;
$cg_database_updates[25][] = <<<CGDB0080
 drop index `newitemid` on nfitemprops
CGDB0080;
$cg_database_updates[25][] = <<<CGDB0081
 create unique index itemuseridx ON nfitemprops (itemid,userid)
CGDB0081;
$cg_database_updates[25][] = <<<CGDB0082
 drop table nfitems_old
CGDB0082;
$cg_database_updates[25][] = <<<CGDB0083
 DELETE FROM nfitemprops WHERE NOT EXISTS ( SELECT * FROM nfitems WHERE nfitems.id = nfitemprops.itemid )
CGDB0083;
$cg_database_updates[25][] = <<<CGDB0084
 ALTER TABLE `nfitemprops` ADD FOREIGN KEY ( `itemid` ) REFERENCES `cartulary`.`nfitems` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0084;
$cg_database_updates[25][] = <<<CGDB0085
 DELETE FROM nfitems WHERE NOT EXISTS ( SELECT * FROM newsfeeds WHERE newsfeeds.id = nfitems.feedid )
CGDB0085;
$cg_database_updates[25][] = <<<CGDB0086
 ALTER TABLE `nfitems` ADD FOREIGN KEY ( `feedid` ) REFERENCES `cartulary`.`newsfeeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0086;

$cg_database_updates[25][] = <<<CGDB0087
 INSERT INTO `dbversion` ( `version` ) VALUES ( '26' )
CGDB0087;
//----------------------------------------------------------------------------------------------------------------

//Version 26 to 27 -----------------------------------------------------------------------------------------------
$cg_database_updates[26][] = <<<CGDB0088
  ALTER TABLE `prefs` ADD `analyticscode` VARCHAR( 767 ) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL COMMENT 'Analytics code to use in html pages.'
CGDB0088;
$cg_database_updates[26][] = <<<CGDB0089
 INSERT INTO `dbversion` ( `version` ) VALUES ( '27' )
CGDB0089;
//----------------------------------------------------------------------------------------------------------------

//Version 27 to 28 -----------------------------------------------------------------------------------------------
$cg_database_updates[27][] = <<<CGDB0090
  ALTER TABLE `prefs` ADD `disqus_shortname` VARCHAR( 64 ) NOT NULL
CGDB0090;
$cg_database_updates[27][] = <<<CGDB0091
 INSERT INTO `dbversion` ( `version` ) VALUES ( '28' )
CGDB0091;
//----------------------------------------------------------------------------------------------------------------

//Version 28 to 29 -----------------------------------------------------------------------------------------------
$cg_database_updates[28][] = <<<CGDB0092
 CREATE TABLE IF NOT EXISTS `recentfiles` (
  `id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Standard id',
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User file belongs to.',
  `url` varchar(767) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Url the file is stored at.',
  `time` int(11) NOT NULL DEFAULT '0' COMMENT 'Time of last save.',
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Recently edited files.'
CGDB0092;
$cg_database_updates[28][] = <<<CGDB0093
 INSERT INTO `dbversion` ( `version` ) VALUES ( '29' )
CGDB0093;
//----------------------------------------------------------------------------------------------------------------

//Version 29 to 30 -----------------------------------------------------------------------------------------------
$cg_database_updates[29][] = <<<CGDB0094
  ALTER TABLE  `recentfiles` ADD  `title` VARCHAR( 512 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT  'Title of the file.' AFTER  `url`
CGDB0094;
$cg_database_updates[29][] = <<<CGDB0095
 INSERT INTO `dbversion` ( `version` ) VALUES ( '30' )
CGDB0095;
//----------------------------------------------------------------------------------------------------------------

//Version 30 to 31 -----------------------------------------------------------------------------------------------
$cg_database_updates[30][] = <<<CGDB0096
  ALTER TABLE `recentfiles` ADD UNIQUE ( `url` )
CGDB0096;
$cg_database_updates[30][] = <<<CGDB0097
 INSERT INTO `dbversion` ( `version` ) VALUES ( '31' )
CGDB0097;
//----------------------------------------------------------------------------------------------------------------

//Version 31 to 32 -----------------------------------------------------------------------------------------------
$cg_database_updates[31][] = <<<CGDB0098
  ALTER TABLE `recentfiles` ADD  `outline` LONGTEXT CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT  'The actual outline content.'
CGDB0098;
$cg_database_updates[31][] = <<<CGDB0099
 INSERT INTO `dbversion` ( `version` ) VALUES ( '32' )
CGDB0099;
//----------------------------------------------------------------------------------------------------------------

//Version 32 to 33 -----------------------------------------------------------------------------------------------
$cg_database_updates[32][] = <<<CGDB0100
 CREATE TABLE IF NOT EXISTS `redirect` (
  `id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Standard id',
  `host` varchar(767) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Host name for redirection.',
  `url` varchar(767) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Url the file is stored at.',
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User id that created file.',
  `hits` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Time of last save.',
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Redirector table.'
CGDB0100;
$cg_database_updates[32][] = <<<CGDB0101
  ALTER TABLE `redirect` ADD UNIQUE ( `host` )
CGDB0101;
$cg_database_updates[32][] = <<<CGDB0102
  ALTER TABLE `redirect` ADD INDEX ( `userid` )
CGDB0102;
$cg_database_updates[32][] = <<<CGDB0103
  ALTER TABLE `redirect` ADD FOREIGN KEY ( `userid` ) REFERENCES `cartulary`.`users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0103;
$cg_database_updates[32][] = <<<CGDB0104
 INSERT INTO `dbversion` ( `version` ) VALUES ( '33' )
CGDB0104;
//----------------------------------------------------------------------------------------------------------------

//Version 33 to 34 -----------------------------------------------------------------------------------------------
$cg_database_updates[33][] = <<<CGDB0105
  ALTER TABLE `recentfiles` ADD FOREIGN KEY ( `userid` ) REFERENCES `cartulary`.`users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0105;
$cg_database_updates[33][] = <<<CGDB0106
 INSERT INTO `dbversion` ( `version` ) VALUES ( '34' )
CGDB0106;
//----------------------------------------------------------------------------------------------------------------

//Version 34 to 35 -----------------------------------------------------------------------------------------------
$cg_database_updates[34][] = <<<CGDB0107
 ALTER TABLE  `prefs` ADD  `editorbucket` VARCHAR( 64 ) NOT NULL COMMENT  'S3 Bucket to hold editor files.'
CGDB0107;
$cg_database_updates[34][] = <<<CGDB0108
 INSERT INTO `dbversion` ( `version` ) VALUES ( '35' )
CGDB0108;
//----------------------------------------------------------------------------------------------------------------

//Version 35 to 36 -----------------------------------------------------------------------------------------------
$cg_database_updates[35][] = <<<CGDB0109
 ALTER TABLE `recentfiles` CHANGE id id bigint(20)auto_increment
CGDB0109;
$cg_database_updates[35][] = <<<CGDB0110
 ALTER TABLE `redirect` CHANGE id id bigint(20)auto_increment
CGDB0110;
$cg_database_updates[35][] = <<<CGDB0111
 INSERT INTO `dbversion` ( `version` ) VALUES ( '36' )
CGDB0111;
//----------------------------------------------------------------------------------------------------------------

//Version 36 to 37 -----------------------------------------------------------------------------------------------
$cg_database_updates[36][] = <<<CGDB0112
 ALTER TABLE `recentfiles` ADD `disqus` TINYINT NOT NULL COMMENT 'Comments enabled?', ADD `wysiwyg` TINYINT NOT NULL COMMENT 'Wysiwyg enabled?'
CGDB0112;
$cg_database_updates[36][] = <<<CGDB0113
 INSERT INTO `dbversion` ( `version` ) VALUES ( '37' )
CGDB0113;
//----------------------------------------------------------------------------------------------------------------

//Version 37 to 38 -----------------------------------------------------------------------------------------------
$cg_database_updates[37][] = <<<CGDB0114
 ALTER TABLE `prefs` ADD `sessioncookies` TINYINT NOT NULL COMMENT 'Stay logged in?'
CGDB0114;
$cg_database_updates[37][] = <<<CGDB0115
 INSERT INTO `dbversion` ( `version` ) VALUES ( '38' )
CGDB0115;
//----------------------------------------------------------------------------------------------------------------

//Version 38 to 39 -----------------------------------------------------------------------------------------------
$cg_database_updates[38][] = <<<CGDB0116
 ALTER TABLE `prefs` ADD `imap_server` VARCHAR( 128 ) NOT NULL ,
 ADD `imap_username` VARCHAR( 128 ) NOT NULL ,
 ADD `imap_password` VARCHAR( 128 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
 ADD `imap_folder` VARCHAR( 128 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL ,
 ADD `imap_secure` TINYINT NOT NULL
CGDB0116;
$cg_database_updates[38][] = <<<CGDB0117
 INSERT INTO `dbversion` ( `version` ) VALUES ( '39' )
CGDB0117;
//----------------------------------------------------------------------------------------------------------------

//Version 39 to 40 -----------------------------------------------------------------------------------------------
$cg_database_updates[39][] = <<<CGDB0118
 ALTER TABLE `newsfeeds` ADD `type` TINYINT NOT NULL COMMENT 'What type of feed is this'
CGDB0118;
$cg_database_updates[39][] = <<<CGDB0119
 INSERT INTO `dbversion` ( `version` ) VALUES ( '40' )
CGDB0119;
//----------------------------------------------------------------------------------------------------------------

//Version 40 to 41 -----------------------------------------------------------------------------------------------
$cg_database_updates[40][] = <<<CGDB0120
 ALTER TABLE `microblog` ADD `type` TINYINT NOT NULL COMMENT 'What type of item is referenced'
CGDB0120;
$cg_database_updates[40][] = <<<CGDB0121
 ALTER TABLE `mbcatalog` ADD `microblogid` BIGINT NOT NULL COMMENT 'Which microblog does this belong to'
CGDB0121;
$cg_database_updates[40][] = <<<CGDB0122
 CREATE TABLE IF NOT EXISTS `microblogs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Standard id',
  `title` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Microblog title',
  `ownerid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Linked to user id',
  `s3bucket` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Where the microblog is stored',
  `s3cname` varchar(767) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Optional url for the microblog',
  `s3filename` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The name of the microblog file',
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of alternate microblogs'
CGDB0122;
$cg_database_updates[40][] = <<<CGDB0123
  ALTER TABLE `microblogs` ADD INDEX ( `ownerid` )
CGDB0123;
$cg_database_updates[40][] = <<<CGDB0124
  ALTER TABLE `microblogs` ADD FOREIGN KEY ( `ownerid` ) REFERENCES `cartulary`.`users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0124;
$cg_database_updates[40][] = <<<CGDB0125
  ALTER TABLE `microblogs` AUTO_INCREMENT = 1
CGDB0125;
$cg_database_updates[40][] = <<<CGDB0126
 INSERT INTO `dbversion` ( `version` ) VALUES ( '41' )
CGDB0126;
//----------------------------------------------------------------------------------------------------------------

//Version 41 to 42 -----------------------------------------------------------------------------------------------
$cg_database_updates[41][] = <<<CGDB0127
 ALTER TABLE `newsfeeds` CHANGE `content` `content` LONGTEXT CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL
CGDB0127;
$cg_database_updates[41][] = <<<CGDB0128
 INSERT INTO `dbversion` ( `version` ) VALUES ( '42' )
CGDB0128;
//----------------------------------------------------------------------------------------------------------------

//Version 42 to 43 -----------------------------------------------------------------------------------------------
$cg_database_updates[42][] = <<<CGDB0129
 ALTER TABLE `newsfeeds` ADD `contenthash` VARCHAR( 40 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'An sha-1 hash of the content column.', ADD INDEX ( `contenthash` )
CGDB0129;
$cg_database_updates[42][] = <<<CGDB0130
 CREATE TABLE IF NOT EXISTS `nfitem_map` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Standard id',
  `word` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Microblog title',
  `dummy` BOOLEAN NOT NULL COMMENT 'http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html',
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Search term mapping for newsfeed items.'
CGDB0130;
$cg_database_updates[42][] = <<<CGDB0131
 ALTER TABLE `nfitem_map` ADD UNIQUE ( `word` )
CGDB0131;
$cg_database_updates[42][] = <<<CGDB0132
 CREATE TABLE IF NOT EXISTS `nfitem_map_catalog` (
  `wordid` bigint(20) NOT NULL COMMENT 'Word mapping id',
  `nfitemid` bigint(20) NOT NULL COMMENT 'Newsfeed mapping id'
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Word to item catalog'
CGDB0132;
$cg_database_updates[42][] = <<<CGDB0133
 ALTER TABLE `nfitem_map_catalog` ADD INDEX ( `wordid` )
CGDB0133;
$cg_database_updates[42][] = <<<CGDB0134
 ALTER TABLE `nfitem_map_catalog` ADD INDEX ( `nfitemid` )
CGDB0134;
$cg_database_updates[42][] = <<<CGDB0135
 ALTER TABLE `nfitem_map_catalog` ADD UNIQUE (`wordid` ,`nfitemid`)
CGDB0135;
$cg_database_updates[42][] = <<<CGDB0136
 ALTER TABLE `nfitem_map_catalog` ADD FOREIGN KEY ( `wordid` ) REFERENCES `cartulary`.`nfitem_map` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0136;
$cg_database_updates[42][] = <<<CGDB0137
 ALTER TABLE `nfitem_map_catalog` ADD FOREIGN KEY ( `nfitemid` ) REFERENCES `cartulary`.`nfitems` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0137;
$cg_database_updates[42][] = <<<CGDB0138
 INSERT INTO `dbversion` ( `version` ) VALUES ( '43' )
CGDB0138;
//----------------------------------------------------------------------------------------------------------------

//Version 43 to 44 -----------------------------------------------------------------------------------------------
$cg_database_updates[43][] = <<<CGDB0139
 ALTER TABLE `microblog` ADD `opmlsource` LONGTEXT NOT NULL COMMENT 'Opml source of the post.'
CGDB0139;
$cg_database_updates[43][] = <<<CGDB0140
 INSERT INTO `dbversion` ( `version` ) VALUES ( '44' )
CGDB0140;
//----------------------------------------------------------------------------------------------------------------

//Version 44 to 45 -----------------------------------------------------------------------------------------------
$cg_database_updates[44][] = <<<CGDB0141
 ALTER TABLE `recentfiles` ADD `qrcode` VARCHAR( 767 ) NOT NULL COMMENT 'QR code for this outline url.'
CGDB0141;
$cg_database_updates[44][] = <<<CGDB0142
 INSERT INTO `dbversion` ( `version` ) VALUES ( '45' )
CGDB0142;
//----------------------------------------------------------------------------------------------------------------

//Version 45 to 46 -----------------------------------------------------------------------------------------------
$cg_database_updates[45][] = <<<CGDB0143
 ALTER TABLE `recentfiles` ADD `watched` TINYINT NOT NULL COMMENT 'watch this files links for changes?'
CGDB0143;
$cg_database_updates[45][] = <<<CGDB0144
 INSERT INTO `dbversion` ( `version` ) VALUES ( '46' )
CGDB0144;
//----------------------------------------------------------------------------------------------------------------

//Version 46 to 47 -----------------------------------------------------------------------------------------------
$cg_database_updates[46][] = <<<CGDB0145
 CREATE TABLE IF NOT EXISTS `watched_urls` (
  `rid` bigint(20) NOT NULL COMMENT 'recent files table id',
  `url` varchar(2048) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'url of outline',
  `lastmodified` varchar(40) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'content of last last-modified header'
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Linkage between articles and external outlines.'
CGDB0145;
$cg_database_updates[46][] = <<<CGDB0146
 ALTER TABLE `watched_urls` ADD INDEX ( `rid` )
CGDB0146;
$cg_database_updates[46][] = <<<CGDB0147
 ALTER TABLE `watched_urls` ADD `content` TEXT CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Content of watched outline'
CGDB0147;
$cg_database_updates[46][] = <<<CGDB0148
 ALTER TABLE `watched_urls` ADD FOREIGN KEY ( `rid` ) REFERENCES `cartulary`.`recentfiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
CGDB0148;
$cg_database_updates[46][] = <<<CGDB0149
 INSERT INTO `dbversion` ( `version` ) VALUES ( '47' )
CGDB0149;
//----------------------------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------------------------


//----------------------------------------------------------------------------------------------------------------
// Database utility functions


//Check for the current database version
function get_database_version()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

  //Get the database version number
  $stmt = "SELECT version FROM $table_dbversion ORDER BY version DESC LIMIT 1";
  if( ($sql=$dbh->prepare($stmt)) === FALSE ) {
    loggit(3,"Error preparing to query database version.");
    return(FALSE);
  }
  if( $sql->execute() === FALSE ) {
    loggit(3,"Error executing query for database version.");
    return(FALSE);
  }
  $sql->store_result() or loggit(2, "MySql error: ".$dbh->error);
  if($sql->num_rows() != 1) {
    $sql->close() or loggit(2, "MySql error: ".$dbh->error);
    loggit(3,"Too many, or not enough, records returned for database version.");
    return(FALSE);
  }
  $sql->bind_result($cdbversion) or loggit(2, "MySql error: ".$dbh->error);
  $sql->fetch() or loggit(2, "MySql error: ".$dbh->error);
  $sql->close() or loggit(2, "MySql error: ".$dbh->error);


  loggit(3,"Database version: [$cdbversion]");
  return( $cdbversion );
}


//Apply updates to the database to bring it to the current version
function apply_all_database_updates()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  global $cg_database_version;
  global $cg_database_updates;

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

  //Get the current database version
  $error = FALSE;
  $dbversion = get_database_version();
  if( $dbversion == FALSE ) {
    $dbversion = 0;
  }


  //We execute a loop, applying all of the updates from the
  //current database version up to the newest
  $rounds = 0;
  while( $dbversion < $cg_database_version ) {
      loggit(3, "DATABASE UPGRADE: Applying update [$dbversion]");

      //Execute the queries in this update
      $stmt = $cg_database_updates[$dbversion];

      $i = 0;
      if( $dbh->multi_query(implode(';', $stmt)) ) {
        do {
          $i++;
        } while ($dbh->next_result());
      }
      if ($dbh->errno) {
        loggit(3, "DATABASE UPGRADE ERROR ON [$i]: ".print_r($dbh->error, TRUE));
        $dbh->close() or loggit(2, "MySql error: ".$dbh->error);
        return(FALSE);
      }

      //Check where we're at now
      $dbversion = get_database_version();
      if( $dbversion == FALSE ) {
        loggit(3,"The last database update: [$dbversion] did not apply correctly.");
        $dbh->close() or loggit(2, "MySql error: ".$dbh->error);
        return(FALSE);
      }

      if( $dbversion == $cg_database_version ) {
        loggit(3,"Database is current at version: [$dbversion].");
        $dbh->close() or loggit(2, "MySql error: ".$dbh->error);
        return(TRUE);
      } else {
        loggit(3,"Database now at version: [$dbversion].");
      }
  }


  //Close connection and bail
  $dbh->close() or loggit(2, "MySql error: ".$dbh->error);
  return(FALSE);
}


//Check if the database actually has a good schema
function check_database_sanity()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

  //Get the database version number
  $stmt = "SELECT table_name FROM information_schema.tables WHERE table_schema = ? and table_name = ?";
  if( ($sql=$dbh->prepare($stmt)) === FALSE ) {
    loggit(3,"Error preparing to query schema.");
    return(FALSE);
  }
  if( $sql->bind_param("ss", $dbname, $table_user) === FALSE ) {
    loggit(3,"Error binding parameters for schema check.");
    return(FALSE);
  }
  if( $sql->execute() === FALSE ) {
    loggit(3,"Error executing query for schema check.");
    return(FALSE);
  }
  $sql->store_result() or loggit(2, "MySql error: ".$dbh->error);
  if($sql->num_rows() != 1) {
    $sql->close() or loggit(2, "MySql error: ".$dbh->error);
    loggit(3,"Too many, or not enough, records returned for database version.");
    return(FALSE);
  }
  $sql->close() or loggit(2, "MySql error: ".$dbh->error);


  loggit(3,"Users table present. Database schema appears sane.");
  return( TRUE );
}