<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_cgi_init_with_followup.php" ?>
<?

if (isset($_REQUEST['json'])) {
    // Json header
    header("Cache-control: no-cache, must-revalidate");
    header("Content-Type: application/json");
    $jsondata = array();
    $json = TRUE;
} else {
    $json = FALSE;
}

//Globals
$html_only = true;
$ispdf = FALSE;
$linkonly = FALSE;

// set include path
set_include_path("$confroot/$libraries" . PATH_SEPARATOR . get_include_path());

// Get a start time
$tstart = time();

// Autoloading of classes allows us to include files only when they're
// needed. If we've got a cached copy, for example, only Zend_Cache is loaded.
function __autoload($class_name)
{
    static $mapping = array(
        // Include SimplePie for RSS/Atom parsing
        'SimplePie' => 'simplepie/simplepie.class.php',
        'SimplePie_Misc' => 'simplepie/simplepie.class.php',
        'SimplePie_HTTP_Parser' => 'simplepie/simplepie.class.php',
        'SimplePie_File' => 'simplepie/simplepie.class.php',
        // Include FeedCreator for RSS/Atom creation
        'FeedWriter' => 'feedwriter/FeedWriter.php',
        'FeedItem' => 'feedwriter/FeedItem.php',
        // Include ContentExtractor and Readability for identifying and extracting content from URLs
        'ContentExtractor' => 'content-extractor/ContentExtractor.php',
        'SiteConfig' => 'content-extractor/SiteConfig.php',
        'Readability' => 'readability/Readability.php',
        // Include Humble HTTP Agent to allow parallel requests and response caching
        'HumbleHttpAgent' => 'humble-http-agent/HumbleHttpAgent.php',
        'SimplePie_HumbleHttpAgent' => 'humble-http-agent/SimplePie_HumbleHttpAgent.php',
        'CookieJar' => 'humble-http-agent/CookieJar.php',
        // Include IRI class for resolving relative URLs
        'IRI' => 'iri/iri.php',
        // Include Zend Cache to improve performance (cache results)
        'Zend_Cache' => 'Zend/Cache.php',
        // Include Zend CSS to XPath for dealing with custom patterns
        'Zend_Dom_Query_Css2Xpath' => 'Zend/Dom/Query/Css2Xpath.php'
    );
    if (isset($mapping[$class_name])) {
        //echo "Loading $class_name\n<br />";
        require_once $mapping[$class_name];
        return true;
    } else {
        return false;
    }
}
function convert_to_utf8($html, $header = null)
{
    $encoding = null;
    if ($html || $header) {
        if (is_array($header)) $header = implode("\n", $header);
        if (!$header || !preg_match_all('/^Content-Type:\s+([^;]+)(?:;\s*charset=["\']?([^;"\'\n]*))?/im', $header, $match, PREG_SET_ORDER)) {
            // error parsing the response
        } else {
            $match = end($match); // get last matched element (in case of redirects)
            if (isset($match[2])) $encoding = trim($match[2], '"\'');
        }
        if (!$encoding) {
            if (preg_match('/^<\?xml\s+version=(?:"[^"]*"|\'[^\']*\')\s+encoding=("[^"]*"|\'[^\']*\')/s', $html, $match)) {
                $encoding = trim($match[1], '"\'');
            } elseif (preg_match('/<meta\s+http-equiv=["\']Content-Type["\'] content=["\'][^;]+;\s*charset=["\']?([^;"\'>]+)/i', $html, $match)) {
                if (isset($match[1])) $encoding = trim($match[1]);
            }
        }
        if (!$encoding) {
            $encoding = 'utf-8';
        } else {
            if (strtolower($encoding) != 'utf-8') {
                if (strtolower($encoding) == 'iso-8859-1') {
                    // replace MS Word smart qutoes
                    $trans = array();
                    $trans[chr(130)] = '&sbquo;'; // Single Low-9 Quotation Mark
                    $trans[chr(131)] = '&fnof;'; // Latin Small Letter F With Hook
                    $trans[chr(132)] = '&bdquo;'; // Double Low-9 Quotation Mark
                    $trans[chr(133)] = '&hellip;'; // Horizontal Ellipsis
                    $trans[chr(134)] = '&dagger;'; // Dagger
                    $trans[chr(135)] = '&Dagger;'; // Double Dagger
                    $trans[chr(136)] = '&circ;'; // Modifier Letter Circumflex Accent
                    $trans[chr(137)] = '&permil;'; // Per Mille Sign
                    $trans[chr(138)] = '&Scaron;'; // Latin Capital Letter S With Caron
                    $trans[chr(139)] = '&lsaquo;'; // Single Left-Pointing Angle Quotation Mark
                    $trans[chr(140)] = '&OElig;'; // Latin Capital Ligature OE
                    $trans[chr(145)] = '&lsquo;'; // Left Single Quotation Mark
                    $trans[chr(146)] = '&rsquo;'; // Right Single Quotation Mark
                    $trans[chr(147)] = '&ldquo;'; // Left Double Quotation Mark
                    $trans[chr(148)] = '&rdquo;'; // Right Double Quotation Mark
                    $trans[chr(149)] = '&bull;'; // Bullet
                    $trans[chr(150)] = '&ndash;'; // En Dash
                    $trans[chr(151)] = '&mdash;'; // Em Dash
                    $trans[chr(152)] = '&tilde;'; // Small Tilde
                    $trans[chr(153)] = '&trade;'; // Trade Mark Sign
                    $trans[chr(154)] = '&scaron;'; // Latin Small Letter S With Caron
                    $trans[chr(155)] = '&rsaquo;'; // Single Right-Pointing Angle Quotation Mark
                    $trans[chr(156)] = '&oelig;'; // Latin Small Ligature OE
                    $trans[chr(159)] = '&Yuml;'; // Latin Capital Letter Y With Diaeresis
                    $html = strtr($html, $trans);
                }
                $html = SimplePie_Misc::change_encoding($html, $encoding, 'utf-8');

                /*
                if (function_exists('iconv')) {
                    // iconv appears to handle certain character encodings better than mb_convert_encoding
                    $html = iconv($encoding, 'utf-8', $html);
                } else {
                    $html = mb_convert_encoding($html, 'utf-8', $encoding);
                }
                */
            }
        }
    }
    return $html;
}
function makeAbsolute($base, $elem)
{
    $base = new IRI($base);
    foreach (array('a' => 'href', 'img' => 'src') as $tag => $attr) {
        $elems = $elem->getElementsByTagName($tag);
        for ($i = $elems->length - 1; $i >= 0; $i--) {
            $e = $elems->item($i);
            //$e->parentNode->replaceChild($articleContent->ownerDocument->createTextNode($e->textContent), $e);
            makeAbsoluteAttr($base, $e, $attr);
        }
        if (strtolower($elem->tagName) == $tag) makeAbsoluteAttr($base, $elem, $attr);
    }
}
function makeAbsoluteAttr($base, $e, $attr)
{
    if ($e->hasAttribute($attr)) {
        // Trim leading and trailing white space. I don't really like this but
        // unfortunately it does appear on some sites. e.g.  <img src=" /path/to/image.jpg" />
        $url = trim(str_replace('%20', ' ', $e->getAttribute($attr)));
        $url = str_replace(' ', '%20', $url);
        if (!preg_match('!https?://!i', $url)) {
            $absolute = IRI::absolutize($base, $url);
            if ($absolute) {
                $e->setAttribute($attr, $absolute);
            }
        }
    }
}

