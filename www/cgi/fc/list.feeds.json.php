<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?include "$confroot/$templates/php_cgi_admin.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();


//Get all of the feeds in the system
$feeds = get_all_feeds_with_stats(50);
$feedcount = count($feeds);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['data']['feeds'] = $feeds;
$jsondata['status'] = "true";
$jsondata['description'] = "List of feeds.";
echo json_encode($jsondata);

return(0);

?>

