<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Check that s3 is enabled
if( !s3_is_enabled($uid) && !sys_s3_is_enabled() ) {
  //Log it
  loggit(2,"User didn't have s3 enabled for opml save: [$uid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Configure s3 in the prefs to enable saving.";
  echo json_encode($jsondata);
  exit(1);
}

//Make sure we have a filename to use
if ( isset($_REQUEST['filename']) ) {
    $filename = $_REQUEST['filename'];
} else {
    //Log it
    loggit(2,"No filename was set for this opml save.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No filename given.";
    echo json_encode($jsondata);
    exit(1);
};

//Get the opml data
if ( isset($_REQUEST['opml']) ) {
  $opml = $_REQUEST['opml'];
} else {
  //Log it
  loggit(2,"No opml data was set for this opml save.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No opml data given.";
  echo json_encode($jsondata);
  exit(1);
};


//Put the file in S3
$s3info = get_s3_info($uid);
$s3res = putInS3($opml, $filename, $s3info['bucket']."/exp", $s3info['key'], $s3info['secret'], "text/xml");
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
loggit(3,"Saved: [$filename] to S3 for user: [$uid]. ");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['url'] = $s3url;
$jsondata['description'] = "File saved to S3.";
echo json_encode($jsondata);

return(0);