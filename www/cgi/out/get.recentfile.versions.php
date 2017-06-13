<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

if( !isset($_REQUEST['url']) || empty($_REQUEST['url']) ) {
    //Give error back
    $jsondata['status'] = "false";
    $jsondata['description'] = "The file url requested was empty or corrupt.";
    echo json_encode($jsondata);
    exit(1);
}

//Pull a list of recently edited files for this user
$versions = get_recent_file_versions_by_url($g_uid, $_REQUEST['url']);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['versions'] = $versions;
$jsondata['description'] = "History versions for file.";
echo json_encode($jsondata);

return(0);