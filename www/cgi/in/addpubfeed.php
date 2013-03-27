<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

$jsondata = array();
$jsondata['fieldname'] = "";


//Get the url of the feed
$jsondata['fieldname'] = "url";
if ( isset($_REQUEST['url']) ) {
  $url = $_REQUEST['url'];
} else {
  //Log it
  loggit(2,"There was no url. Can't add this feed.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No URL given.";
  echo json_encode($jsondata);
  exit(1);
};
//Make sure url is within limits
if( strlen($url) > 254 ) {
  //Log it
  loggit(2,"The url is too long: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max url length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
//Is the feed even valid?
$url = get_final_url($url);
$content = fetchUrl($url);
if( $content == FALSE ) {
  //Log it
  loggit(2,"Getting this url failed: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Whoa there! That's a bad feed url.";
  echo json_encode($jsondata);
  exit(1);
}

//Test if the feed has a valid structure
if( !feed_is_valid($content) ) {
  //Log it
  loggit(2,"This feed doesn't look right: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Whoa there! That feed looks broken.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the feed title
$title = get_feed_title($content);

//Get the feed link
$link = get_feed_link($content);

//Add this pub feed to the pub feed table
add_pub_feed($url, $uid, $title, $link);

//Re-build social outline
build_social_outline($uid);

//Log it
loggit(1,"User: [$uid] added a new pub feed to their outline.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['title'] = $title;
$jsondata['url'] = $url;
$jsondata['description'] = "Feed added.";
echo json_encode($jsondata);

return(0);

?>
