<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/posts.php";

// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to delete a post without being logged in first.");
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

//Get the post id to unlink from this user
if ( isset($_REQUEST['id']) ) {
  $id = $_REQUEST['id'];
} else {
  //Log it
  loggit(2,"There was no post id in the request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No post id was specified.";
  echo json_encode($jsondata);
  exit(1);
};

//Make sure this post belongs to this user
if( !user_owns_post($uid, $id) ) {
  //Log it
  loggit(2, "User: [$uid] tried to remove a post: [$id] they don't own.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Post id not valid.";
  echo json_encode($jsondata);
  return(0);
}

//Unlink the article
if( !delete_post($id) ) {
  //Log it
  loggit(2, "Failed to remove: post: [$id] from user: [$uid]'s microblog.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Failed to delete post.";
  echo json_encode($jsondata);
  return(0);
}

//Rebuild the static feed
build_blog_rss_feed($uid, NULL, FALSE);
build_blog_opml_feed($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, TRUE);
build_blog_script_widget($uid, 20, FALSE);
loggit(1, "User: [$uid]'s static files were rebuilt.");

//Log it
loggit(1,"User: [$uid] removed post: [$id] from his microblog.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Post removed.";
echo json_encode($jsondata);

return(0);

?>