////////////////////////////////
// Check for feed URL
////////////////////////////////
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

//////////////////////////////////
// Set up HTTP agent
//////////////////////////////////
$http = new HumbleHttpAgent();

//See if the response returned was actually a meta-refresh forwarding document

//##: -------  PRE-PROCESS the URL here to make sure we dodge any weirdness like proxies or non-HTML content-types
//Feed proxy?
if (preg_match('/feedproxy\.google\.com/i', $url)) {
    $oldurl = $url;
    $url = get_final_url($oldurl);
    loggit(3, "Converting feedproxy url: [$oldurl] to [$url].");
}
//##: ------- END PRE-PROCESS of URL -----------------------------------------------------------------------------


$response = fetchUrlExtra($url);
//loggit(3, "DEBUG: ".print_r($response, TRUE));
$mret = preg_match('|http-equiv.*refresh.*content="\s*\d+\s*;\s*url=\'?(.*?)\'?\s*"|i', $response['body'], $mrmatches);
if (($mret > 0) && !empty($mrmatches[1])) {
    //loggit(3, "Found a meta refresh pointing to: [" . $mrmatches[1] . "].");
    $url = get_final_url($mrmatches[1]);
    $response = fetchUrlExtra($url);
}
$html = $response['body'];

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

    if( preg_match("/\<p.*class=\"title.*\<a.*class=\"title.*href=\"(.*)\"/iU", $html, $matches) ) {
        $url = get_final_url($matches[1]);
        loggit(3, "Reddit link: [".$url."]");
        $response = fetchUrlExtra($url);
        $html = $response['body'];
    } else {
        loggit(2, "Couldn't extract Reddit link.");
    }
}

