<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
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
