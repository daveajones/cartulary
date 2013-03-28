<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

//Echo current server info
$jsondata['guid'] = $cg_main_serverguid;

//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['time'] = time();
$jsondata['description'] = "Current server information.";
echo json_encode($jsondata);

return(0);

?>