//Is this a PDF?
if( substr($response['body'], 0, 4) == "%PDF" ) {
    $ispdf = TRUE;
    $pdfbody = $response['body'];
    loggit(3, "The url: [$url] is a PDF document.");
}

// ---------- BEGIN ARTICLE EXISTENCE CHECK ----------
//Is this URL already in the database?
loggit(3, "Received request for article at: [$url].");
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
    ///////////////////////////////////////////////
    // Extraction pattern
    ///////////////////////////////////////////////
    $auto_extract = true;
    $extract_pattern = (isset($_REQUEST['what']) ? trim($_REQUEST['what']) : 'auto');
    if (($extract_pattern != '') && ($extract_pattern != 'auto')) {
        // split pattern by space (currently only descendants of 'auto' are recognised)
        $extract_pattern = preg_split('/\s+/', $extract_pattern, 2);
        if ($extract_pattern[0] == 'auto') { // parent selector is 'auto'
            $extract_pattern = $extract_pattern[1];
        } else {
            $extract_pattern = implode(' ', $extract_pattern);
            $auto_extract = false;
        }
        // Convert CSS to XPath
        // Borrowed from Symfony's cssToXpath() function: https://github.com/fabpot/symfony/blob/master/src/Symfony/Component/CssSelector/Parser.php
        // (Itself based on Python's lxml library)
        if (preg_match('#^\w+\s*$#u', $extract_pattern, $match)) {
            $extract_pattern = '//' . trim($match[0]);
        } elseif (preg_match('~^(\w*)#(\w+)\s*$~u', $extract_pattern, $match)) {
            $extract_pattern = sprintf("%s%s[@id = '%s']", '//', $match[1] ? $match[1] : '*', $match[2]);
        } elseif (preg_match('#^(\w*)\.(\w+)\s*$#u', $extract_pattern, $match)) {
            $extract_pattern = sprintf("%s%s[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]", '//', $match[1] ? $match[1] : '*', $match[2]);
        } else {
            // if the patterns above do not match, invoke Zend's CSS to Xpath function
            $extract_pattern = Zend_Dom_Query_Css2Xpath::transform($extract_pattern);
        }
    } else {
        $extract_pattern = false;
    }

    $format = 'rss';

    //////////////////////////////////
    // Set up Content Extractor
    //////////////////////////////////
    $extractor = new ContentExtractor(dirname(__FILE__) . '/site_config/custom', new ContentExtractor(dirname(__FILE__) . '/site_config/standard'));


    ////////////////////////////////////////////////////////////////////////////////
    // Extract content from HTML (if URL is not feed or explicit HTML request has been made)
    ////////////////////////////////////////////////////////////////////////////////
    if ($html_only || !$result) {
        unset($feed, $result);

        //Get the page
        if ($response) {
            $effective_url = $response['effective_url'];
            loggit(3, "Article effective url is: [$effective_url].");

            $html = $response['body'];

            //loggit(3, "ARTICLE: [$html]");
            if( empty($html) ) {
                loggit(3, "DEBUG: Blank content returned for html.");
                //loggit(3, "DEBUG: ".print_r($response, TRUE));
            }
            // remove strange things here
            $html = str_replace('</[>', '', $html);

            // Convert non-standard elements to divs

            $html = preg_replace("/<article[^>]+\>/i", "<div>", $html);
            $html = preg_replace("/<\/article[^>]+\>/i", "</div>", $html);
            $html = preg_replace("/<br[^>]*\>/i", "<br><br>", $html);
            $html = preg_replace("/<ul class=\"outline\"><li class=\"ou outline\">(.*)<\/li><\/ul>/i", "<div>$1</div>", $html);


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

        } else

        //Is this an image
        if (url_is_a_picture($url)) {
            loggit(3, "Getting an image.");
            loggit(3, "Image source: [" . $url . "]");
            $content = '<br/><img style="width:600px;" src="' . $url . '"></img>';
            $analysis = "";
            $slimcontent = $content;
        } else

        //Is this audio
        if (url_is_audio($url)) {
            loggit(3, "Getting an audio url.");
            loggit(3, "Audio source: [" . $url . "]");
            $mt = make_mime_type($url);
            $content = '<br/><audio style="width:400px" controls="true"><source src="' . $url . '" type="' . $mt . '"></audio>';
            $analysis = "";
            $slimcontent = $content;
        } else

        //Is this video
        if (url_is_video($url)) {
            loggit(3, "Getting a video url.");
            loggit(3, "Video source: [" . $url . "]");
            $mt = make_mime_type($url);
            $content = '<br/><video style="width:95%;margin:0 auto;display:block;" controls="true"><source src="' . $url . '" type="' . $mt . '"></video>';
            $analysis = "";
            $slimcontent = $content;
        } else

        //Is this an imgur link?
        if (preg_match('/imgur\.com/i', $url)) {
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
        } else

        //Is this a wordpress post?
        if (preg_match('/\<div.*class.*post-content\>/i', $html)) {
            loggit(2, "DEBUG: ----------------------> Getting a wordpress post.");

            $dom = new DomDocument();
            $dom->loadHTML($html);
            $classname = 'post-content';
            $finder = new DomXPath($dom);
            $nodes = $finder->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
            $tmp_dom = new DOMDocument();
            foreach ($nodes as $node)
            {
                $tmp_dom->appendChild($tmp_dom->importNode($node,true));
            }
            $content.=trim($tmp_dom->saveHTML());

            $analysis = "";
            $slimcontent = $content;

        //Is this a PDF?
        } else
        if ( $ispdf ) {
            loggit(3, "Cartulizing a PDF.");
            $content = '';
            include "$confroot/$libraries/PDFParser/vendor/autoload.php";
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseContent($pdfbody);
            foreach ($pdf->getPages() as $page) {
                $content .= "<p>".$page->getText()."</p>";
            }
            //$content = $pdf->getText();
            //Do textual analysis and save it in the database
            $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));
            //Reduce all that whitespace
            $slimcontent = clean_article_content(preg_replace('~>\s+<~', '><', $content), 0, FALSE, FALSE);

        //Normal web page
        } else {
            loggit(3, "Cartulizing html.");
            //Set up an extraction
            if ($auto_extract) {
                $extract_result = $extractor->process($html, $effective_url);
                if (!$extract_result) {
                    if ($json == TRUE) {
                        //Give feedback that all was not well
                        $jsondata['status'] = "false";
                        $jsondata['article'] = array('id' => 'error',
                            'title' => '',
                            'body' => '<center><p>Extraction failed.  Click <a href="' . $url . '">here</a> to link out to the full source article.</p></center>',
                            'url' => $url,
                            'shorturl' => '',
                            'sourceurl' => '',
                            'sourcetitle' => ''
                        );
                        echo json_encode($jsondata);
                        return (0);
                    } else {
                        //echo "<html><head><meta http-equiv=\"refresh\" content=\"2;URL='$url'\"></head><body><p>Extraction failed. Forwarding to original url in 2 seconds.</p><p>Link is below if redirect does not work:<br/><a href=\"$url\">$url</a></p></body></html>";
                        echo $html;
                        exit(1);
                    }
                }
                $readability = $extractor->readability;
                $content_block = $extractor->getContent();
                $title = $extractor->getTitle();
            } else {
                $readability = new Readability($html, $effective_url);
                // content block is entire document
                $content_block = $readability->dom;
                //TODO: get title
                $title = '';
            }

            //Extract the body content
            if ($extract_pattern) {
                $xpath = new DOMXPath($readability->dom);
                $elems = @$xpath->query($extract_pattern, $content_block);
                // check if our custom extraction pattern matched
                if ($elems && $elems->length > 0) {
                    // get the first matched element
                    $content_block = $elems->item(0);
                    // clean it up
                    $readability->removeScripts($content_block);
                    $readability->prepArticle($content_block);
                } else {
                    $content_block = $readability->dom->createElement('p', 'Sorry, could not extract content');
                }
            }
            $readability->clean($content_block, 'select');
            makeAbsolute($effective_url, $content_block);
            //footnotes
            if ($extract_pattern) {
                // get outerHTML
                $content = $content_block->ownerDocument->saveXML($content_block);
            } else {
                if ($content_block->childNodes->length == 1 && $content_block->firstChild->nodeType === XML_ELEMENT_NODE) {
                    $content = $content_block->firstChild->innerHTML;
                } else {
                    $content = $content_block->innerHTML;
                }
            }
            unset($readability, $html);

            //Do textual analysis and save it in the database
            $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));

            //Reduce all that whitespace
            //$slimcontent = preg_replace('~>\s+<~', '><', $content);
            $slimcontent = clean_article_content(preg_replace('~>\s+<~', '><', $content), 0, FALSE, FALSE);

        }

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
//Was a title specified in the request?  If so, set that as the title instead of the extracted one
if (isset($_REQUEST['title'])) {
    if (!empty($_REQUEST['title'])) {
        $title = $_REQUEST['title'];
        if (strpos($sourceurl, 'twitter.com') !== FALSE) {
            $title = '@' . $title;
        }
    }
}
$title = trim($title);
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
    $jsondata['article'] = array('id' => $aid,
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