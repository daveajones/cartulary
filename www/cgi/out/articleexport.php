<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Check that s3 is enabled
if( !s3_is_enabled($uid) ) {
  //Log it
  loggit(2,"User didn't have s3 enabled for article export: [$uid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Configure s3 in the prefs to enable exporting.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the article list
if ( isset($_REQUEST['articles']) ) {
  $articles = $_REQUEST['articles'];
} else {
  //Log it
  loggit(2,"No articles were selected for export.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No articles selected.";
  echo json_encode($jsondata);
  exit(1);
};

//How many articles do they want?
$acount = count($articles);

//Generate an opml file containing the articles selected
$arlist = array();
foreach( $articles as $article ) {
  $arlist[] = get_article($article, $uid);
}
$opml = build_opml_feed($uid, 9999, FALSE, $arlist, TRUE);

//Get the microblog feed file name
$filename = time()."-".$default_opml_export_file_name;

//Put the file in S3
$s3res = putInS3($opml, $filename, $prefs['s3bucket']."/exp", $prefs['s3key'], $prefs['s3secret'], "text/xml");
if(!$s3res) {
  loggit(2, "Could not create S3 file: [$filename] for user: [$uid].");
  loggit(3, "Could not create S3 file: [$filename] for user: [$uid].");
  //Log it
  $jsondata['status'] = "false";
  $jsondata['description'] = "Error writing to S3.";
  echo json_encode($jsondata);
  exit(1);
} else {
  $s3url = get_s3_url($uid, "/exp/", $filename);
  loggit(1, "Wrote feed to S3 at url: [$s3url].");
}


//Log it
loggit(3,"Exported: [$acount] articles to opml for user: [$uid]. ");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['url'] = $s3url;
$jsondata['description'] = "Articles exported.";
echo json_encode($jsondata);

return(0);

?>
