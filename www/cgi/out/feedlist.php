<?
// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/feeds.php";
include "$confroot/$includes/opml.php";

// Valid session?
if(!is_logged_in()) {
  loggit(2,"A non-authenticated user tried to see the feed list.");
  exit(0);
}

// Json header
//header("Content-Type: text/plain");
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Vars
$uid = get_user_id_from_sid(get_session_id());
$jsondata = array();

// Get this user's feeds
$feeds = get_feeds($uid);
if($feeds == FALSE) {
  $jsondata['status'] = "nofeeds";
  $jsondata['description'] = "List of feeds.";
  $jsondata['feeds'] = 0;
  echo json_encode($jsondata);
  loggit(1,"User: [$uid] has a blank feed list.");
  exit(0);
}

// Get this user's outlines
$outlines = get_outlines($uid);
if($outlines == FALSE) {
  $jsondata['outlines'] = 0;
  loggit(1,"User: [$uid] has a blank outline list.");
}

// Dump the stats
$jsondata['status'] = "true";
$jsondata['description'] = "List of feeds.";
$jsondata['feeds'] = $feeds;
$jsondata['outlines'] = $outlines;
echo json_encode($jsondata);
loggit(1,"Sending feed list for user: [$uid]");

?>
