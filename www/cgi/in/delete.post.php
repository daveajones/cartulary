<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();


// Vars
$postId = $_REQUEST['postId'];

// Make sure we have a post id to use
if(empty($postId)) {
  //Log it
  loggit(2,"The post id was missing.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The post id was missing.";
  echo json_encode($jsondata);
  exit(1);
}

//Make sure this post belongs to this user
if( !user_owns_post($g_uid, $postId) ) {
  //Log it
  loggit(2, "User: [$g_uid] tried to remove a post: [$postId] they don't own.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Post id not valid.";
  echo json_encode($jsondata);
  return(0);
}

//Unlink the article
if( !delete_post($postId) ) {
  //Log it
  loggit(2, "Failed to remove post: [$postId] from user: [$g_uid]'s microblog.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Failed to delete post.";
  echo json_encode($jsondata);
  return(0);
}

//Rebuild the static feed
build_blog_rss_feed($g_uid, NULL, FALSE);
build_blog_opml_feed($g_uid, NULL, FALSE);
build_blog_html_archive($g_uid, NULL, FALSE);
build_blog_html_archive($g_uid, NULL, TRUE);
build_blog_script_widget($g_uid, 20, FALSE);
loggit(1, "User: [$g_uid]'s static files were rebuilt.");

//Log it
loggit(1, "User: [$g_uid] removed post: [$postId] from their microblog.");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['postid'] = $postId;
$jsondata['status'] = "true";
$jsondata['description'] = "Post was deleted.";
echo json_encode($jsondata);

return(0);

?>
