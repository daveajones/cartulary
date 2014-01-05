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

//Get the title
$title = "";
if ( isset($_REQUEST['title']) ) {
    $title = $_REQUEST['title'];
}

//Get the redirect source
$rhost = "";
if ( isset($_REQUEST['redirect']) && !empty($_REQUEST['redirect']) ) {
    $rhost = $_REQUEST['redirect'];
}

//Get disqus bool
$disqus = FALSE;
if ( isset($_REQUEST['disqus']) && $_REQUEST['disqus'] == "true" ) {
    $disqus = TRUE;
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


//Put the opml file in S3
$s3info = get_s3_info($uid);
$s3res = putInS3($opml, $filename, $s3info['bucket']."/opml", $s3info['key'], $s3info['secret'], "text/xml");
if(!$s3res) {
  loggit(2, "Could not create S3 file: [$filename] for user: [$uid].");
  loggit(3, "Could not create S3 file: [$filename] for user: [$uid].");
  //Log it
  $jsondata['status'] = "false";
  $jsondata['description'] = "Error writing to S3.";
  echo json_encode($jsondata);
  exit(1);
} else {
  $s3url = get_s3_url($uid, "/opml/", $filename);
  loggit(1, "Wrote opml to S3 at url: [$s3url].");
}

//Put the html file in S3
$htmldata = process_opml_to_html($opml, $title, $uid, $disqus, $s3url);
$htmlfilename = str_replace('.opml', '.html', $filename);
$s3res = putInS3($htmldata, $htmlfilename, $s3info['bucket']."/html", $s3info['key'], $s3info['secret'], "text/html");
if(!$s3res) {
    loggit(2, "Could not create S3 file: [$htmlfilename] for user: [$uid].");
    loggit(3, "Could not create S3 file: [$htmlfilename] for user: [$uid].");
    //Log it
    $jsondata['status'] = "false";
    $jsondata['description'] = "Error writing HTML to S3.";
    echo json_encode($jsondata);
    exit(1);
} else {
    $s3html = get_s3_url($uid, "/html/", $htmlfilename);
    loggit(1, "Wrote html to S3 at url: [$s3html].");
}

//Update recent file table
update_recent_file($uid, $s3url, $title);

//Update the redirector table
if( !empty($rhost) ) {
    //Update the redirection table
    update_redirection_host_name_by_url($s3html, $rhost, $uid);

    //Create the index stub that will redirect via a meta-refresh
    $rfile = create_short_url_file($s3html, $uid);

    //Parse out the url to find the bucket and key names
    $purl = parse_url($rhost);

    //Now put the index stub into s3
    $s3res = putInS3($rfile, "index.html", $purl['host'].rtrim($purl['path'], '/'), $s3info['key'], $s3info['secret'], "text/html");
    if(!$s3res) {
        loggit(2, "Could not create S3 file: [index.html] for user: [$uid].");
        loggit(3, "Could not create S3 file: [index.html] for user: [$uid].");
        //Log it
        $jsondata['status'] = "false";
        $jsondata['description'] = "Error writing redirection stub to S3.";
        echo json_encode($jsondata);
        exit(1);
    } else {
        $redhtml = $purl['host'].rtrim($purl['path'], '/')."/index.html";
        loggit(3, "DEBUG: Wrote html to S3 at url: [$redhtml].");
    }
}

//Log it
loggit(3,"Saved: [$filename] to S3 for user: [$uid]. ");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['url'] = $s3url;
$jsondata['html'] = $s3html;
$jsondata['description'] = "File saved to S3.";
echo json_encode($jsondata);

return(0);