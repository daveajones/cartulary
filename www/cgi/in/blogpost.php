<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_cgi_init_with_followup.php" ?>
<?
// Get the input
$jsondata = array();
$jsondata['fieldname'] = "";

//Is S3 available?
$s3info = get_s3_info($uid);

//Get the content of the post
loggit(3, "Blog post form: " . print_r($_REQUEST, TRUE));
loggit(3, "Blog post files: " . print_r($_FILES, TRUE));
$jsondata['fieldname'] = "content";
if (isset($_REQUEST['content'])) {
    $content = $_REQUEST['content'];
} else {
    //Log it
    loggit(2, "There was no content. Can't post this blog.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Can't post blank content.";
    echo json_encode($jsondata);
    exit(1);
}
//Make sure content is within limits
if (strlen($content) > 64000) {
    //Log it
    loggit(2, "The post is too long: [$content]");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Max post length is 64000 characters.";
    echo json_encode($jsondata);
    exit(1);
}

//Is there a type defined?
$jsondata['fieldname'] = "type";
if (isset($_REQUEST['type'])) {
    $type = $_REQUEST['type'];
} else {
    $type = 0;
}

//See if there was an opml source given
if (isset($_REQUEST['opmlsource']) && !empty($_REQUEST['opmlsource'])) {
    $opmlsource = $_REQUEST['opmlsource'];
    $customXML = new SimpleXMLElement($opmlsource);
    $dom = dom_import_simplexml($customXML);
    $opmlsource = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
} else {
    $opmlsource = "";
}

//Get the title
$jsondata['fieldname'] = "title";
if (isset($_REQUEST['title'])) {
    $title = $_REQUEST['title'];
} else {
    $title = "";
}
//Make sure the title is within limits
if (strlen($title) > 250) {
    //Log it
    loggit(2, "The title is too long: [$title]");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Max title length is 254 characters.";
    echo json_encode($jsondata);
    exit(1);
}

//Get the short link
$jsondata['fieldname'] = "shortlink";
if (isset($_REQUEST['shortlink'])) {
    $shorturl = $_REQUEST['shortlink'];
} else {
    $shorturl = FALSE;
}

//Get the link
$jsondata['fieldname'] = "link";
if (isset($_REQUEST['link'])) {
    $url = $_REQUEST['link'];
} else {
    $url = null;
}
//Make sure the url is within limits
if (strlen($url) > 1024) {
    //Log it
    loggit(2, "The link is too long: [$url]");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Max link length is 1024 characters.";
    echo json_encode($jsondata);
    exit(1);
}

//Get the origin
$jsondata['fieldname'] = "origin";
if (isset($_REQUEST['origin'])) {
    $origin = $_REQUEST['origin'];
} else {
    $origin = FALSE;
}

//Were there any enclosures?
if (isset($_REQUEST['enclosure'])) {
    $enclosure = $_REQUEST['enclosure'];
} else {
    $enclosure = FALSE;
}
$enclosures = array();
if (!empty($enclosure)) {
    foreach ($enclosure as $encfile) {
        $encarray = json_decode($encfile, TRUE);
        //loggit(3, "DEBUG: ".print_r($encarray, TRUE));
        $enclosures[] = array(
            'url' => (string)$encarray['url'],
            'length' => (string)$encarray['length'],
            'type' => (string)$encarray['type']
        );
    }
}
if (!empty($_REQUEST['extenclosure'])) {
    $extenclosures = $_REQUEST['extenclosure'];
    if (!isset($enclosures)) {
        $enclosures = array();
    }
    foreach ($extenclosures as $extenclosure) {
        $enclosures[] = array(
            'url' => $extenclosure['url'],
            'length' => $extenclosure['length'],
            'type' => $extenclosure['type']
        );
    }
}
$source = FALSE;
if (isset($_REQUEST['source'])) {
    $source = $_REQUEST['source'];
    if (empty($source['url']) && empty($source['title'])) {
        $source = array();
    }
}

//Was this a mobile post with file attachments?
if (isset($_FILES['file_mobile']) && $_FILES['file_mobile']['size'] > 0 && ($s3info != FALSE)) {
    $files = $_FILES['file_mobile'];
    if (!isset($enclosures)) {
        $enclosures = array();
    }
    loggit(3, "Enclosures incoming: " . print_r($enclosures, TRUE));
    $encfile = date('YmdHis') . "_" . strtolower($files['name']);
    putFileInS3($files['tmp_name'], $encfile, $s3info['bucket'] . "/enc", $s3info['key'], $s3info['secret']);
    $enclosures[] = array(
        'url' => get_s3_url($uid, '/enc/', $encfile),
        'length' => $files['size'],
        'type' => $files['type']
    );
    loggit(3, "Enclosures outgoing: " . print_r($enclosures, TRUE));
}

//If there is no link, but there is an enclosure let's use the enclosure url as the link
if (empty($url) && (count($enclosures) > 0)) {
    $url = $enclosures[0]['url'];
}

//If there is no link then give a link back to the html archive
if (empty($url)) { //&& isset($_REQUEST['tweet']) ) {
    $url = get_s3_url($uid, '/arc' . date('/Y/m/d/'), get_microblog_html_filename($uid) . '?ts=' . time());
    $archiveurl = TRUE;
}

//Do we need a short url?
if (!empty($url) && $shorturl == FALSE) {
    $shorturl = get_short_url($uid, $url);
}

//Put this post in the database
$didtweet = FALSE;
if (isset($_REQUEST['tweet'])) {
    $didtweet = TRUE;
}
loggit(1, "Adding post: [$content | $title | $url] for user: [$uid].");
$pid = add_post($uid, $content, $url, $shorturl, serialize($enclosures), $source, $didtweet, $title, NULL, $origin, $type, $opmlsource);

//Does the user want his posts tweeted?
if (isset($_REQUEST['tweet']) && twitter_is_enabled($uid)) {
    $twlink = "";
    $twtext = "";

    //What about enclosure pictures
    $mid = "";
    if (count($enclosures) > 0 && stripos($enclosures[0]['type'], 'image/') !== FALSE && (0 + $enclosures[0]['length']) < 3000000) {
        $img = fetchUrl($enclosures[0]['url']);
        $b64 = base64_encode($img);
        $mid = tweet_upload_picture($uid, $b64);
    }

    //Set the text of the tweet correctly
    if (!empty($title)) {
        $twtext = $title;
    } else {
        $twtext = strip_tags($content);
    }

    //Get the appropriate url
    if (!empty($url)) {
        $twlink = $url;
    }
    if (!empty($shorturl)) {
        $twlink = $shorturl;
    }
    if (strlen($title . $content) < 138 && isset($archiveurl)) {
        $twlink = "";
    }

    //Post it to twitter
    $twresult = tweet($uid, $twtext, $twlink, $mid);

    //Log it
    if ($twresult == TRUE) {
        loggit(1, "Blog post: [$pid] was sent to twitter for user: [$uid].");
    } else {
        loggit(2, "Blog post: [$pid] failed when posting to Twitter for user: [$uid]. See log for details.");
    }
}

//Rebuild static files
//$aposts = get_blog_posts($uid);
build_blog_rss_feed($uid, NULL, FALSE);
if(!empty($opmlsource)) {
    build_blog_rss_feed($uid, NULL, FALSE, NULL, FALSE, TRUE);
}
build_blog_opml_feed($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, FALSE);
build_blog_html_archive($uid, NULL, TRUE);
build_blog_script_widget($uid, 20, FALSE);
loggit(1, "User: [$uid]'s static files were rebuilt.");

//Log it
loggit(1, "User: [$uid] posted a new microblog: [$pid].");
$jsondata['pid'] = $pid;
$jsondata['post'] = array(
    'id' => $pid,
    'body' => $content,
    'link' => $url,
    'title' => $title,
    'avatarUrl' => $g_prefs['avatarurl'],
    'pubDate' => date("D, d M Y H:i:s O")
);
$jsondata['status'] = "true";
$jsondata['description'] = "Blog was posted.";

//Give feedback that all went well
$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
header("Cache-control: no-cache, must-revalidate");
if (!$xhr) {
    header("Content-Type: text/html");
} else {
    header("Content-Type: application/json");
}

if (!$xhr) {
    $resp = '<textarea>' . json_encode(utf8ize($jsondata)) . '</textarea>';
} else {
    $resp = json_encode(utf8ize($jsondata));
}
echo $resp;

loggit(3, "Blogpost cgi returning: [$resp]");
return (0);