<?
  // Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  include "$confroot/$includes/util.php";
  include "$confroot/$includes/auth.php";
  include "$confroot/$includes/feeds.php";
  include "$confroot/$includes/opml.php";
  include "$confroot/$includes/posts.php";
  include "$confroot/$includes/articles.php";

  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

    $cfname = "$confroot/conf/cartulary.conf";
    $cftemp = "$confroot/$templates/cartulary.conf";

    //If there is already a config file, let's hang on to it
    if( file_exists($cfname) ) {
      rename( $cfname, $cfname.'.old.'.time() );
    }

    //Now read in the config file template
    $fh = fopen($cftemp, "r");
    $template = fread($fh, filesize($cftemp));

    //Replace the tags
    echo "What is your mysql username? [required]";
    $response = get_user_response();
    $template = str_replace('dbusernamegoeshere', $response, $template);

    echo "What is your mysql password? [required]";
    $response = get_user_response();
    $template = str_replace('dbpasswordgoeshere', $response, $template);

    echo "What is the fully qualified hostname of your server? [required]";
    $response = get_user_response();
    $template = str_replace('domain.goes.here', $response, $template);
    $template = str_replace('fqdn.goes.here', $response, $template);

    echo "What is your Amazon S3 key? [can be blank]";
    $response = get_user_response();
    $template = str_replace('s3keyvalue', $response, $template);

    echo "What is your Amazon S3 secret? [can be blank]";
    $response = get_user_response();
    $template = str_replace('s3secretvalue', $response, $template);

    echo "What S3 bucket do you want user info stored in? [can be blank]";
    $response = get_user_response();
    $template = str_replace('s3userbucketvalue', $response, $template);

    echo "Do you have a dns CNAME pointed to this bucket? If so, what is it?  [can be blank]";
    $response = get_user_response();
    $template = str_replace('s3cnamevalue', $response, $template);

    echo "What S3 bucket do you want backups stored in? [can be blank]";
    $response = get_user_response();
    $template = str_replace('s3backupbucketvalue', $response, $template);


    //Close the template
    fclose($fh);

    //Write the new config file
    $fh = fopen($cfname, "w+");
    fwrite( $fh, $template );
    fclose($fh);

  //Remove the lock file
  cronHelper::unlock();
  }

  // Log and leave
  return(TRUE);
?>
