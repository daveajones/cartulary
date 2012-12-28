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
    echo "What is your mysql username? ";
    $response = get_user_response();
    $template = str_replace('dbusernamegoeshere', $response, $template);

    echo "What is your mysql password? ";
    $response = get_user_response();
    $template = str_replace('dbpasswordgoeshere', $response, $template);

    echo "What is the fully qualified hostname of your server? ";
    $response = get_user_response();
    $template = str_replace('domain.goes.here', $response, $template);
    $template = str_replace('fqdn.goes.here', $response, $template);

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
