<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<?
if ($g_platform == "mobile") {
    $mobile = TRUE;
} else {
    $mobile = FALSE;
}

$pretty = FALSE;
if (isset($_REQUEST['pretty'])) {
    $pretty = TRUE;
}

if (isset($_REQUEST['url'])) {
    $jsondata = @file_get_contents($_REQUEST['url']);
} else {
    $jsondata = "onGetRiverStream(" . get_river_as_json($uid, $mobile, $pretty) . ")";
}

$section = "River";
$tree_location = "River (JSON)";

header("Content-Type: application/javascript");
echo $jsondata;