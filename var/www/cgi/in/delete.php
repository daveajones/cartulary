<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/articles.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to delete an article without being logged in first.");
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

//Get the article id to unlink from this user
if ( isset($_REQUEST['aid']) ) {
  $aid = $_REQUEST['aid'];
} else {
  //Log it
  loggit(2,"There was no article id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No article id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Unlink the article
unlink_article($uid, $aid);

//Rebuild the static feed
build_rss_feed($uid);

//Log it
loggit(1,"User: [$uid] removed article: [$aid] from his catalog.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Article removed.";
echo json_encode($jsondata);

return(0);

?>
