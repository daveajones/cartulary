<?
  //It's important that the river page not cache in the browser
  header("Cache-Control: no-cache, max-age=0, must-revalidate, no-store");
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();


//Get all the users in the system
$items = get_sticky_feed_items($g_uid);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['data']['items'] = $items;
$jsondata['status'] = "true";
$jsondata['description'] = "List of sticky river items.";
if( isset($_REQUEST['pretty']) ) {
    echo format_json(json_encode($jsondata));
} else {
    echo json_encode($jsondata);
}

return(0);