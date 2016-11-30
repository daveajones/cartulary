<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

//Get the feed id to change
if ( !isset($g_uid) ) {
  //Log it
  loggit(2,"There was no user id found for the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No user id could be determined.";
  echo json_encode($jsondata);
  exit(1);
};

//Change the item
unmark_all_feed_items_as_sticky($g_uid);

//Log it
loggit(3,"User: [$g_uid] marked all of their items as unsticky.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Unsticky successful.";
echo json_encode($jsondata);

return(0);