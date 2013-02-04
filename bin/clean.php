<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

  	loggit(1, "Performing cleanup...");
  	echo "Performing cleanup...\n";
  	$tstart = time();


  	//Clean session table
        purge_old_sessions( $tstart - 345600 );

	//Clean spool directory
        $spooldir = "$confroot/$cg_folder_spool/";
        if( $handle = opendir($spooldir) ) {
          while( false !== ($file = readdir($handle)) ) {
            //Remove files that haven't been modified in 2 hours
            if( $file != '..' && $file != '.' ) {
              if( filemtime($spooldir.$file) <= (time() - 7200) ) {
                loggit(1, "Removed old spool file: [$file].");
                echo "  Removed old spool file: [$file]\n";
                unlink($spooldir.$file);
              }
            }
          }
          closedir($handle);
        }

  	//Calculate how long it took to clean things up
  	$took = time() - $tstart;
  	echo "It took: [$took] seconds to perform cleanup.\n";
  	loggit(1, "It took: [$took] seconds to perform cleanup.");

        //Release the lock
  	cronHelper::unlock();
  }
  exit(0);
?>
