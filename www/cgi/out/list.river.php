<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

// Get the river
$river = get_river($uid);

// Output
$jsondata['status'] = "true";
$jsondata['description'] = "News river.";
$jsondata['river'] = $river;
if( isset($_REQUEST['pretty']) ) {
    echo format_json(json_encode($jsondata));
} else {
    echo json_encode($jsondata);
}
loggit(1,"Sending news river for user: [$uid]");