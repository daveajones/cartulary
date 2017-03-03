<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

//Was a max value specified?
if( isset($_REQUEST['max']) ) {
  $max = $_REQUEST['max'];
} else {
  $max = $default_max_list;
}

//Was a range start specified?
if( isset($_REQUEST['start']) ) {
  $start = $_REQUEST['start'];
} else {
  $start = 0;
}

//Get all the users in the system
$items = get_feed_items_with_enclosures2($g_uid, NULL, $max, $start, 2, TRUE);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['data']['items'] = $items;
$jsondata['status'] = "true";
$jsondata['description'] = "List of river items containing audio enclosures.";
echo json_encode($jsondata);

return(0);