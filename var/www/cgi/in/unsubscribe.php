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

// Get the input
//if ( isset($_POST['newpref']) ) { $newpref = $_POST['newpref']; } else { $newpref = ""; };
$jsondata = array();
$jsondata['fieldname'] = "";


//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to delete a feed without being logged in first.");
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

//Get the id of the item being unsubscribed
$jsondata['fieldname'] = "fid";
if ( isset($_REQUEST['id']) ) {
  $id = $_REQUEST['id'];
  loggit(3,"User: [$uid] wants to unsubscribe from feed: [$id].");
} else {
  //Log it
  loggit(2,"There was no feed id given.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No feed id given.";
  echo json_encode($jsondata);
  exit(1);
};

//Is this an outline?
if( !is_array($id) && outline_exists_by_id($id) ) {
  //Log it
  loggit(3,"Id: [$id] refers to an outline.");
  $outline = get_outline_info($id);
  //if( $outline['type'] != 'sopml' ) {
  //  //Run through and unlink all the feeds from this outline
  //  $feeds = get_feeds_by_outline_id($id);
  //  foreach($feeds as $feed) {
  //    unlink_feed_from_outline($feed, $id);
  //  }
  //}
  unlink_outline_from_user($id, $uid);
  $jsondata['status'] = "true";
  $jsondata['description'] = "Outline unsubscribed.";
  echo json_encode($jsondata);
  exit(1);
}

//Is this an array of feed ids?
if( is_array($id) ) {
  loggit(3, "UNSUB: ".print_r($_REQUEST, TRUE));
  foreach($id as $feedid) {
    unlink_feed_from_user($uid, $feedid);
  }
} else {
  //Unlink this feed
  unlink_feed_from_user($uid, $id);
}

//Rebuild the users river
//update_river($uid, build_river($uid));


//Log it
loggit(1,"User: [$uid] un-subscribed from feed: [$id].");
$jsondata['fid'] = $id;

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Un-subscribed.";
echo json_encode($jsondata);

return(0);

?>
