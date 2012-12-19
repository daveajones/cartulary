<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/feeds.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to unsticky an item without being logged in first.");
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

//Get the feed id to change
if ( isset($_REQUEST['id']) ) {
  $id = $_REQUEST['id'];
} else {
  //Log it
  loggit(2,"There was no item id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No item id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Change the item
unmark_feed_item_as_sticky($id, $uid);
mark_river_as_updated($uid);

//Log it
loggit(1,"User: [$uid] marked item: [$id] as unsticky.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Unsticky successful.";
echo json_encode($jsondata);

return(0);

?>
