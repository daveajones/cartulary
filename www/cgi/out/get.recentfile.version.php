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

if( !isset($_REQUEST['versionid']) || empty($_REQUEST['versionid']) || !is_numeric($_REQUEST['versionid'])) {
    //Give error back
    $jsondata['status'] = "false";
    $jsondata['description'] = "The version id requested was empty or corrupt.";
    echo json_encode($jsondata);
    exit(1);
}

//Get the version of the file requested
$version = get_recent_file_version_by_url($g_uid, $_REQUEST['url'], $_REQUEST['versionid']);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['file'] = $version;
$jsondata['description'] = "Historical version of file.";
echo json_encode($jsondata);

return(0);