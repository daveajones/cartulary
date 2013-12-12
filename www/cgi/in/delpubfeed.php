<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

//Get the feed url to unlink from this user
if ( isset($_REQUEST['url']) ) {
  $url = $_REQUEST['url'];
} else {
  //Log it
  loggit(2,"There was no feed url in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No feed url was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Unlink the feed from this user
if( !del_pub_feed($uid, $url) ) {
  //Log it
  loggit(2, "Failed to remove pub feed: [$url] from user: [$uid]'s social outline.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Failed to remove pub feed.";
  echo json_encode($jsondata);
  return(0);
}

//Rebuild the social outline
build_social_outline($uid);
loggit(1, "User: [$uid]'s social outline was rebuilt.");

//Log it
loggit(1,"User: [$uid] removed pub feed: [$url] from his social outline.");

//Give feedback that all went well
$jsondata['url'] = $url;
$jsondata['status'] = "true";
$jsondata['description'] = "Feed removed.";
echo json_encode($jsondata);

return(0);

?>
