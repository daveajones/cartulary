<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

    //If this is being run as an upgrade
    $action = "";
    if( isset($argv[1]) ) {
      $action = $argv[1];
    }

    $cfname = "$confroot/conf/cartulary.conf";
    $cftemp = "$confroot/$templates/cartulary.conf";

    //Default values
    $l_serverguid = random_gen(64);
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
    $l_rsscloud = 0;

    //If there is already a config file, let's hang on to it
    if( file_exists($cfname) ) {
      if( $action = "upgrade" ) {
        //Pull in the existing values
        $l_dbusername = $dbuser;
        $l_dbpassword = $dbpass;
        $l_fqdn = $system_fqdn;
        $l_s3key = $s3_sys_key;
        $l_s3secret = $s3_sys_secret;
        $l_s3bucket = $s3_sys_bucket;
        $l_s3cname = $s3_sys_cname;
        $l_s3backup = $s3_sys_backup;
        $l_s3riverbucket = $s3_sys_server_river_bucket;
        $l_s3rivercname = $s3_sys_server_river_cname;
        if( isset($s3_sys_server_river_file) ) {
          $l_s3riverfile = $s3_sys_server_river_file;
        }
        if( isset($s3_sys_server_river_title) ) {
          $l_s3rivertitle = $s3_sys_server_river_title;
        }
        if( isset($cg_main_serverguid) ) {
          $l_serverguid = $cg_main_serverguid;
        }
        $l_rsscloud = $enable_rsscloud;
      }

      copy( $cfname, $cfname.'.old.'.time() );
    }

    //Now read in the config file template
    $fh = fopen($cftemp, "r");
    $template = fread($fh, filesize($cftemp));
    fclose($fh);

    //Replace the tags
    echo "What is your mysql username? [$l_dbusername]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_dbusername; }
    $template = str_replace('dbusernamegoeshere', $response, $template);

    echo "What is your mysql password? [$l_dbpassword]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_dbpassword; }
    $template = str_replace('dbpasswordgoeshere', $response, $template);

    echo "What is the fully qualified hostname of your server? [$l_fqdn]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_fqdn; }
    $template = str_replace('domain.goes.here', $response, $template);
    $template = str_replace('fqdn.goes.here', $response, $template);

    echo "What is your Amazon S3 key? [$l_s3key]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3key; }
    $template = str_replace('s3keyvalue', $response, $template);

    echo "What is your Amazon S3 secret? [$l_s3secret]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3secret; }
    $template = str_replace('s3secretvalue', $response, $template);

    echo "What S3 bucket do you want user info stored in? [$l_s3bucket]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3bucket; }
    $template = str_replace('s3userbucketvalue', $response, $template);

    echo "Do you have a dns CNAME pointed to this bucket? If so, what is it? [$l_s3cname]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3cname; }
    $template = str_replace('s3cnamevalue', $response, $template);

    echo "What S3 bucket do you want backups stored in? [$l_s3backup]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3backup; }
    $template = str_replace('s3backupbucketvalue', $response, $template);

    echo "Do you want a server-wide river? If so, what S3 bucket should we use? [$l_s3riverbucket]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3riverbucket; }
    $template = str_replace('s3riverbucketvalue', $response, $template);

    echo "Does the server-wide river bucket have a dns CNAME pointed to it?  If so, what is it? [$l_s3rivercname]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3rivercname; }
    $template = str_replace('s3rivercnamevalue', $response, $template);

    echo "What filename do you use for the server-wide river html file? [$l_s3riverfile]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3riverfile; }
    $template = str_replace('s3riverfilevalue', $response, $template);

    echo "What do you want the title of the server-wide river to be? [$l_s3rivertitle]: ";
    $response = get_user_response();
    if( empty($response) ) { $response = $l_s3rivertitle; }
    $template = str_replace('s3rivertitlevalue', $response, $template);

    //Preserve server guid
    $template = str_replace('server.guid.goes.here', $l_serverguid, $template);

    //Preserve rssCloud setting
    if( $l_rsscloud == 1 ) {
      $template = str_replace('enable_rsscloud=0', 'enable_rsscloud=1', $template);
    }

    //Eliminate the newinstall flag if it's set
    if( !isset($cartularynewinstall) ) {
      $template = str_replace('cartularynewinstall=1', "", $template);
    }

    //Write the new config file
    $fh = fopen($cfname, "w+");
    fwrite( $fh, $template );
    fclose($fh);

    //Log it
    add_admin_log_item("A new configuration file was created.", "Cartulary.conf generated.");

    //If this is an upgrade, then install a new cron job
    if( $action == "upgrade" ) {
      rename( $cronloc, "/tmp/cartulary-cron.old.".time() );
      $cmdtorun = "php $confroot/bin/syscheck.php upgrade";
      $output = `$cmdtorun`;
      echo $output;
    }

    //Log the upgrade
    if( $action == "upgrade" ) {
      add_admin_log_item("System was upgraded from version $version", "System Upgrade");
    }

  //Remove the lock file
  cronHelper::unlock();
  }

  // Log and leave
  return(TRUE);
?>
