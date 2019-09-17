<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    //If this is being run as an upgrade
    $action = "";
    if (in_array("upgrade", $argv)) {
        $action = "upgrade";
    }
    $silent = FALSE;
    if (in_array("silent", $argv) && $action == "upgrade") {
        $silent = TRUE;
    }

    $cfname = "$confroot/conf/cartulary.conf";
    $cftemp = "$confroot/$templates/cartulary.conf";

    //Default values
    $l_pathtocart = $confroot;
    $l_serverguid = random_gen(64);
    $l_dblocalhost = "127.0.0.1";
    $l_dbusername = "cartulary";
    $l_dbpassword = "cartulary";
    $l_fqdn = "localhost";
    $l_s3key = "";
    $l_s3secret = "";
    $l_s3bucket = "";
    $l_s3cname = "";
    $l_s3backup = "";
    $l_s3riverbucket = "";
    $l_s3rivercname = "";
    $l_s3riverfile = "index.html";
    $l_s3rivertitle = "Community River";
    $l_s3rivertemplate = "";
    $l_s3redirectbucket = "";
    $l_tw_sys_key = "";
    $l_tw_sys_secret = "";
    $l_tw_sys_token = "";
    $l_tw_sys_tokensecret = "";
    $l_ipreflectorurl = "http://checkip.dyndns.com";
    $l_peoplesearch = 1;
    $l_opensignup = 0;
    $l_opensignupphrase = "";
    $l_backupencrypt = 0;
    $l_backupencryptpassword = "";
    $l_cg_session_hijack_checking = 0;
    $l_cg_paypal_enabled = FALSE;
    $l_cg_paypal_button_id = "";
    $l_cg_search_v2_enable = FALSE;


    //If there is already a config file, let's hang on to it
    if (file_exists($cfname)) {
        if ($action == "upgrade") {
            //Pull in the existing values
            $l_dblocalhost = $dbhost;
            $l_dbusername = $dbuser;
            $l_dbpassword = $dbpass;
            $l_fqdn = $system_fqdn;
            $l_s3key = $s3_sys_key;
            $l_s3secret = $s3_sys_secret;
            $l_s3bucket = $s3_sys_bucket;
            $l_s3cname = $s3_sys_cname;
            $l_s3backup = $s3_sys_backup;
            $l_tw_sys_key = $tw_sys_key;
            $l_tw_sys_secret = $tw_sys_secret;
            $l_tw_sys_token = $tw_sys_token;
            $l_tw_sys_tokensecret = $tw_sys_tokensecret;
            $l_s3riverbucket = $s3_sys_server_river_bucket;
            $l_s3rivercname = $s3_sys_server_river_cname;
            if (isset($s3_sys_server_redirect_bucket)) {
                $l_s3redirectbucket = $s3_sys_server_redirect_bucket;
            }
            if (isset($cg_external_ip_reflector_url)) {
                $l_ipreflectorurl = $cg_external_ip_reflector_url;
            }
            if (isset($s3_sys_server_river_file)) {
                $l_s3riverfile = $s3_sys_server_river_file;
            }
            if (isset($s3_sys_server_river_title)) {
                $l_s3rivertitle = $s3_sys_server_river_title;
            }
            if (isset($s3_sys_server_river_template)) {
                $l_s3rivertemplate = $s3_sys_server_river_template;
            }
            if (isset($cg_main_serverguid)) {
                $l_serverguid = $cg_main_serverguid;
            }
            if (isset($cg_peoplesearch)) {
                $l_peoplesearch = $cg_peoplesearch;
            }
            if (isset($cg_opensignup)) {
                $l_opensignup = $cg_opensignup;
            }
            if (isset($cg_opensignup_phrase)) {
                $l_opensignupphrase = $cg_opensignup_phrase;
            }
            if (isset($cg_backup_encrypt)) {
                $l_backupencrypt = $cg_backup_encrypt;
            }
            if (isset($cg_backup_encrypt_password)) {
                $l_backupencryptpassword = $cg_backup_encrypt_password;
            }
            if (isset($cg_session_hijack_checking)) {
                $l_cg_session_hijack_checking = $cg_session_hijack_checking;
            }
            if (isset($cg_paypal_enabled)) {
                $l_cg_paypal_enabled = $cg_paypal_enabled;
            }
            if (isset($cg_paypal_button_id)) {
                $l_cg_paypal_button_id = $cg_paypal_button_id;
            }
            if (isset($cg_search_v2_enable)) {
                $l_cg_search_v2_enable = $cg_search_v2_enable;
            }
        }

        copy($cfname, $cfname . '.old.' . time());
    }


    //Now read in the config file template
    $fh = fopen($cftemp, "r");
    $template = fread($fh, filesize($cftemp));
    fclose($fh);


    //Replace the tags
    $response = "";
    if ($silent == FALSE) {
        echo "What is your mysql hostname? [$l_dblocalhost]: ";
        $response = get_user_response();
    }
    if (empty($response) || $silent == TRUE) {
        $response = $l_dblocalhost;
    }
    $template = str_replace('[DBLOCALHOST]', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What is your mysql username? [$l_dbusername]: ";
        $response = get_user_response();
    }
    if (empty($response) || $silent == TRUE) {
        $response = $l_dbusername;
    }
    $template = str_replace('dbusernamegoeshere', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What is your mysql password? [$l_dbpassword]: ";
        $response = get_user_response();
    }
    if (empty($response) || $silent == TRUE) {
        $response = $l_dbpassword;
    }
    $template = str_replace('dbpasswordgoeshere', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What is the fully qualified hostname of your server? [$l_fqdn]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_fqdn;
    }
    $template = str_replace('domain.goes.here', $response, $template);
    $template = str_replace('fqdn.goes.here', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What is your Amazon S3 key? [$l_s3key]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3key;
    }
    $template = str_replace('s3keyvalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What is your Amazon S3 secret? [$l_s3secret]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3secret;
    }
    $template = str_replace('s3secretvalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What S3 bucket do you want user info stored in? [$l_s3bucket]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3bucket;
    }
    $template = str_replace('s3userbucketvalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "Do you have a dns CNAME pointed to this bucket? If so, what is it? [$l_s3cname]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3cname;
    }
    $template = str_replace('s3cnamevalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What S3 bucket do you want backups stored in? [$l_s3backup]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3backup;
    }
    $template = str_replace('s3backupbucketvalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "Do you have a bucket that will server as an external redirector to your server? [$l_s3redirectbucket]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3redirectbucket;
    }
    $template = str_replace('s3redirectbucket', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What url do you want to use as an external ip reflector service? [$l_ipreflectorurl]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_ipreflectorurl;
    }
    $template = str_replace('http://checkip.dyndns.com', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "Do you want a server-wide river? If so, what S3 bucket should we use? [$l_s3riverbucket]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3riverbucket;
    }
    $template = str_replace('s3riverbucketvalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "Does the server-wide river bucket have a dns CNAME pointed to it?  If so, what is it? [$l_s3rivercname]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3rivercname;
    }
    $template = str_replace('s3rivercnamevalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What filename do you use for the server-wide river html file? [$l_s3riverfile]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3riverfile;
    }
    $template = str_replace('s3riverfilevalue', $response, $template);


    $response = "";
    if ($silent == FALSE) {
        echo "What do you want the title of the server-wide river to be? [$l_s3rivertitle]: ";
        $response = get_user_response();
    }
    if (empty($response)) {
        $response = $l_s3rivertitle;
    }
    $template = str_replace('s3rivertitlevalue', $response, $template);

    //Preserve sys twitter values
    $template = str_replace('tw_sys_key = ""', 'tw_sys_key = "' . $l_tw_sys_key . '"', $template);
    $template = str_replace('tw_sys_secret = ""', 'tw_sys_secret = "' . $l_tw_sys_secret . '"', $template);
    $template = str_replace('tw_sys_token = ""', 'tw_sys_token = "' . $l_tw_sys_token . '"', $template);
    $template = str_replace('tw_sys_tokensecret = ""', 'tw_sys_tokensecret = "' . $l_tw_sys_tokensecret . '"', $template);

    //Preserve server river template
    if (!empty($l_s3rivertemplate)) {
        $template = str_replace('s3_sys_server_river_template = ""', 's3_sys_server_river_template = "' . $l_s3rivertemplate . '"', $template);
    }

    //Preserve server guid
    $template = str_replace('server.guid.goes.here', $l_serverguid, $template);

    //Preserve peoplesearch setting
    if ($l_peoplesearch == 0) {
        $template = str_replace('cg_peoplesearch=1', 'cg_peoplesearch=0', $template);
    }

    //Preserve backup encryption settings
    if ($l_backupencrypt == 1) {
        $template = str_replace('cg_backup_encrypt=0', 'cg_backup_encrypt=1', $template);
    }
    $template = str_replace('cg_backup_encrypt_password="backupencryptpassword"', 'cg_backup_encrypt_password="' . $l_backupencryptpassword . '"', $template);

    //Preserve open signup settings
    if ($l_opensignup == 1) {
        $template = str_replace('cg_opensignup=0', 'cg_opensignup=1', $template);
    }
    $template = str_replace('cg_opensignup_phrase="opensignupphrase"', 'cg_opensignup_phrase="' . $l_opensignupphrase . '"', $template);

    //Preserve session hijack setting check
    if ($l_cg_session_hijack_checking == 1) {
        $template = str_replace('cg_session_hijack_checking=0', 'cg_session_hijack_checking=1', $template);
    }

    //Paypal enabled?
    if ($l_cg_paypal_enabled == TRUE) {
        $template = str_replace('cg_paypal_enabled=false', 'cg_paypal_enabled=true', $template);
    }

    //Preserve paypal button id
    $template = str_replace('cg_paypal_button_id=""', 'cg_paypal_button_id="' . $l_cg_paypal_button_id . '"', $template);

    //Search v2 enabled?
    if ($l_cg_search_v2_enable == TRUE) {
        $template = str_replace('cg_search_v2_enable=false', 'cg_search_v2_enable=true', $template);
    }

    //Eliminate the newinstall flag if it's set
    if (!isset($cartularynewinstall)) {
        $template = str_replace('cartularynewinstall=1', "", $template);
    }

    //Build paths
    $template = str_replace('[PATHTOCART]', $l_pathtocart, $template);

    //Write the new config file
    $fh = fopen($cfname, "w+");
    fwrite($fh, $template);
    fclose($fh);

    //Log it
    add_admin_log_item("A new configuration file was created.", "Cartulary.conf generated.");

    //If this is an upgrade, then install a new cron job
    if ($action == "upgrade") {
        rename($cronloc, "/tmp/cartulary-cron.old." . time());
        $cmdtorun = "php $confroot/bin/syscheck.php upgrade";
        $output = `$cmdtorun`;
        echo $output;
    }

    //Log the upgrade
    if ($action == "upgrade") {
        add_admin_log_item("System was upgraded from version $version", "System Upgrade");
    }

    echo "\n";

    //Remove the lock file
    cronHelper::unlock();
}

// Log and leave
return (TRUE);