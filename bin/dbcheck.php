<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?include "$confroot/$includes/database.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

	//Set the database update flag
    set_sys_flag($CG_FLAG_DBUPDATE, 1);

	//Timing
	$tstart = time();

	//Check for a valid schema first
	if( !check_database_sanity() ) {
		echo "The database doesn't appear to have a valid schema.  Did you do the initialization import yet?  It's in [$confroot/$templates].\n";
        loggit(2, "Database sanity check failed.");
		echo "\n";
		//Set the database update flag
        delete_sys_flag($CG_FLAG_DBUPDATE);
        //Release the lock
        cronHelper::unlock();
		exit(1);
	}

	//See if we need to upgrade
    $resp = get_database_version();
    if( $cg_database_version != $resp ) {
	    echo "The database is being upgraded. Be patient, this could take a while...\n";
        loggit(3, "The database needs updating.");

		//Attempt a database upgrade
        $stat = apply_all_database_updates();
        if( $stat == TRUE ) {
            echo "The database was upgraded to version: [$cg_database_version].\n";
            loggit(3, "The database was upgraded to version: [$cg_database_version]");
            add_admin_log_item("The database was upgraded to version: [$cg_database_version].", "Database Upgrade.");
        } else {
            echo "WARNING: Attempt to upgrade the database failed. Check server logs for details.\n";
            loggit(3, "WARNING: Attempt to upgrade the database failed. Check server logs for details.");
            add_admin_log_item("WARNING: Attempt to upgrade the database failed. Check server logs for details.", "Database Upgrade.");
        }

	//No upgrade needed
    } else {
    	echo "Database is current.\n";
        loggit(3, "Database is current.");
    }

    //Calculate how long it took
    $took = time() - $tstart;
    echo "It took: [$took] seconds to check the database.\n";
    loggit(3, "It took: [$took] seconds to check the database.");

	echo "\n";

	//Set the database update flag
    delete_sys_flag($CG_FLAG_DBUPDATE);

    //Release the lock
    cronHelper::unlock();
  }
  exit(0);
?>

