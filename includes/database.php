<?php
//########################################################################################
// API for managing database schema
//########################################################################################


//A list of database schema updates for each version
$cg_database_version = 24;
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
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A list of ip addresses that are not allowed.';
CGDB0057;
$cg_database_updates[23][] = <<<CGDB0058
 INSERT INTO `dbversion` ( `version` ) VALUES ( '24' )
CGDB0058;
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