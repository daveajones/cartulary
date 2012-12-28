<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/posts.php";
include "$confroot/$includes/feeds.php";
include "$confroot/$includes/opml.php";
include "$confroot/$includes/articles.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to delete a post without being logged in first.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(0);
}
$uid = get_user_id_from_sid(is_logged_in());
if(empty($uid) || ($uid == FALSE)) {
  //Log it
  loggit(2,"Couldn't retrieve a user id for this session: [$sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(1);
}

//See if the user has activated their account yet
if(!is_user_active($uid)) {
  //Log it
  loggit(2,"User tried to access a page without activating first: [$uid | $sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the post id to unlink from this user
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
