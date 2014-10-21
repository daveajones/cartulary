<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    //If this is being run as an upgrade
    $action = "";
    if (isset($argv[1])) {
        $action = $argv[1];
    }

    //Let's check the system
    loggit(3, "Checking server health...");
    $healthy = TRUE;
    $tstart = time();

    //See how much space is left on the app partition, and if
    //it's less than 1 gigabyte, put a warning in the admin feedlog
    $dfree = disk_free_space($confroot);
    if ($dfree < $cg_default_disk_free_warning) {
        echo "WARNING: The server only has: [" . format_bytes($dfree) . "] of free space left.\n";
        loggit(2, "WARNING: The server only has: [" . format_bytes($dfree) . "] of free space left.");
        //Add an administrative log entry for this event
        add_admin_log_item("WARNING: The server only has: [" . format_bytes($dfree) . "] of free space left.", "Disk Space Warning!");
        $healthy = FALSE;
    }

    //Make sure the system has the correct crontab entries
    if (!file_exists("$cronloc")) {
        //Read the template
        $crontemp = "$confroot/$templates/crontab";
        $fh = fopen($crontemp, "r");
        $template = fread($fh, filesize($crontemp));
        fclose($fh);

        //Adjust file paths
        $template = str_replace('[$CONF_ROOT]', $confroot, $template);

        //Write the new config file
        $fh = fopen($cronloc, "w+");
        fwrite($fh, $template);
        fclose($fh);

        //Log it
        if ($action == "") {
            echo "WARNING: The server had no cron file. A fresh one was created.\n";
            loggit(2, "The server had no cron file. A fresh one was created.");
            //Add an administrative log entry for this event
            add_admin_log_item("WARNING: The server had no cron file. A fresh one was created.", "Cron File Missing.");
        }
        $healthy = FALSE;
    }

    //Check external ip address and update redirect bucket
    if (!empty($s3_sys_server_redirect_bucket) && !empty($cg_external_ip_reflector_url)) {
        $exip = get_external_ip_address($cg_external_ip_reflector_url);
        if (!set_bucket_redirect($s3_sys_server_redirect_bucket, $exip)) {
            loggit(2, "Error setting bucket: [$s3_sys_server_redirect_bucket] to redirect to host: [$exip].");
        } else {
            loggit(3, "Set bucket: [$s3_sys_server_redirect_bucket] to redirect to host: [$exip].");
        }
    }

    //Expire old registration attempt bans
    reset_registration_attempt_counters();

    //Make sure we list our own server only once
    remove_server($cg_main_serverguid);
    update_server_address($cg_main_serverguid, $system_fqdn);

    //Check for dead servers
    $servers = get_all_servers();
    foreach( $servers as $server ) {
        //Make sure the server is still alive
        $serverurl = get_final_url('http://' . $server['address']);
        if ($serverurl == FALSE) {
            remove_server($server['guid']);
        }
        $serverinfo = fetchUrl(trim($serverurl, '/') . $getserverinfocgi . '?addr=' . $system_fqdn . '&guid=' . $cg_main_serverguid);
        if ($serverinfo == FALSE) {
            remove_server($server['guid']);
        }
    }

    //Calculate how long it took
    $took = time() - $tstart;
    echo "It took: [$took] seconds to check server health.";
    loggit(3, "It took: [$took] seconds to check server health.");

    //Log an unhealthy system check
    if ($healthy != TRUE && $action == "") {
        echo "WARNING:  The system isn't healthy.  See previous log entries for details.\n";
        loggit(3, "WARNING: The system isn't healthy.  See previous log entries for details.");
    }

    echo "\n";

    //Release the lock
    cronHelper::unlock();
}
exit(0);
?>

