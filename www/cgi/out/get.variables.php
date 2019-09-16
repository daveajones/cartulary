<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

//Check for parameters
if(isset($_REQUEST['fid']) && is_numeric($_REQUEST['fid'])) {
    $fid = $_REQUEST['fid'];
} else {
    //Log it
    loggit(2,"There was no file id in the request.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No file id was given.";
    echo json_encode($jsondata);
    exit(1);
}

//Pull a list of variables for this file
$variables = get_recent_file_variables($g_uid, $fid);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['variables'] = $variables;
$jsondata['description'] = "Pulled variable list.";
echo json_encode($jsondata);

return(0);