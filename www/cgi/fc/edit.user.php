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

//Get the form input
$userId = $_REQUEST['hidEditUserId'];
$userName = $_REQUEST['txtEditUserName'];
$userEmail = $_REQUEST['txtEditUserEmail'];
$userStage = $_REQUEST['txtEditUserStage'];
$userBadLogins = $_REQUEST['txtEditUserBadLogins'];

//Clean the user id
$userId = substr($userId, strlen("edit_"));

// Make sure we have some user id input to use
if(empty($userId)) {
  //Log it
  loggit(2,"The user id was missing.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The user id was missing.";
  echo json_encode($jsondata);
  exit(1);
}

// Make sure that the user input we were given is not empty
if(empty($userName) || empty($userEmail)) {
  //Log it
  loggit(2,"The user name or email was missing: name=[" + $userName + "] email=[" + $userEmail + "]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Neither the name or email are allowed to be blank.";
  echo json_encode($jsondata);
  exit(1);
}

// Make sure that the user input we were given is sane
if(!filter_var($userEmail,FILTER_VALIDATE_EMAIL)) {
  //Log it
  loggit(2,"The email entered looks wierd: [" + $userEmail + "]. Rejecting.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The email address entered looks malformed. Please check it.";
  echo json_encode($jsondata);
  exit(1);
}
if(strlen($userName) > 128) {
  //Log it
  loggit(2,"The user name entered looks too long: [" + $userName + "]. Rejecting.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Surely the user's name is not this long.  Please check your input.";
  echo json_encode($jsondata);
  exit(1);
}

//Make sure the stage value is sane
if((strlen($userStage) > 1) || ($userStage > 4) || ($userStage < 0)) {
  //Log it
  loggit(2,"The stage entered looks too long: [" + $userStage + "]. Rejecting.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The stage you entered isn't an acceptable value. Only 0-4 are accepted.";
  echo json_encode($jsondata);
  exit(1);
} else {
  set_activation_stage($userId, $userStage);
}

//If the admin value exists then this user is an admin
if( isset($_REQUEST['chkEditUserAdmin']) ) {
  set_user_as_admin($userId);
} else {
  unset_user_as_admin($userId);
}

//Set activation status
if( isset($_REQUEST['txtEditUserActivated']) ) {
  activate_user($userId);
} else {
  deactivate_user($userId);
}

loggit(3, "User id to be changed: [$userId] to stage: [$userStage], email: [$userEmail].");


//Get the user's name so we can intelligently respond to the editor
$userOldName = get_user_name_from_uid($userId);

//Change the user
set_identity_by_user_id($userId, $userName);
set_email($userId, $userEmail);
badlogin_set($userEmail, $userBadLogins);

//Log it
loggit(3,"Details changed for user: [$userId]. Identity: [$userName] Email: [$userEmail]");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['userid'] = $userId;
$jsondata['status'] = "true";
$jsondata['description'] = "$userName was modified.";
echo json_encode($jsondata);

return(0);

?>

