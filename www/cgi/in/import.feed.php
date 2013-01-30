<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------


//Which user to import for?
$uid = $g_uid;

//Where's the feed?
if ( isset($_REQUEST['url']) && !empty($_REQUEST['url']) ) {
    //We append a nop argument to the end of the url so that we trick the
    //system into thinking it's a new feed in case someone already
    //subscribes to it
    $url = $_REQUEST['url']."?cartulary=import";
} else {
  //Log it
  loggit(2, "The given url: [$url] for importing seems invalid.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Invalid or malformed feed url.";
  echo json_encode($jsondata);
  return(0);
}


//First, bring in the feed, but don't link it to a user. We
//will remove it later and this ensures it won't show up in anyone's river.
$fid = add_feed($url, NULL, FALSE);

//Now get as many items as possible
if( get_feed_items($fid, 9999) < 0 ) {
  //Log it
  loggit(2, "There was an error parsing the feed. Make sure it's valid.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Invalid or malformed feed data.";
  echo json_encode($jsondata);
  return(0);
}

//Now collect all of the items as an array
$items = get_items_by_feed_id($fid, 9999);

//Loop through and add each one as a post to the microblog of this user
foreach( $items as $it ) {
  add_post($uid, $it['description'], $it['url'], FALSE, $it['enclosure'], array('url' => $it['sourceurl'], 'title' => $it['sourcetitle']), FALSE, $it['title'], $it['timestamp']);
}

//Now remove the feed
delete_feed($fid);

//Now rebuild the static files
build_blog_rss_feed($uid, NULL, FALSE);
build_blog_opml_feed($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, TRUE);
build_blog_script_widget($uid, 20, FALSE);
loggit(1, "User: [$uid]'s static files were rebuilt.");


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = count($items)." items imported.";
echo json_encode($jsondata);

return(0);

?>

