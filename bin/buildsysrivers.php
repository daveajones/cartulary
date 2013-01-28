<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

        //Let's not scan while other scripts are in process
        if( file_exists("$confroot/$run/backup.php.lock") ) {
          cronHelper::unlock();
          loggit(3, "BUILDSYSRIVER: Backup is in progress, so skipping this build.");
          exit(0);
        }

  	loggit(1, "Building server river...");
  	echo "Building server river.\n\n";

  	//Build the server-wide river if those values aren't blank
  	$tstart = time();
        $s3info = get_sys_s3_info();
        if( !empty($s3info['riverbucket']) && !empty($s3info['riverfile']) ) {
          build_server_river_json(100);
        } else {
    	  loggit(1, "Skipping server-wide river build. Bucket or file value is empty.");
    	  echo "Skipping server-wide river build. Bucket or file value is empty.\n\n";
        }

  	//Calculate how long it took to build the rivers
  	$took = time() - $tstart;

    	loggit(1, "It took: [$took] seconds to build the server-wide river.");
  	loggit(1, "Done.");

        //Release the lock
  	cronHelper::unlock();
  }
  exit(0);

?>
