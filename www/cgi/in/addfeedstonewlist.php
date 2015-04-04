<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

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

//Get the name of the new list
if ( isset($_REQUEST['listname']) ) {
  $title = $_REQUEST['listname'];
} else {
  //Log it
  loggit(2,"There were no list name in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No list name was given.";
  echo json_encode($jsondata);
  exit(1);
};

//Create the new list
$oid = build_reading_list($title, $uid);
if( $oid == FALSE) {
  //Log it
  loggit(2,"The list couldn't be created: [$title]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "List creation failed.";
  echo json_encode($jsondata);
  exit(1);
}
loggit(3, "DEBUG: Reading list id: [$oid].");

//Change the feeds
foreach($fid as $feed) {
  loggit(3, "DEBUG: Adding feed: [$feed] to new list: [$title].");
  link_feed_to_list($feed, $oid);
}

//Rebuild the reading lists
build_reading_list($title, $uid, $oid);

//Log it
loggit(1,"User: [$uid] created list: [$title] and linked feeds: [$fid] to it.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "List created.";
echo json_encode($jsondata);

return(0);