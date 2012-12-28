<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/feeds.php";
include "$confroot/$includes/opml.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to set feed outline without being logged in first.");
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
if ( isset($_REQUEST['fid']) ) {
  $fid = $_REQUEST['fid'];
} else {
  //Log it
  loggit(2,"There was no feed id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No feed id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Get the outline id to change
if ( isset($_REQUEST['oid']) ) {
  $oid = $_REQUEST['oid'];
} else {
  //Log it
  loggit(2,"There was no outline id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No outline id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Change the feed
link_feed_to_outline($fid, $oid, $uid);
$feed = get_feed_info($fid);
$jsondata['element'] = '<li class="feedItem" data-id="'.$feed['id'].'" data-title="'.$feed['title'].'"><a class="aUnsubscribe" href="/cgi/in/unsubscribe?id='.$feed['id'].'"><img class="icon-remove" src="/images/blank.gif" /></a><a href="'.$feed['url'].'">'.$feed['title'].'</a></li>';

//Rebuild the reading list
$outline = get_outline_info($oid);
build_reading_list($outline['title'], $uid, $oid);

//Log it
loggit(1,"User: [$uid] set feed: [$fid] as linked to outline: [$oid].");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Feed moved.";
$jsondata['oid'] = $oid;
$jsondata['fid'] = $fid;
echo json_encode($jsondata);

return(0);

?>
