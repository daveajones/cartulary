<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
$jsondata['fieldname'] = "";

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


//Log it
loggit(1,"User: [$uid] un-subscribed from feed: [$id].");
$jsondata['fid'] = $id;

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Un-subscribed.";
echo json_encode($jsondata);

return(0);

?>
