<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?include "$confroot/$templates/php_cgi_admin.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$prefs = array();
$jsondata = array();
$jsondata['prefname'] = "";

//Vars
$feedId = $_REQUEST['feedId'];

//Clean the user id
$feedId = substr($feedId, strlen("reset_"));

//Make sure we have some user id input to use
if(empty($feedId)) {
  //Log it
  loggit(2,"The feed id was missing.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The feed id was missing.";
  echo json_encode($jsondata);
  exit(1);
}

//Mark the feed as updated
mark_feed_as_updated($feedId);

//Log it
loggit(3,"Re-scan requested for: [$feedId].");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['status'] = "true";
$jsondata['description'] = "Marked feed for re-scan.";
echo json_encode($jsondata);

return(0);

?>

