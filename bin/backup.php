<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

        //Is s3 enabled and a backup bucket specified?
        if( !sys_s3_is_enabled() ) {
  		loggit(3, "System level S3 not enabled in conf file.");
  		cronHelper::unlock();
                exit(0);
        }
        if( empty($s3_sys_backup) ) {
  		loggit(3, "S3 backup bucket not specified in conf file.");
  		cronHelper::unlock();
                exit(0);
        }

        //We're good, so do the backup
  	loggit(3, "Backing up the database...");
  	$tstart = time();

        //Create a filename to use for this backup
        $filename = $dbname."_backup.".date('Y-m-d.His').".sql.gz";
        $dumpfile = sys_get_temp_dir()."/".$filename;

        //Run mysqldump command
	$cmdtorun = "mysqldump --single-transaction --quick -h$dbhost -u$dbuser -p$dbpass $dbname | cstream -t 1000000 | gzip -c > $dumpfile";
	//$cmdtorun = "echo 'test' > $dumpfile"; // for testing
  	loggit(3, "BACKUP: Running command: [$cmdtorun].");
        $output = `$cmdtorun`;
        loggit(3, "BACKUP: Result: [".print_r($output, TRUE)."]");

        //Get the file size
        $filesize = filesize($dumpfile);

	//If we can get some sane S3 credentials then let's go
	$s3info = get_sys_s3_info();
        if( $s3info != FALSE ) {
                //Put the file
                $s3res = putFileInS3($dumpfile, $filename, $s3info['backup'], $s3info['key'], $s3info['secret'], "text/plain", TRUE);
                if(!$s3res) {
                        loggit(3, "Could not write database backup: [$filename | ".format_bytes($filesize)."] to S3 in bucket: [".$s3info['backup']."].");
                } else {
                        loggit(3, "Wrote database backup: [$filename | ".format_bytes($filesize)."] to S3 in bucket: [".$s3info['backup']."].");
		}
	}

  	//Calculate how long it took to backup the database
  	$took = time() - $tstart;
  	echo "It took: [$took] seconds to backup the database.";
  	loggit(3, "It took: [$took] seconds to backup the database.");

	//Add an administrative log entry for this event
	add_admin_log_item("Wrote database backup: [$filename | ".format_bytes($filesize)."] to S3 in bucket: [".$s3info['backup']."].  The operation took: [$took] seconds.", "Backup Complete");

	//Clean up the temporary dump file
        if( !unlink($dumpfile) ) {
  		loggit(3, "Could not remove temporary database dump file. Check file/user permissions.");
	}

        //Release the lock
  	cronHelper::unlock();
  }
  exit(0);
?>
