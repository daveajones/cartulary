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
  loggit(2,"User attempted to set feed properties without being logged in first.");
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
if ( isset($_REQUEST['fid']) && !empty($_REQUEST['fid']) ) {
  $fid = $_REQUEST['fid'];
} else {
  //Log it
  loggit(2,"There was no feed id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No feed id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Change the feed
if( isset($_REQUEST['sticky']) ) {
  loggit(3,"User: [$uid] set feed: [$fid] as sticky.");
  $jsondata['description'] = "Feed is now sticky.";
  mark_feed_as_sticky($fid, $uid);
}
if( isset($_REQUEST['unsticky']) ) {
  loggit(3,"User: [$uid] set feed: [$fid] as NOT sticky.");
  $jsondata['description'] = "Feed is no longer sticky.";
  unmark_feed_as_sticky($fid, $uid);
}
if( isset($_REQUEST['hidden']) ) {
  loggit(3,"User: [$uid] set feed: [$fid] as hidden.");
  $jsondata['description'] = "Feed is now hidden.";
  mark_feed_as_hidden($fid, $uid);
}
if( isset($_REQUEST['unhidden']) ) {
  loggit(3,"User: [$uid] set feed: [$fid] as NOT hidden.");
  $jsondata['description'] = "Feed is no longer hidden.";
  unmark_feed_as_hidden($fid, $uid);
}

//Log it
loggit(3,"User: [$uid] set properties on feed: [$fid].");

//Give feedback that all went well
$jsondata['id'] = $fid;
$jsondata['status'] = "true";
echo json_encode($jsondata);

return(0);

?>
