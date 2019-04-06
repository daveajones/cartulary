<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_cgi_init_with_followup.php" ?>
<?

// Include path
set_include_path("$confroot/$libraries" . PATH_SEPARATOR . get_include_path());

include "/opt/cartulary/libraries/readability-php/src/Configuration.php";
include "/opt/cartulary/libraries/readability-php/src/ParseException.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/NodeTrait.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMAttr.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMCdataSection.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMCharacterData.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMComment.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMDocumentFragment.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMDocumentType.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMElement.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMEntity.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMEntityReference.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMNode.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMNotation.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMProcessingInstruction.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMText.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/NodeUtility.php";
include "/opt/cartulary/libraries/readability-php/src/Nodes/DOM/DOMDocument.php";
include "/opt/cartulary/libraries/readability-php/src/Readability.php";

use andreskrey\Readability\Readability;
use andreskrey\Readability\HTMLParser;
use andreskrey\Readability\Configuration;

//See if JSON or HTML format was requested
if (isset($_REQUEST['json'])) {
    // Json header
    header("Cache-control: no-cache, must-revalidate");
    header("Content-Type: application/json");
    $jsondata = array();
    $json = TRUE;
} else {
    $json = FALSE;
}

//Was a title specified in the request?  If so, set that as the title instead of the extracted one
$reqtitle = "";
if (isset($_REQUEST['title'])) {
    if (!empty($_REQUEST['title']) && stripos($_REQUEST['title'], "Subscribe to read") === FALSE ) {
        $title = $_REQUEST['title'];
        if (strpos($sourceurl, 'twitter.com') !== FALSE) {
            $title = '@' . $title;
        }
        $reqtitle = trim($title);
    }
}

//Globals
$html_only = true;
$ispdf = FALSE;
$linkonly = FALSE;
$querystring = $_SERVER['QUERY_STRING'];
$referer = "";

// Get a start time
$tstart = time();


