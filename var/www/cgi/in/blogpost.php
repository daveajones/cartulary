<?
//[!------------SECURITY-------------------------------!]

// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/posts.php";

// Get the input
//if ( isset($_POST['newpref']) ) { $newpref = $_POST['newpref']; } else { $newpref = ""; };
$jsondata = array();
$jsondata['fieldname'] = "";

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to delete an article without being logged in first.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Access denied.";
  echo json_encode($jsondata);
  exit(0);
}
$uid = get_user_id_from_sid(is_logged_in());
$prefs = get_user_prefs($uid);
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

//Get the right timezone
date_default_timezone_set('America/Chicago');

//Is S3 available?
$s3info = get_s3_info($uid);

//Get the content of the post
loggit(3, "Blog post form: ".print_r($_REQUEST, TRUE));
loggit(3, "Blog post files: ".print_r($_FILES, TRUE));
$jsondata['fieldname'] = "content";
if ( isset($_REQUEST['content']) ) {
  $content = $_REQUEST['content'];
} else {
  //Log it
  loggit(2,"There was no content. Can't post this blog.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Can't post blank content.";
  echo json_encode($jsondata);
  exit(1);
}
//Make sure content is within limits
if( strlen($content) > 64000 ) {
  //Log it
  loggit(2,"The post is too long: [$content]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max post length is 64000 characters.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the title
$jsondata['fieldname'] = "title";
if ( isset($_REQUEST['title']) ) {
  $title = $_REQUEST['title'];
} else {
  $title = "";
}
//Make sure the title is within limits
if( strlen($title) > 250 ) {
  //Log it
  loggit(2,"The title is too long: [$title]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max title length is 254 characters.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the link
$jsondata['fieldname'] = "link";
if ( isset($_REQUEST['link']) ) {
  $url = $_REQUEST['link'];
} else {
  $url = null;
}
//Make sure the url is within limits
if( strlen($url) > 1024 ) {
  //Log it
  loggit(2,"The link is too long: [$url]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Max link length is 1024 characters.";
  echo json_encode($jsondata);
  exit(1);
}


//Were there any enclosures?
if( isset($_REQUEST['enclosure']) ) {
  $enclosure = $_REQUEST['enclosure'];
} else {
  $enclosure = FALSE;
}
$enclosures = array();
if( !empty($enclosure) && ($s3info != FALSE) ) {
  loggit(3, "Enclosures incoming: ".print_r($enclosure, TRUE));
  foreach($enclosure as $encfile) {
    putFileInS3("$confroot/$spooldir/$uid"."_".$encfile, $encfile, $s3info['bucket']."/enc", $s3info['key'], $s3info['secret']);
    $enclosures[] = array( 'url' => get_s3_url($uid, '/enc/', $encfile),
			   'length' => filesize("$confroot/$spooldir/$uid"."_".$encfile),
			   'type' => mime_content_type("$confroot/$spooldir/$uid"."_".$encfile)
    );
    unlink("$confroot/$spooldir/$uid"."_".$encfile);
  }
  loggit(3, "Enclosures outgoing: ".print_r($enclosures, TRUE));
}
if( !empty($_REQUEST['extenclosure']) ) {
  $extenclosures = $_REQUEST['extenclosure'];
  if(!isset($enclosures)) {
    $enclosures = array();
  }
  foreach($extenclosures as $extenclosure) {
  	$enclosures[] = array(   'url' =>    $extenclosure['url'],
				 'length' => $extenclosure['length'],
			 	 'type' =>   $extenclosure['type']
	);
  }
}
$source = FALSE;
if( isset($_REQUEST['source']) ) {
  $source = $_REQUEST['source'];
  if( empty($source['url']) && empty($source['title']) ) {
    $source = array();
  }
}

//Was this a mobile post with file attachments?
if( isset($_FILES['file_mobile']) && $_FILES['file_mobile']['size'] > 0 && ($s3info != FALSE) ) {
    $files = $_FILES['file_mobile'];
    if(!isset($enclosures)) {
      $enclosures = array();
    }
    loggit(3, "Enclosures incoming: ".print_r($enclosures, TRUE));
    $encfile = date('YmdHis')."_".strtolower($files['name']);
    putFileInS3($files['tmp_name'], $encfile, $s3info['bucket']."/enc", $s3info['key'], $s3info['secret']);
    $enclosures[] = array( 'url' => get_s3_url($uid, '/enc/', $encfile),
			   'length' => $files['size'],
			   'type' => $files['type']
    );
    loggit(3, "Enclosures outgoing: ".print_r($enclosures, TRUE));
}

//If there is no link, but there is an enclosure let's use the enclosure url as the link
if( empty($url) && (count($enclosures) > 0) ) {
  $url = $enclosures[0]['url'];
}

//If there is no link then give a link back to the html archive
if( empty($url) && isset($_REQUEST['tweet']) ) {
  $url = get_s3_url($uid, '/arc'.date('/Y/m/d/'), get_microblog_archive_filename($uid).'?ts='.time());
  $archiveurl = TRUE;
}

//Do we need a short url?
if( !empty($url) ) {
  $shorturl = get_short_url($uid, $url);
} else {
  $shorturl = FALSE;
}

//Put this post in the database
$didtweet = FALSE;
if( isset($_REQUEST['tweet']) ) { $didtweet = TRUE; }
loggit(1, "Adding post: [$content | $title | $url] for user: [$uid].");
$pid = add_post($uid, $content, $url, $shorturl, serialize($enclosures), $source, $didtweet, $title);

//Does the user want his posts tweeted?
if( isset($_REQUEST['tweet']) && twitter_is_enabled($uid) ) {
        $twlink = "";
        $twtext = "";

	//Set the text of the tweet correctly
        if( !empty($title) ) {
	  $twtext = $title;
	} else {
	  $twtext = $content;
	}

	//Get the appropriate url
        if( !empty($url) ) {
          $twlink = $url;
        }
        if( !empty($shorturl) ) {
	  $twlink = $shorturl;
        }
	if( (strlen($twtext)) < 138 && isset($archiveurl) ) {
	  $twlink = "";
	}

        //Post it to twitter
        $twresult = tweet($uid, $twtext, $twlink);

	//Log it
        if($twresult == TRUE) {
                loggit(1, "Blog post: [$pid] was sent to twitter for user: [$uid].");
        } else {
                loggit(2, "Blog post: [$pid] failed when posting to Twitter for user: [$uid]. See log for details.");
        }
}

//Rebuild static files
//$aposts = get_blog_posts($uid);
build_blog_rss_feed($uid, NULL, FALSE);
build_blog_opml_feed($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, TRUE);
build_blog_script_widget($uid, 20, FALSE);
loggit(1, "User: [$uid]'s static files were rebuilt.");

//Log it
loggit(1,"User: [$uid] posted a new blog: [$pid].");
$jsondata['pid'] = $pid;

$jsondata['status'] = "true";
$jsondata['description'] = "Blog was posted.";

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

loggit(3, "Blogpost cgi returning: [$resp]");
return(0);

?>
