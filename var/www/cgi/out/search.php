<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/feeds.php";
include "$confroot/$includes/posts.php";
include "$confroot/$includes/articles.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted a search without being logged in first.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Not logged in.";
  echo json_encode($jsondata);
  exit(0);
}
$uid = get_user_id_from_sid(is_logged_in());
if(empty($uid) || ($uid == FALSE)) {
  //Log it
  loggit(2,"Couldn't retrieve a user id for this session: [$sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Not logged in.";
  echo json_encode($jsondata);
  exit(1);
}

//See if the user has activated their account yet
if(!is_user_active($uid)) {
  //Log it
  loggit(2,"User tried to access a page without activating first: [$uid | $sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Not activated.";
  echo json_encode($jsondata);
  exit(1);
}

// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------

loggit(3, "DEBUG: ".print_r($_REQUEST, TRUE));

//This will hold the search results
$jsondata['data'] = "";

//First we process the incoming query for shenanigans
$query = $_REQUEST['q'];
if( ereg("^[A-z0-9+. -,@]*[']?[A-z0-9+. -,@]*$", $query) ) {
  $trimmed = trim($query);
} else {
  //Log it
  loggit(2,"User: [$uid | $sid] entered a suspicious search term.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "That search looks suspicious.";
  echo json_encode($jsondata);
  exit(1);
}
$query = $trimmed;
$jsondata['query'] = $query;

//What are we searching?
$section = $_REQUEST['s'];
if( $section == "Articles" ) {
  $results = search_articles($uid, $query, 100);
} elseif( $section == "River" ) {
  $results = search_feed_items($uid, $query, 100);
} elseif( $section == "Microblog" ) {
  $results = search_posts($uid, $query, 100);
} elseif( $section == "Subscribe" ) {
  $results = search_feeds($uid, $query, 100);
} else {
  //Log it
  loggit(2, "Given section:[$section] not searchable.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "This type of search not supported yet.";
  echo json_encode($jsondata);
  exit(1);
}


//Did we get any results?
if( $results != FALSE ) {
  $jsondata['data'] = $results;
}


// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------

$jsondata['status'] = "true";

//Give feedback that all went well
$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
header("Cache-control: no-cache, must-revalidate");
if(!$xhr) {
  header("Content-Type: text/html");
} else {
  header("Content-Type: application/json");
}

if(!$xhr) {
  $resp = '<textarea>'.json_encode($jsondata).'</textarea>';
} else {
  $resp = json_encode($jsondata);
}
echo $resp;
//Give feedback that all went well

return(0);

?>
