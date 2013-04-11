<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?include "$confroot/$templates/php_cgi_admin.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$prefs = array();
$jsondata = array();
$jsondata['prefname'] = "";

//Vars
$userId = $_REQUEST['userId'];

//Clean the user id
$userId = substr($userId, strlen("reset_"));

//Make sure we have some user id input to use
if(empty($userId)) {
  //Log it
  loggit(2,"The user id was missing.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The user id was missing.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the user's name so we can intelligently respond to the deleter
$userName = get_user_name_from_uid($userId);

//Delete the user
$newPassword = random_gen(12);
set_password($userId, $newPassword);
send_new_password_email(get_email_from_uid($userId), $newPassword);

//Log it
loggit(1,"Password reset for user: [$userId].");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['status'] = "true";
$jsondata['description'] = "New password for user $userName:  $newPassword";
echo json_encode($jsondata);

return(0);

?>

