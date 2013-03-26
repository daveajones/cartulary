<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

//First get a url for what we will render
if( isset($_REQUEST['url']) && !empty($_REQUEST['url']) ) {
  $url = $_REQUEST['url'];
} else {
  //Error back
  $jsondata['status'] = "false";
  $jsondata['description'] = "No valid url given.";
  echo json_encode($jsondata);
  return(0);
}

//Get the url
$content = fetchUrl(get_final_url($url));
if( $content == FALSE ) {
  //Error back
  $jsondata['status'] = "false";
  $jsondata['description'] = "Couldn't get the content.";
  echo json_encode($jsondata);
  return(0);
}

//Make sure it's a social outline
if( !is_social_outline($content) ) {
  //Error back
  $jsondata['status'] = "false";
  $jsondata['description'] = "This isn't a social outline.";
  echo json_encode($jsondata);
  return(0);
}

//Get the name of this person
$jsondata['data']['title'] = get_title_from_outline($content);
$jsondata['data']['ownername'] = get_ownername_from_outline($content);
$jsondata['data']['avatarurl'] = get_avatar_url_from_outline($content);
$jsondata['data']['url'] = get_canonical_url_from_outline($content);
$jsondata['data']['feeds']['pub'] = get_pub_feeds_from_outline($content);
$jsondata['data']['feeds']['sub'] = get_feeds_from_outline($content);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Parsed social outline content.";
echo json_encode($jsondata);

return(0);

?>

