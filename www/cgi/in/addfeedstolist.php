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
if ( isset($_REQUEST['fid']) && is_array($_REQUEST['fid']) ) {
  $fid = $_REQUEST['fid'];
} else {
  //Log it
  loggit(2,"There were no feed ids in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No feed ids were given.";
  echo json_encode($jsondata);
  exit(1);
};

//Get the outline id to change
if ( isset($_REQUEST['oid']) && is_array($_REQUEST['oid']) ) {
  $oid = $_REQUEST['oid'];
} else {
  //Log it
  loggit(2,"There were no outline ids in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No outline ids were specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Change the feeds
foreach($fid as $feed) {
  foreach($oid as $outline) {
    $oi = get_outline_info($outline);
    loggit(3, "SUBMOVE: List: [$outline] is owned by: [".$oi['ownerid']."]");
    if($oi['ownerid'] == $uid) {
      link_feed_to_list($feed, $outline);
    } else {
      loggit(2, "User: [$uid] tried to assign feed: [$feed] to list: [$outline], but they are not the owner.");
    }
  }
}
loggit(3, "SUBMOVE: ".print_r($_REQUEST, TRUE));

//Rebuild the reading lists
foreach($oid as $outline) {
  $oi = get_outline_info($outline);
  build_reading_list($oi['title'], $uid, $outline);
}

//Log it
loggit(1,"User: [$uid] set feed: [$fid] as linked to outline: [$oid].");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Feed assignment complete.";
echo json_encode($jsondata);

return(0);

?>