// Sanitize and validate incoming URL string
if (!isset($_REQUEST['url'])) {
    die('No URL supplied');
}
$url = $_REQUEST['url'];
if (!preg_match('!^https?://.+!i', $url)) {
    $url = 'http://' . $url;
}
$url = filter_var($url, FILTER_SANITIZE_URL);
$test = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
// deal with bug http://bugs.php.net/51192 (present in PHP 5.2.13 and PHP 5.3.2)
if ($test === false) {
    $test = filter_var(strtr($url, '-', '_'), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
}
if ($test !== false && $test !== null && preg_match('!^https?://!', $url)) {
    // all okay
    unset($test);
} else {
    die('Invalid URL supplied');
}


//Resolve re-directs
//$newurl = get_final_url($url);
$newurl = $url;

//Remove feedburner garbage
$url = trim(rtrim(preg_replace("/&?utm_(.*?)\=[^&]+/", "", $newurl), '?'));


//See if the response returned was actually a meta-refresh forwarding document
//##: -------  PRE-PROCESS the URL here to make sure we dodge any weirdness like proxies or non-HTML content-types
//Feed proxy?
if (preg_match('/feedproxy\.google\.com/i', $url)) {
    $oldurl = $url;
    $url = get_final_url($oldurl);
    loggit(3, "Converting feedproxy url: [$oldurl] to [$url].");
}
if (preg_match('/wsj\.com\/articles/i', $url)) {
    $oldurl = $url;
    $url = str_replace("wsj.com/articles/", "wsj.com/amp/articles/", $url);
    loggit(3, "Converting wsj url: [$oldurl] to [$url].");
}
if (preg_match('/ft\.com\//i', $url)) {
    $referer = "https://www.google.com";
    loggit(3, "Setting referer to: [$referer].");
}
//##: ------- END PRE-PROCESS of URL -----------------------------------------------------------------------------
$referer = "https://www.google.com";
$response = fetchUrlExtra($url, 30, $referer);
//loggit(3, "DEBUG: ".print_r($response, TRUE));
$mret = preg_match('|http-equiv.*refresh.*content="\s*\d+\s*;\s*url=\'?(.*?)\'?\s*"|i', $response['body'], $mrmatches);
if (($mret > 0) && !empty($mrmatches[1])) {
    //loggit(3, "Found a meta refresh pointing to: [" . $mrmatches[1] . "].");
    $url = get_final_url($mrmatches[1]);
    $response = fetchUrlExtra($url);
}
$html = $response['body'];

//If html body content was passed in just use it
if( isset($_REQUEST['content']) && !empty($_REQUEST['content']) ) {
    $html = $_REQUEST['content'];
}

//Reddit
if (preg_match('/^https?\:\/\/(www\.)?reddit\.com/i', $url)) {
    loggit(3, "Getting a reddit link.");

    $luie = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    //Get the title
    $nodes = $doc->getElementsByTagName("title");
    $title = $nodes->item(0)->nodeValue;
    loggit(3, "Reddit title: $title");
    libxml_use_internal_errors($luie);

    if (preg_match("/\<p.*class=\"title.*\<a.*class=\"title.*href=\"(.*)\"/iU", $html, $matches)) {
        $url = get_final_url($matches[1]);
        loggit(3, "Reddit link: [" . $url . "]");
        $response = fetchUrlExtra($url);
        $html = $response['body'];
    } else {
        loggit(2, "Couldn't extract Reddit link.");
    }

//Memeorandum
} else if (preg_match('/memeorandum\.com/i', $url)) {
    loggit(3, "Converting memeorandum.com link to span ref.");
    //Get the code from the link
    $posLastSlash = strripos($url, '/');
    $posPoundA = stripos($url, '#a', $posLastSlash);
    $code = substr($url, $posPoundA + 2);

    if (preg_match("/\<span.*pml=\"$code\".*url=\"(.*)\".*head=\"(.*)\"/iU", $html, $matches)) {
        $url = get_final_url($matches[1]);
        $title = $matches[2];
        loggit(3, "Memeorandum link-through url: [" . $url . "]");
        $response = fetchUrlExtra($url);
        $html = $response['body'];
    } else {
        loggit(2, "Couldn't extract Memeorandum link.");
    }
}

//Is this a PDF?
if (substr($response['body'], 0, 4) == "%PDF") {
    $ispdf = TRUE;
    $pdfbody = $response['body'];
    loggit(3, "The url: [$url] is a PDF document.");
}

// ---------- BEGIN ARTICLE EXISTENCE CHECK ----------
//Is this URL already in the database?
loggit(3, "Received request for article at: [$url] with title: [$reqtitle].");
$aid = article_exists($url);
if ($aid) {
    loggit(3, "Article: [$url] already exists as: [$aid].");
    $art = get_article($aid, $uid);

    if (user_can_view_article($aid, $uid)) {
        loggit(3, "Article already linked to user: [$uid].");
        //Return the article as a json object if that was asked for
        if ($json) {
            //Give feedback that all went well
            $jsondata['status'] = "true";
            $jsondata['article'] = array(
                'id' => $aid,
                'title' => $art['title'],
                'body' => $art['content'],
                'url' => $url,
                'shorturl' => $art['shorturl'],
                'sourceurl' => $art['sourceurl'],
                'sourcetitle' => $art['sourcetitle']
            );
            echo json_encode($jsondata);
            return (0);
        } else {
            //Redirect to the article viewer to see it
            header("Location: $showarticlepage?aid=$aid");
            return (0);
        }
    } else {
        loggit(3, "Linking article: [$aid] to user: [$uid].");
        link_article_to_user($aid, $uid);
        $slimcontent = $art['content'];
        $linkonly = TRUE;
    }

} else {
    loggit(3, "Article: [$url] does not exist.");
}
// ---------- END ARTICLE EXISTENCE CHECK ----------


// ---------- BEGIN ARTICLE PROCESSING ----------
//We skip all the extraction stuff if the article was already in the database
if ($linkonly == FALSE) {

    //Get the page
    if ($response) {
        $effective_url = $response['effective_url'];
        loggit(3, "Article effective url is: [$effective_url].");

        $html = $response['body'];

        //loggit(3, "ARTICLE: [$html]");
        if (empty($html)) {
            loggit(3, "DEBUG: Blank content returned for html.");
            //loggit(3, "DEBUG: ".print_r($response, TRUE));
        }
        // remove strange things here
        $html = str_replace('</[>', '', $html);

        // Convert non-standard elements to divs

        //FC Editor conversion
        $html = preg_replace("/\<ul\ class=\"outline[^>]*\"\>\<li\ class=\"ou\ outline[^>]*\"\>(.*)\<\/li>\<\/ul\>/iU", "<p>$1</p>", $html);

        // Convert encoding
        $html = convert_to_utf8($html, $response['headers']);
    }

    //Was there an error?
    if (!$response || $response['status_code'] >= 400) {
        if ($json == TRUE) {
            //Give feedback that all was not well
            $jsondata['status'] = "false";
            $jsondata['article'] = array('id' => 'error',
                'title' => '',
                'body' => '<center><p>Could not retreive article. Server returned response code: [' . $response['status_code'] . ']. Click <a href="' . $url . '">here</a> to link out to the full source article.</p></center>',
                'url' => $url,
                'shorturl' => '',
                'sourceurl' => '',
                'sourcetitle' => ''
            );
            echo json_encode($jsondata);
            return (0);
        } else {
            die('(' . $response['status_code'] . ') Error retrieving ' . $url . ' [' . $effective_url . ']');
        }
    }

    //Is this a youtube link?
    if (preg_match('/youtube\.com/i', $url)) {
        loggit(3, "Cartulizing a Youtube video.");
        preg_match("/v[\/\=]([A-Za-z0-9\_\-]*)/i", $url, $matches) || die("Couldn't extract YouTube ID string.");
        $content = '<br/><iframe class="bodyvid" src="https://www.youtube.com/embed/' . $matches[1] . '" frameborder="0" allowfullscreen></iframe>';
        preg_match("/\<meta.*property\=\"og\:title\".*content\=\"(.*)\".*\>/i", $html, $matches) || die("Couldn't extract the YouTube video title.");
        $title = $matches[1];
        loggit(3, "Youtube video title: [$title].");
        $analysis = "";
        $slimcontent = $content;

    //Is this an image
    } else if (url_is_a_picture($url)) {
        loggit(3, "Getting an image.");
        loggit(3, "Image source: [" . $url . "]");
        $content = '<br/><img style="width:600px;" src="' . $url . '"></img>';
        $analysis = "";
        $slimcontent = $content;

    //Is this audio
    } else if (url_is_audio($url)) {
        loggit(3, "Getting an audio url.");
        loggit(3, "Audio source: [" . $url . "]");
        $mt = make_mime_type($url);
        $content = '<br/><audio style="width:400px" controls="true"><source src="' . $url . '" type="' . $mt . '"></audio>';
        $analysis = "";
        $slimcontent = $content;

    //Is this video
    } else if (url_is_video($url)) {
        loggit(3, "Getting a video url.");
        loggit(3, "Video source: [" . $url . "]");
        $mt = make_mime_type($url);
        $content = '<br/><video style="width:95%;margin:0 auto;display:block;" controls="true"><source src="' . $url . '" type="' . $mt . '"></video>';
        $analysis = "";
        $slimcontent = $content;

    //Is this an imgur link?
    } else if (preg_match('/imgur\.com/i', $url)) {
        loggit(3, "Getting an image file as a full article.");
        if (preg_match("/\<link.*rel=\"image_src.*href=\"(.*)\"/iU", $html, $matches)) {
            $url = $matches[1];
            loggit(3, "Imgur image source: [" . $url . "]");
            $content = '<br/><img class="bodyvid" src="' . $matches[1] . '"></img>';
        } else {
            loggit(2, "Couldn't extract Imgur image: [" . $matches[1] . "]");
        }
        $analysis = "";
        $slimcontent = $content;

    //Askwoody?
    } else if (preg_match('/^http.*askwoody\.com.*/i', $url)) {
        loggit(2, "DEBUG: ----------------------> Askwoody.com post.");

        $dom = new DomDocument();
        $dom->loadHTML($html);
        $classname = 'paddings';
        $finder = new DomXPath($dom);
        $nodes = $finder->query("(//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]//ul/li)[1]/*[self::p or self::blockquote or self::img or self::ul or self::ol or self::li or self::a]");
        $tmp_dom = new DOMDocument();
        foreach ($nodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $reqtitle, $effective_url);

        $analysis = "";
        $slimcontent = $content;

    //Bizjournals?
    } else if (preg_match('/^http.*bizjournals\.com.*/i', $url)) {
        loggit(2, "DEBUG: ----------------------> Bizjournals post.");

                loggit(3, print_r($_REQUEST, TRUE));

//        $dom = new DomDocument();
//        $dom->loadHTML($html);
//        $classname = 'paddings';
//        $finder = new DomXPath($dom);
//        $nodes = $finder->query("(//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]//ul/li)[1]/*[self::p or self::blockquote or self::img or self::ul or self::ol or self::li or self::a]");
//        $tmp_dom = new DOMDocument();
//        foreach ($nodes as $node) {
//            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
//        }
//        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $reqtitle, $effective_url);
//
//        $analysis = "";
//        $slimcontent = $content;

    //Is this a wordpress post?
//    } else if (preg_match('/\<div.*class.*entry-content.*\>/i', $html)) {
//        loggit(2, "DEBUG: ----------------------> Getting a wordpress post.");
//
//        $dom = new DomDocument();
//        $dom->loadHTML($html);
//        $classname = 'entry-content';
//        $finder = new DomXPath($dom);
//        $nodes = $finder->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
//        $tmp_dom = new DOMDocument();
//        foreach ($nodes as $node) {
//            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
//        }
//
//        //Get rid of all the wordpress sharing crap
//        $content = preg_replace('/\<div.*class.*sharedaddy.*</div>/i', '', $content);
//
//        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE);
//
//        $analysis = "";
//        $slimcontent = $content;

    //Is this a blogger post?
    } else if (preg_match('/^http.*blogspot\.com.*/i', $url)) {
        loggit(3, "DEBUG: ----------------------> Getting a blogger.com post.");

        $dom = new DomDocument();
        $dom->loadHTML($html);
        $classname = 'post-body';
        $finder = new DomXPath($dom);
        $nodes = $finder->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
        $tmp_dom = new DOMDocument();
        foreach ($nodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $reqtitle, $effective_url);

        $analysis = "";
        $slimcontent = $content;

    //Is this a PDF?
    } else if ($ispdf) {
        loggit(3, "Cartulizing a PDF.");
        $content = '';
        include "$confroot/$libraries/PDFParser/vendor/autoload.php";
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseContent($pdfbody);
        $details = $pdf->getDetails();
        loggit(3, print_r($details, TRUE));
        if( empty($title) && isset($details['title']) && !empty($details['title']) ) {
            $title = $details['title'];
        } else if (empty($title)) {
            $title = "Untitled PDF";
        }
        foreach ($pdf->getPages() as $page) {
            $content .= "<p>" . $page->getText() . "</p>";
        }
        //$content = $pdf->getText();
        //Do textual analysis and save it in the database
        $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));
        //Reduce all that whitespace
        $slimcontent = clean_article_content($content, 0, FALSE, FALSE, $reqtitle);

    //Normal web page
    } else {
        loggit(3, "Cartulizing article: [$url] with Readability.");

        //Debugging loggit(3, print_r($html, TRUE));

        //Set up an extraction
        $readability = new Readability(new Configuration());

        try {
            $readability->parse($html);
            $content = $readability->getContent();
            $title = $readability->getTitle();
            if(!empty($title)) {
                loggit(3, "Got article: [$title] with Readability.");
            }
        } catch (\andreskrey\Readability\ParseException $e) {
            loggit(3, "DEBUG: New cart process failed.");
            header("Location: /cgi/in/cartulize2?".$querystring);
            exit(0);
            //$content = sprintf('Error processing text: %s', $e->getMessage);
        }

        //Do textual analysis and save it in the database
        $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));

        //Reduce all that whitespace
        $content = clean_article_content($content, 0, FALSE, FALSE, $reqtitle, $effective_url);
        $slimcontent = $content;
    }

    //Calculate how long it took to cartulize this article
    $took = time() - $tstart;
    loggit(3, "Article: [$url] took: [$took] seconds to cartulize.");
}
// ---------- END ARTICLE PROCESSING ----------


