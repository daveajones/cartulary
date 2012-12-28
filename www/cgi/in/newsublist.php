<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/feeds.php";
include "$confroot/$includes/opml.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Get the input
//if ( isset($_POST['newpref']) ) { $newpref = $_POST['newpref']; } else { $newpref = ""; };
$jsondata = array();
$jsondata['fieldname'] = "";


//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to create a new subscription list without being logged in first.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(0);
}
$uid = get_user_id_from_sid(is_logged_in());
if(empty($uid) || ($uid == FALSE)) {
  //Log it
  loggit(2,"Couldn't retrieve a user id for this session: [$sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(1);
}

//See if the user has activated their account yet
if(!is_user_active($uid)) {
  //Log it
  loggit(2,"User tried to access a page without activating first: [$uid | $sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the name of the list to create
$jsondata['fieldname'] = "title";
if ( isset($_REQUEST['title']) && !empty($_REQUEST['title']) ) {
  $title = trim($_REQUEST['title']);
} else {
  //Log it
  loggit(2,"There was no title. Can't create the list.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No title given.";
  echo json_encode($jsondata);
  exit(1);
};
//Make sure url is within limits
if( strlen($title) > 1024 ) {
  //Log it
  loggit(2,"The title is too long: [$title]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max title length is 1024 characters.";
  echo json_encode($jsondata);
  exit(1);
}


//Create the list
$url = build_reading_list($title, $uid);
if( $url == FALSE) {
  //Log it
  loggit(2,"The reading list couldn't be created: [$title]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "List creation failed.";
  echo json_encode($jsondata);
  exit(1);
}


//Log it
loggit(1,"User: [$uid] created a new subscription list.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['title'] = $title;
$jsondata['url'] = $url;
$jsondata['description'] = "List created.";
echo json_encode($jsondata);

return(0);

?>
