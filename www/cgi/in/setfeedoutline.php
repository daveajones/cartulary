<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

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