// ---------- BEGIN URL SHORTENING ----------
$tstart = time();
//Shorten the URL?
if ($prefs['shortcart'] == 1) {
    $shorturl = get_short_url($uid, $url);
} else {
    $shorturl = FALSE;
}
//Calculate how long it took
$took = time() - $tstart;
loggit(3, "It took: [$took] seconds to shorten the url for article: [$aid].");
// ---------- END URL SHORTENING ----------


// ---------- BEGIN SOURCE ATTRIBUTION HANDLING ----------
//Check for a source url and title
$sourceurl = NULL;
$sourcetitle = NULL;
if (isset($_REQUEST['surl'])) {
    $sourceurl = $_REQUEST['surl'];
}
if (isset($_REQUEST['stitle'])) {
    $sourcetitle = $_REQUEST['stitle'];
}
// ---------- END SOURCE ATTRIBUTION HANDLING ----------


// ---------- BEGIN TITLE HANDLING ----------
if(!empty($reqtitle)) {
    $title = $reqtitle;
}
// ---------- END TITLE HANDLING ----------


//Put this article in the database
if ($linkonly == FALSE) {
    $tstart = time();
    $aid = add_article($url, $title, $slimcontent, $analysis, $uid, $shorturl, FALSE, $sourceurl, $sourcetitle);
    //Calculate how long it took
    $took = time() - $tstart;
    loggit(3, "It took: [$took] seconds to add article: [$aid] to the database.");
}


