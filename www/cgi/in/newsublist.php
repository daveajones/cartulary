<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
$jsondata['fieldname'] = "";

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
