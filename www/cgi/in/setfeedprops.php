<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

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
if( isset($_REQUEST['fulltext']) ) {
  loggit(3,"User: [$uid] set feed: [$fid] as fulltext.");
  $jsondata['description'] = "Feed is now fulltext.";
  mark_feed_as_fulltext($fid, $uid);
}
if( isset($_REQUEST['unfulltext']) ) {
  loggit(3,"User: [$uid] set feed: [$fid] as NOT fulltext.");
  $jsondata['description'] = "Feed is no longer fulltext.";
  unmark_feed_as_fulltext($fid, $uid);
}

//Log it
loggit(3,"User: [$uid] set properties on feed: [$fid].");

//Give feedback that all went well
$jsondata['id'] = $fid;
$jsondata['status'] = "true";
echo json_encode($jsondata);

return(0);

?>
