<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?include "$confroot/$templates/php_cgi_admin.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();


//Get all the users in the system
$users = get_users();


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['data']['users'] = $users;
$jsondata['status'] = "true";
$jsondata['description'] = "List of users.";
echo json_encode($jsondata);

return(0);

?>

