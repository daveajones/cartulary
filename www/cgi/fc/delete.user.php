<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/admin.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$prefs = array();
$jsondata = array();
$jsondata['prefname'] = "";

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to hit an admin page without being logged in first.");
  header("Location: $loginpage");
  exit(0);
}
$uid = get_user_id_from_sid(is_logged_in());
if(empty($uid) || ($uid == FALSE)) {
  //Log it
  loggit(2,"Couldn't retrieve a user id for this session: [$sid].");
  header("Location: $loginerrorpage?code=3");
  exit(1);
}

//See if the user has activated their account yet
if(!is_user_active($uid)) {
  //Log it
  loggit(2,"User tried to access a page without activating first: [$uid | $sid].");
  header("Location: $errorpage?code=9");
  exit(1);
}

//User must be an admin to do this action
if(!is_admin($uid)) {
  //Log it
  loggit(2,"A non-admin user tried to access an admin only page: [$uid | $sid].");
  header("Location: $errorpage?code=9");
  exit(1);
}

//--------------------------------------------------------------------------------


// Vars
$userId = $_REQUEST['userId'];

//Clean the user id
$userId = substr($userId, strlen("delete_"));

// Make sure we have some user id input to use
if(empty($userId)) {
  //Log it
  loggit(2,"The user id was missing.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The user id was missing.";
  echo json_encode($jsondata);
  exit(1);
}

// Make sure that by doing this we aren't going to be deleting the last admin user
if( (count(get_admin_users()) <= 1) && (is_admin($userId)) ) {
  //Log it
  loggit(2,"Can't delete this user since it's the last admin.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Can't delete the last admin user.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the user's name so we can intelligently respond to the deleter
$userName = get_user_name_from_uid($userId);

//Delete the user
delete_user_by_user_id($userId);

//Log it
loggit(1,"User deleted: [$userId | $userName].");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['userid'] = $userId;
$jsondata['status'] = "true";
$jsondata['description'] = "$userName was deleted.";
echo json_encode($jsondata);

return(0);

?>

