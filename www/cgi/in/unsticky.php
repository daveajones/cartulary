<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

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
