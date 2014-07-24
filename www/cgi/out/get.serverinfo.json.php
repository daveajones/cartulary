<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

//The requestor must at least declare its address and guid
if( isset($_REQUEST['addr']) && !empty($_REQUEST['addr']) ) {
  $raddr = $_REQUEST['addr'];
} else {
  loggit(3, "Server info request didn't provide it's own address.");
  return(0);
}
if( isset($_REQUEST['guid']) && !empty($_REQUEST['guid']) ) {
  $rguid = $_REQUEST['guid'];
} else {
  loggit(3, "Server info request didn't provide it's own guid.");
  return(0);
}

//What should the server declaration be?
if( !empty($s3_sys_server_redirect_bucket) && !empty($cg_external_ip_reflector_url) ) {
  $serverdec = $s3_sys_server_redirect_bucket;
} else {
  $serverdec = $system_fqdn;
}

//Update this server's info in our database
update_server_address($rguid, $raddr);

//Echo current server info
loggit(3, "Server info request from: [$raddr | $rguid].");
$jsondata['addr'] = $serverdec;
$jsondata['guid'] = $cg_main_serverguid;

//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['time'] = time();
$jsondata['description'] = "Current server information.";
echo json_encode($jsondata);

return(0);