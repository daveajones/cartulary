<?
  // Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  include "$confroot/$includes/util.php";
  include "$confroot/$includes/auth.php";
  include "$confroot/$includes/admin.php";
  include "$confroot/$includes/feeds.php";
  include "$confroot/$includes/opml.php";
  include "$confroot/$includes/posts.php";
  include "$confroot/$includes/articles.php";

  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

    $cfname = "$confroot/conf/cartulary.conf";
    $cftemp = "$confroot/$templates/cartulary.conf";

    //Default values
    $l_dbusername = "cartulary";
    $l_dbpassword = "cartulary";
    $l_fqdn = "localhost";
    $l_s3key = "";
    $l_s3secret = "";
    $l_s3bucket = "";
    $l_s3cname = "";
    $l_s3backup = "";

    //If there is already a config file, let's hang on to it
    if( file_exists($cfname) ) {
      if( !isset($cartularynewinstall) ) {
        //Pull in the existing values
        $l_dbusername = $dbuser;
        $l_dbpassword = $dbpass;
        $l_fqdn = $system_fqdn;
        $l_s3key = $s3_sys_key;
        $l_s3secret = $s3_sys_secret;
        $l_s3bucket = $s3_sys_bucket;
        $l_s3cname = $s3_sys_cname;
        $l_s3backup = $s3_sys_backup;
      }

      rename( $cfname, $cfname.'.old.'.time() );
    }

    //Now read in the config file template
    $fh = fopen($cftemp, "r");
    $template = fread($fh, filesize($cftemp));

    //Replace the tags
    echo "What is your mysql username? [$l_dbusername]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_dbusername;  }
    $template = str_replace('dbusernamegoeshere', $response, $template);

    echo "What is your mysql password? [$l_dbpassword]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_dbpassword;  }
    $template = str_replace('dbpasswordgoeshere', $response, $template);

    echo "What is the fully qualified hostname of your server? [$l_fqdn]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_fqdn;  }
    $template = str_replace('domain.goes.here', $response, $template);
    $template = str_replace('fqdn.goes.here', $response, $template);

    echo "What is your Amazon S3 key? [$l_s3key]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_s3key;  }
    $template = str_replace('s3keyvalue', $response, $template);

    echo "What is your Amazon S3 secret? [$l_s3secret]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_s3secret;  }
    $template = str_replace('s3secretvalue', $response, $template);

    echo "What S3 bucket do you want user info stored in? [$l_s3bucket]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_s3bucket;  }
    $template = str_replace('s3userbucketvalue', $response, $template);

    echo "Do you have a dns CNAME pointed to this bucket? If so, what is it?  [$l_s3cname]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_s3cname;  }
    $template = str_replace('s3cnamevalue', $response, $template);

    echo "What S3 bucket do you want backups stored in? [$l_s3backup]: ";
    $response = get_user_response();
    if( empty($response) ) {  $response = $l_s3backup;  }
    $template = str_replace('s3backupbucketvalue', $response, $template);


    //Close the template
    fclose($fh);

    //Write the new config file
    $fh = fopen($cfname, "w+");
    fwrite( $fh, $template );
    fclose($fh);

    //Log it
    add_admin_log_item("The server had no configuration file. A fresh one was created.", "Cartulary.conf generated.");


  //Remove the lock file
  cronHelper::unlock();
  }

  // Log and leave
  return(TRUE);
?>
