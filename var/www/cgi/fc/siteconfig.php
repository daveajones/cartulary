<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$prefs = array();
$jsondata = array();
$jsondata['prefname'] = "";

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to hit the set password page without being logged in first.");
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





//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['status'] = "true";
$jsondata['description'] = "Not implemented yet.";
echo json_encode($jsondata);

return(0);

?>

