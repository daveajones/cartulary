<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

//Get the initial set of prefs to make sure we have a good set of data to alter
if($prefs == FALSE) {
  $prefs = init_user_prefs($uid);
}
$oldprefs = $prefs;


loggit(3, "Pre-prefs: ".print_r($prefs, TRUE));

// Get the input
//if ( isset($_POST['newpref']) ) { $newpref = $_POST['newpref']; } else { $newpref = ""; };
if ( isset($_POST['s3bucket']) ) { $s3bucket = $_POST['s3bucket']; } else { $s3bucket = ""; };
if ( isset($_POST['s3key']) ) { $s3key = $_POST['s3key']; } else { $s3key = ""; };
if ( isset($_POST['s3secret']) ) { $s3secret = $_POST['s3secret']; } else { $s3secret = ""; };
if ( isset($_POST['s3cname']) ) { $s3cname = $_POST['s3cname']; } else { $s3cname = ""; };
$jsondata = array();
$jsondata['prefname'] = "";

//Clean the input of any whitespace since it was probably a cut/paste accident
$s3key = preg_replace("/\s+/", "", $s3key);
$s3secret = preg_replace("/\s+/", "", $s3secret);
$twitterkey = preg_replace("/\s+/", "", $twitterkey);
$twittersecret = preg_replace("/\s+/", "", $twittersecret);
$twittertoken = preg_replace("/\s+/", "", $twittertoken);
$twittertokensecret = preg_replace("/\s+/", "", $twittertokensecret);

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to hit the set prefs page without being logged in first.");
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


// Does it meet the value requirements
$jsondata['prefname'] = "s3bucket";
if( strlen($s3bucket) > 254 ) {
  //Log it
  loggit(2,"The value for the s3bucket pref was too long: [$s3bucket]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max bucket name length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['s3bucket'] = $s3bucket;

$jsondata['prefname'] = "s3key";
if( strlen($s3key) > 254 ) {
  //Log it
  loggit(2,"The value for s3key pref was too long: [$s3key]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max key length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['s3key'] = $s3key;

$jsondata['prefname'] = "s3secret";
if( strlen($s3secret) > 254 ) {
  //Log it
  loggit(2,"The value for s3secret pref was too long: [$s3secret]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max secret length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['s3secret'] = $s3secret;

$jsondata['prefname'] = "s3cname";
if( strlen($s3cname) > 254 ) {
  //Log it
  loggit(2,"The value for s3cname pref was too long: [$s3cname]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max S3 cname length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['s3cname'] = $s3cname;


//Check key credentials to make sure they are correct and warn the user if not
$jsondata['prefname'] = "s3key";
if(!empty($s3key) && !empty($s3secret)) {
  if(!get_s3_buckets($s3key, $s3secret)) {
    //Log it
    loggit(2,"S3 credentials: [$s3key | $s3secret] were wrong for user: [$uid].");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Your S3 credentials didn't work.";
    echo json_encode($jsondata);
    exit(1);
  }
}
//--------------------------------------------------------

loggit(3, "Post-prefs: ".print_r($prefs, TRUE));


//Change the password
set_user_prefs($uid, $prefs);

//Build initial static files
build_social_outline($uid);
build_opml_reading_list($uid);

//Log it
loggit(1,"Setup page 1 for user: [$uid] was completed.");

//That worked, so set this user as being at stage 2
set_activation_stage($uid, 2);

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Prefs updated successfully.";
$jsondata['prefname'] = "";
echo json_encode($jsondata);

return(0);

?>
