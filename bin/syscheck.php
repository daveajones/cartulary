<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

        //Let's check the system
        loggit(3, "Checking server health...");
        $healthy = TRUE;
        $tstart = time();

        //See how much space is left on the app partition, and if
        //it's less than 2 gigabytes, put a warning in the admin feedlog
        $dfree = disk_free_space($confroot);
        if( $dfree < 2147483648 ) {
          echo "WARNING: The server only has: [".format_bytes($dfree)."] of free space left.\n";
          loggit(2, "WARNING: The server only has: [".format_bytes($dfree)."] of free space left.");
          //Add an administrative log entry for this event
          add_admin_log_item("WARNING: The server only has: [".format_bytes($dfree)."] of free space left.", "Disk Space Warning!");
          $healthy = FALSE;
        }

        //Make sure the system has the correct crontab entries
        if( !file_exists("$cronloc") ) {
          copy( "$confroot/$templates/crontab", "$cronloc");
          echo "WARNING: The server had no cron file. A fresh one was created.\n";
          loggit(2, "The server had no cron file. A fresh one was created.");
          //Add an administrative log entry for this event
          add_admin_log_item("WARNING: The server had no cron file. A fresh one was created.", "Cron File Missing.");
          $healthy = FALSE;
        }

        //Calculate how long it took
        $took = time() - $tstart;
        echo "It took: [$took] seconds to check server health.";
        loggit(3, "It took: [$took] seconds to check server health.");

        //Log an unhealthy system check
        if( $healthy != TRUE ) {
          echo "WARNING:  The system isn't healthy.  See previous log entries for details.\n";
          loggit(3, "WARNING: The system isn't healthy.  See previous log entries for details.");
        }

        //Release the lock
        cronHelper::unlock();
  }
  exit(0);
?>