//Does the user want his posts tweeted?
if ($prefs['tweetcart'] == 1 && twitter_is_enabled($uid)) {
    $tstart = time();

    $twtext = "Reading... " . trim($title);
    $twlink = "";
    if (!empty($url)) {
        $twlink = $url;
    }
    if (!empty($shorturl)) {
        $twlink = $shorturl;
    }

    //Post it to twitter
    $twresult = tweet($uid, $twtext, $twlink);
    if ($twresult == TRUE) {
        loggit(1, "Article: [$aid] was sent to twitter for user: [$uid].");
    } else {
        loggit(2, "Article: [$aid] failed when posting to Twitter for user: [$uid]. See log for details.");
    }

    //Calculate how long it took
    $took = time() - $tstart;
    loggit(3, "It took: [$took] seconds to tweet article: [$aid].");
}

//Rebuild static files
$tstart = time();

//Store article in S3?
$staticurl = "";
if ($prefs['staticarticles'] == 1) {
    $s3info = get_s3_info($g_uid);
    if ($s3info != FALSE) {
        $targetS3File = time() . "_" . random_gen(8) . ".html";
        putInS3(make_article_printable($aid, $uid), $targetS3File, $s3info['bucket'] . "/art", $s3info['key'], $s3info['secret'], "text/html");
        $staticurl = get_s3_url($uid, '/art/', $targetS3File);
        loggit(3, "Stored article in S3 at location: [$staticurl].");
        update_article_static_url($aid, $uid, $staticurl);
    }
}

//Rebuild static files
build_rss_feed($uid, NULL, FALSE);
build_opml_feed($uid, NULL, FALSE);

//Calculate how long it took
$took = time() - $tstart;
loggit(3, "It took: [$took] seconds to build static files after cartulizing article: [$aid].");


//Return the article as a json object if that was asked for
if (isset($_REQUEST['json'])) {
    //Give feedback that all went well
    $jsondata['status'] = "true";
    $jsondata['article'] = array(
        'id' => $aid,
        'title' => $title,
        'body' => $slimcontent,
        'url' => $url,
        'shorturl' => $shorturl,
        'staticurl' => $staticurl,
        'sourceurl' => $sourceurl,
        'sourcetitle' => $sourcetitle
    );
    echo json_encode($jsondata);
    return (0);
}

//Redirect to the article viewer to see it
header("Location: $showarticlepage?aid=$aid");