<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

//Check for parameters
if(isset($_REQUEST['type']) && is_numeric($_REQUEST['type'])) {
    $type = $_REQUEST['type'];
} else {
    $type = -1;
}

//Pull a list of recently edited files for this user
$files = get_recent_files($g_uid, 30, $type);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['files'] = $files;
$jsondata['description'] = "Pulled recent files list.";
echo json_encode($jsondata);

return(0);