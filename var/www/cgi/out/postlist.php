<?
// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/posts.php";

// Valid session?
if(!is_logged_in()) {
  loggit(2,"A non-authenticated user tried to see the post list.");
  exit(0);
}

// Json header
//header("Content-Type: text/plain");
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Vars
$uid = get_user_id_from_sid(get_session_id());
$jsondata = array();
$postlist = array();
$data = array();

// Get the stats to display as JSON
$posts = get_blog_posts($uid);
if($posts == FALSE) {
  $jsondata['status'] = "noposts";
  $jsondata['description'] = "List of posts.";
  $jsondata['posts'] = 0;
  echo json_encode($jsondata);
  loggit(1,"User: [$uid] has no posts.");
  exit(0);
}

// Dump the stats
$jsondata['status'] = "true";
$jsondata['description'] = "List of posts.";
$jsondata['posts'] = $posts;
echo json_encode($jsondata);
loggit(1,"Sending microblog post history for user: [$uid]");

?>
