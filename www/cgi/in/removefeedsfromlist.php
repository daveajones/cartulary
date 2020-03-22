<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
$jsondata['fieldname'] = "";

//Get the ids of the items being unsubscribed
$jsondata['fieldname'] = "ids";
if ( isset($_REQUEST['ids']) ) {
  $ids = $_REQUEST['ids'];
  loggit(3,"User: [$uid] wants to remove these feeds from their lists: [$ids].");
} else {
  //Log it
  loggit(2,"There was no id array given.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No ids given.";
  echo json_encode($jsondata);
  exit(1);
};


//Is this an array of feed and list ids?
loggit(3, "DEBUG: Request array: ".print_r($_REQUEST, TRUE));
if( is_array($ids) ) {
  foreach($ids as $listid=>$feeds) {
    foreach($feeds as $list=>$feedid) {
      loggit(3, "DEBUG: Removing [$feedid] from [$listid].");
      unlink_feed_from_list($feedid, $listid);
    }
  }
}

//Rebuild the affected reading lists
foreach($ids as $listid=>$feeds) {
  $oi = get_outline_info($listid);
  build_reading_list($oi['title'], $uid, $listid);
}


//Log it
loggit(1,"User: [$uid] modified these lists: [$ids].");
$jsondata['fid'] = $id;

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Lists modified.";
echo json_encode($jsondata);

return(0);

?>
