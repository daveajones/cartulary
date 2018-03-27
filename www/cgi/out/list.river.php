<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

// Get the river
if( isset($_REQUEST['flat']) ) {
    loggit(3, "River being built in real time with get_river_feed_items()");
    $river = get_river_feed_items($g_uid);
} else {
    $river = get_river($g_uid);
}

// Output
$jsondata['status'] = "true";
$jsondata['description'] = "News river.";
$jsondata['data']['items'] = $river;
if( isset($_REQUEST['pretty']) ) {
    echo format_json(json_encode(utf8ize($jsondata)));
} else {
    echo json_encode( utf8ize($jsondata));
}
loggit(3,"DEBUG: Sending news river for user: [$uid]");