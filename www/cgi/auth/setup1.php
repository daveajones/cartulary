<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/posts.php";
include "$confroot/$includes/articles.php";
include "$confroot/$includes/opml.php";
include "$confroot/$includes/feeds.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Get the input
//if ( isset($_POST['newpref']) ) { $newpref = $_POST['newpref']; } else { $newpref = ""; };
if ( isset($_POST['publicdefault']) ) { $publicdefault = 1; } else { $publicdefault = 0; };
if ( isset($_POST['publicrss']) ) { $publicrss = 1; } else { $publicrss = 0; };
if ( isset($_POST['publicopml']) ) { $publicopml = 1; } else { $publicopml = 0; };
if ( isset($_POST['sourceurlrt']) ) { $sourceurlrt = 1; } else { $sourceurlrt = 0; };
if ( isset($_POST['sourceurlrss']) ) { $sourceurlrss = 1; } else { $sourceurlrss = 0; };
if ( isset($_POST['linkblog']) ) { $linkblog = $_POST['linkblog']; } else { $linkblog = ""; };
if ( isset($_POST['stylesheet']) ) { $stylesheet = $_POST['stylesheet']; } else { $stylesheet = ""; };
if ( isset($_POST['maxlist']) ) { $maxlist = $_POST['maxlist']; } else { $maxlist = $default_max_list; };
if ( isset($_POST['s3bucket']) ) { $s3bucket = $_POST['s3bucket']; } else { $s3bucket = ""; };
if ( isset($_POST['s3key']) ) { $s3key = $_POST['s3key']; } else { $s3key = ""; };
if ( isset($_POST['s3secret']) ) { $s3secret = $_POST['s3secret']; } else { $s3secret = ""; };
if ( isset($_POST['s3cname']) ) { $s3cname = $_POST['s3cname']; } else { $s3cname = ""; };
if ( isset($_POST['twitterkey']) ) { $twitterkey = $_POST['twitterkey']; } else { $twitterkey = ""; };
if ( isset($_POST['twittersecret']) ) { $twittersecret = $_POST['twittersecret']; } else { $twittersecret = ""; };
if ( isset($_POST['twittertoken']) ) { $twittertoken = $_POST['twittertoken']; } else { $twittertoken = ""; };
if ( isset($_POST['twittertokensecret']) ) { $twittertokensecret = $_POST['twittertokensecret']; } else { $twittertokensecret = ""; };
if ( isset($_POST['urlshortener']) ) { $urlshortener = $_POST['urlshortener']; } else { $urlshortener = ""; };
if ( isset($_POST['avatarurl']) ) { $avatarurl = $_POST['avatarurl']; } else { $avatarurl = ""; };
if ( isset($_POST['riverheadlinecart']) ) { $riverheadlinecart = 1; } else { $riverheadlinecart = 0; };
if ( isset($_POST['homepagelink']) ) { $homepagelink = $_POST['homepagelink']; } else { $homepagelink = ""; };
if ( isset($_POST['s3shortbucket']) ) { $s3shortbucket = $_POST['s3shortbucket']; } else { $s3shortbucket = ""; };
if ( isset($_POST['lastshortcode']) ) { $lastshortcode = $_POST['lastshortcode']; } else { $lastshortcode = ""; };
if ( isset($_POST['shortcart']) ) { $shortcart = 1; } else { $shortcart = 0; };
if ( isset($_POST['riverhours']) ) { $riverhours = $_POST['riverhours']; } else { $riverhours = $default_river_hours; };
if ( isset($_POST['tweetcart']) ) { $tweetcart = 1; } else { $tweetcart = 0; };
if ( isset($_POST['microblogtitle']) ) { $microblogtitle = $_POST['microblogtitle']; } else { $microblogtitle = ""; };
if ( isset($_POST['cartularytitle']) ) { $cartularytitle = $_POST['cartularytitle']; } else { $cartularytitle = ""; };
if ( isset($_POST['mbfilename']) ) { $mbfilename = $_POST['mbfilename']; } else { $mbfilename = ""; };
if ( isset($_POST['cartfilename']) ) { $cartfilename = $_POST['cartfilename']; } else { $cartfilename = ""; };
if ( isset($_POST['mobilehidebigpics']) ) { $mobilehidebigpics = 1; } else { $mobilehidebigpics = 0; };
if ( isset($_POST['mbarchivecss']) ) { $mbarchivecss = $_POST['mbarchivecss']; } else { $mbarchivecss = ""; };
$prefs = array();
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
$jsondata['prefname'] = "publicdefault";
if( ($publicdefault < 0) || ($publicdefault > 1) ) {
  //Log it
  loggit(2,"The value for publicdefault pref was not within acceptable range: [$publicdefault]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['publicdefault'] = $publicdefault;

$jsondata['prefname'] = "publicrss";
if( ($publicrss < 0) || ($publicrss > 1) ) {
  //Log it
  loggit(2,"The value for publicrss pref was not within acceptable range: [$publicrss]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['publicrss'] = $publicrss;

$jsondata['prefname'] = "publicopml";
if( ($publicopml < 0) || ($publicopml > 1) ) {
  //Log it
  loggit(2,"The value for publicopml pref was not within acceptable range: [$publicopml]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['publicopml'] = $publicopml;

$jsondata['prefname'] = "sourceurlrt";
if( ($sourceurlrt < 0) || ($sourceurlrt > 1) ) {
  //Log it
  loggit(2,"The value for sourceurlrt pref was not within acceptable range: [$sourceurlrt]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['sourceurlrt'] = $sourceurlrt;

$jsondata['prefname'] = "sourceurlrss";
if( ($sourceurlrss < 0) || ($sourceurlrss > 1) ) {
  //Log it
  loggit(2,"The value for sourceurlrss pref was not within acceptable range: [$publicdefault]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['sourceurlrss'] = $sourceurlrss;

$jsondata['prefname'] = "linkblog";
if( strlen($linkblog) > 254 ) {
  //Log it
  loggit(2,"The value for linkblog pref was not within acceptable range: [$linkblog]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Pref string is too long.";
  echo json_encode($jsondata);
  exit(1);
}
if( !empty($linkblog) && !preg_match("/http.?\:\/\/.*/", $linkblog) ) {
  //Log it
  loggit(2,"The value for linkblog pref doesn't seem valid: [$linkblog]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Linkblog url doesn't seem valid.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['linkblog'] = $linkblog;

$jsondata['prefname'] = "stylesheet";
if( strlen($stylesheet) > 254 ) {
  //Log it
  loggit(2,"The value for stylesheet pref was not within acceptable range: [$stylesheet]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Pref string is too long";
  echo json_encode($jsondata);
  exit(1);
}
if( !empty($stylesheet) && !preg_match("/.*\/.*/", $stylesheet) ) {
  //Log it
  loggit(2,"The value for stylesheet pref doesn't seem valid: [$stylesheet]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Stylesheet url doesn't seem valid.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['stylesheet'] = $stylesheet;

$jsondata['prefname'] = "maxlist";
if( strlen($maxlist) > 3 ) {
  //Log it
  loggit(2,"The value for maxlist pref was not within acceptable range: [$maxlist]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Maxlist pref limit is 999.";
  echo json_encode($jsondata);
  exit(1);
}
if( empty($maxlist) || !is_numeric($maxlist) ) {
  //Log it
  loggit(2,"The value for maxlist pref doesn't seem valid: [$maxlist]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Maxlist pref doesn't seem valid: [$maxlist].";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['maxlist'] = $maxlist;

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

$jsondata['prefname'] = "twitterkey";
if( strlen($twitterkey) > 254 ) {
  //Log it
  loggit(2,"The value for twitterkey pref was too long: [$twitterkey]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max key length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['twitterkey'] = $twitterkey;

$jsondata['prefname'] = "twittersecret";
if( strlen($twittersecret) > 254 ) {
  //Log it
  loggit(2,"The value for twittersecret pref was too long: [$twittersecret]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max secret length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['twittersecret'] = $twittersecret;

$jsondata['prefname'] = "twittertoken";
if( strlen($twittertoken) > 254 ) {
  //Log it
  loggit(2,"The value for twittertoken pref was too long: [$twittertoken]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max token length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['twittertoken'] = $twittertoken;

$jsondata['prefname'] = "twittertokensecret";
if( strlen($twittertokensecret) > 254 ) {
  //Log it
  loggit(2,"The value for twittertokensecret pref was too long: [$twittertokensecret]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max secret length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['twittertokensecret'] = $twittertokensecret;

$jsondata['prefname'] = "urlshortener";
if( strlen($urlshortener) > 254 ) {
  //Log it
  loggit(2,"The value for urlshortener pref was too long: [$urlshortener]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max url shortener call length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['urlshortener'] = $urlshortener;

$jsondata['prefname'] = "avatarurl";
if( strlen($avatarurl) > 1023 ) {
  //Log it
  loggit(2,"The value for avatarurl pref was too long: [$avatarurl]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max avatar url length is 1023 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['avatarurl'] = $avatarurl;

$jsondata['prefname'] = "riverheadlinecart";
if( ($riverheadlinecart < 0) || ($riverheadlinecart > 1) ) {
  //Log it
  loggit(2,"The value for riverheadlinecart pref was not within acceptable range: [$riverheadlinecart]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['riverheadlinecart'] = $riverheadlinecart;

$jsondata['prefname'] = "homepagelink";
if( strlen($homepagelink) > 1023 ) {
  //Log it
  loggit(2,"The value for homepagelink pref was too long: [$homepagelink]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max homepage link length is 1023 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['homepagelink'] = $homepagelink;

$jsondata['prefname'] = "s3shortbucket";
if( strlen($s3shortbucket) > 255 ) {
  //Log it
  loggit(2,"The value for s3shortbucket pref was too long: [$s3shortbucket]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max s3shortbucket link length is 255 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['s3shortbucket'] = $s3shortbucket;

$jsondata['prefname'] = "lastshortcode";
if( strlen($lastshortcode) > 64 ) {
  //Log it
  loggit(2,"The value for the lastshortcode pref was too long: [$lastshortcode]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max lastshortcode length is 64 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['lastshortcode'] = $lastshortcode;

$jsondata['prefname'] = "shortcart";
if( ($shortcart < 0) || ($shortcart > 1) ) {
  //Log it
  loggit(2,"The value for shortcart pref was not within acceptable range: [$shortcart]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['shortcart'] = $shortcart;

$jsondata['prefname'] = "riverhours";
if( strlen($riverhours) > 3 ) {
  //Log it
  loggit(2,"The value for riverhours pref was not within acceptable range: [$riverhours]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Riverhours pref limit is 255.";
  echo json_encode($jsondata);
  exit(1);
}
if( empty($riverhours) || !is_numeric($riverhours) ) {
  //Log it
  loggit(2,"The value for riverhours pref doesn't seem valid: [$riverhours]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Riverhours pref doesn't seem valid: [$riverhours].";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['riverhours'] = $riverhours;

$jsondata['prefname'] = "tweetcart";
if( ($tweetcart < 0) || ($tweetcart > 1) ) {
  //Log it
  loggit(2,"The value for tweetcart pref was not within acceptable range: [$tweetcart]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['tweetcart'] = $tweetcart;

$jsondata['prefname'] = "microblogtitle";
if( strlen($microblogtitle) > 255 ) {
  //Log it
  loggit(2,"The value for microblogtitle pref was too long: [$microblogtitle]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max microblog title length is 255 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['microblogtitle'] = $microblogtitle;

$jsondata['prefname'] = "cartularytitle";
if( strlen($cartularytitle) > 255 ) {
  //Log it
  loggit(2,"The value for cartularytitle pref was too long: [$cartularytitle]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max article archive title length is 255 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['cartularytitle'] = $cartularytitle;

$jsondata['prefname'] = "mbfilename";
if( strlen($mbfilename) > 255 ) {
  //Log it
  loggit(2,"The value for mbfilename pref was too long: [$mbfilename]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max microblog file name length is 255 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['mbfilename'] = $mbfilename;

$jsondata['prefname'] = "cartfilename";
if( strlen($cartfilename) > 255 ) {
  //Log it
  loggit(2,"The value for cartfilename pref was too long: [$cartfilename]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max article archive file name length is 255 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['cartfilename'] = $cartfilename;

$jsondata['prefname'] = "mobilehidebigpics";
if( ($mobilehidebigpics < 0) || ($mobilehidebigpics > 1) ) {
  //Log it
  loggit(2,"The value for mobilehidebigpics pref was not within acceptable range: [$mobilehidebigpics]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Value of pref is out of range.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['mobilehidebigpics'] = $mobilehidebigpics;

$jsondata['prefname'] = "mbarchivecss";
if( strlen($mbarchivecss) > 1024 ) {
  //Log it
  loggit(2,"The value for mbarchivecss pref was too long: [$mbarchivecss]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max microblog archive css url length is 1024 characters.";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['mbarchivecss'] = $mbarchivecss;


/* // New pref setting template
$jsondata['prefname'] = "newpref";
if( strlen($newpref) > 3 ) {
  //Log it
  loggit(2,"The value for maxlist pref was not within acceptable range: [$newpref]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Pref out of range.";
  echo json_encode($jsondata);
  exit(1);
}
if( empty($newpref) || !is_numeric($newpref) ) {
  //Log it
  loggit(2,"The value for maxlist pref doesn't seem valid: [$newpref]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Pref doesn't seem valid: [$newpref].";
  echo json_encode($jsondata);
  exit(1);
}
$prefs['newpref'] = $newpref;
*/

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
