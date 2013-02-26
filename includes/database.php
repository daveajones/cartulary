<?
//A list of database schema updates for each version
$cg_database_version['major'] = 0;
$cg_database_version['minor'] = 1;
$cg_database_updates = array();

//Version 0 to 1 -------------------------------------------------------------------------------------------------
$cg_database_updates[0][0][] = <<<CGDB001
 ALTER TABLE `prefs` ADD `cartinriver` TINYINT NOT NULL DEFAULT '0' COMMENT 'Show cartulized articles in a modal.'
CGDB001;

$cg_database_updates[0][0][] = <<<CGDB002
 CREATE TABLE IF NOT EXISTS `dbversion` (
  `major` int(11) NOT NULL DEFAULT '0' COMMENT 'Major changes.',
  `minor` int(11) NOT NULL DEFAULT '0' COMMENT 'Minor changes.',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date change was made.',
  KEY `changed` (`changed`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Track database version and changes.'
CGDB002;

$cg_database_updates[0][0][] = <<<CGDB003
 ALTER TABLE `cartulary`.`dbversion` ADD UNIQUE `current` ( `major` , `minor` )
CGDB003;

$cg_database_updates[0][0][] = <<<CGDB004
 INSERT INTO `cartulary`.`dbversion` ( `major` , `minor` , `changed` ) VALUES ( '0', '1', CURRENT_TIMESTAMP )
CGDB004;
//----------------------------------------------------------------------------------------------------------------

?>



<?
// ----- Database utility functions

//_______________________________________________________________________________________
//Check if the given user id actually exists in the system
function get_database_version()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get the database version number
  $stmt = "SELECT major,minor FROM $table_dbversion ORDER BY major,minor DESC LIMIT 1";
  if( ($sql=$dbh->prepare($stmt)) === FALSE ) {
    loggit(3,"Error preparing to query database version.");
    return(FALSE);
  }
  if( $sql->execute() === FALSE ) {
    loggit(3,"Error executing query for database version.");
    return(FALSE);
  }
  $sql->store_result() or print(mysql_error());
  if($sql->num_rows() != 1) {
    $sql->close() or print(mysql_error());
    loggit(3,"Too many, or not enough, records returned for database version.");
    return(FALSE);
  }
  $sql->bind_result($dbvmajor,$dbvminor) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());


  loggit(3,"Database version: [$dbvmajor.$dbvminor]");
  return( array('major' => $dbvmajor, 'minor' => $dbvminor) );
}


//_______________________________________________________________________________________
//Apply updates to the database to bring it to the current version
function apply_all_database_updates()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  global $cg_database_version;
  global $cg_database_updates;

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get the current database version
  $error = FALSE;
  $dbvmajor = 0;
  $dbvminor = 0;
  $cdbv = get_database_version();
  if( $cdbv != FALSE ) {
    $dbvmajor = $cdbv['major'];
    $dbvminor = $cdbv['minor'];
  }


  //We execute a loop, applying all of the updates from the
  //current database version up to the newest
  while( $dbvmajor <= $cg_database_version['major'] ) {
    while( $dbvminor < $cg_database_version['minor'] ) {
      loggit(3, "DATABASE UPGRADE: Applying update [$dbvmajor.$dbvminor]");

      //Execute the queries in this update
      $stmt = $cg_database_updates[$dbvmajor][$dbvminor];


      $i = 0;
      if( $dbh->multi_query(implode(';', $stmt)) ) {
        do {
          $i++;
        } while ($dbh->next_result());
      }
      if ($dbh->errno) {
        loggit(3, "DATABASE UPGRADE ERROR ON [$i]: ".print_r($dbh->error, TRUE));
        $dbh->close() or print(mysql_error());
        return(FALSE);
      }

      //Check where we're at now
      $cdbv = get_database_version();
      if( $cdbv != FALSE ) {
        $dbvmajor = $cdbv['major'];
        $dbvminor = $cdbv['minor'];
      } else {
        loggit(3,"The last database update: [$dbvmajor.$dbvminor] did not apply correctly.");
        $dbh->close() or print(mysql_error());
        return(FALSE);
      }
    }

    if( $dbvmajor == $cg_database_version['major'] && $dbvminor == $cg_database_version['minor'] ) {
      loggit(3,"Database is current at version: [$dbvmajor.$dbvminor].");
      $dbh->close() or print(mysql_error());
      return(TRUE);
    }
  }


  //Close connection and bail
  $dbh->close() or print(mysql_error());
  return(FALSE);
}



?>
