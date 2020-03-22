<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

//Get the article id to unlink from this user
if ( isset($_REQUEST['aid']) ) {
  $aid = $_REQUEST['aid'];
} else {
  //Log it
  loggit(2,"There was no article id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No article id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Unlink the article
unlink_article($uid, $aid);

//Rebuild the static feed
build_rss_feed($uid);

//Log it
loggit(1,"User: [$uid] removed article: [$aid] from his catalog.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Article removed.";
echo json_encode($jsondata);

return(0);

?>
