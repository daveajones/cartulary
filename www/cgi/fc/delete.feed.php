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

// Vars
$feedId = $_REQUEST['feedId'];

//Clean the feed id
$feedId = substr($feedId, strlen("delete_"));

// Make sure we have some feed id input to use
if( empty($feedId) ) {
  //Log it
  loggit(2,"The feed id was missing.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The feed id was missing.";
  echo json_encode($jsondata);
  exit(1);
}

//Delete the feed
delete_feed($feedId);

//Log it
loggit(3, "Feed deleted: [$feedId].");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['feedid'] = $feedId;
$jsondata['status'] = "true";
$jsondata['description'] = "Feed was deleted.";
echo json_encode($jsondata);

return(0);

?>

