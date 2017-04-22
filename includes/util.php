<?php
//########################################################################################
// API for general utility type functions
//########################################################################################

use Aws\S3\S3Client;

// Logging function
function loggit($lognum, $message)
{
    //Get the big vars list
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Timestamp for this log
    date_default_timezone_set($default_timezone);
    $tstamp = date("Y.m.d - h:i:s");

    //Open the file
    switch ($lognum) {
        case 1:
            if ($log_errors_only == 1) {
                return (0);
            }
            $fd = fopen("$confroot/$log/$acclog", "a");
            break;
        case 2:
            $fd = fopen("$confroot/$log/$errlog", "a");
            break;
        case 3:
            $fd = fopen("$confroot/$log/$dbglog", "a");
            break;
    }

    //Write the message
    if (isset($_SERVER['REMOTE_ADDR'])) {
        fwrite($fd, "[$tstamp] [" . $_SERVER['REMOTE_ADDR'] . "] (" . $_SERVER['SCRIPT_NAME'] . ")(" . __LINE__ . ") " . $message . "\n");
    } else {
        fwrite($fd, "[$tstamp] [LOCAL] (" . $_SERVER['SCRIPT_NAME'] . ")(" . __LINE__ . ") " . $message . "\n");
    }

    //Close the file
    fclose($fd);

    //Return
    return (0);
}


//Calculate a TOTP value
//via: http://www.opendoorinternet.co.uk/news/2013/05/09/simple-totp-rfc-6238-in-php
function calculate_totp($seed = NULL, $time_window = 30)
{
    // Define your secret seed in hexadecimal format
    if (empty($seed)) {
        loggit(3, "Bad seed given for TOTP calculation.");
        return (FALSE);
    }

    //Convert to hex
    $secret_seed = convert_string_to_hex($seed);

    // Get the exact time from the server
    $exact_time = microtime(true);

    // Round the time down to the time window
    $rounded_time = floor($exact_time / $time_window);

    // Pack the counter into binary
    $packed_time = pack("N", $rounded_time);

    // Make sure the packed time is 8 characters long
    $padded_packed_time = str_pad($packed_time, 8, chr(0), STR_PAD_LEFT);

    // Pack the secret seed into a binary string
    $packed_secret_seed = pack("H*", $secret_seed);

    // Generate the hash using the SHA1 algorithm
    $hash = hash_hmac('sha1', $padded_packed_time, $packed_secret_seed, true);

    // Extract the 6 digit number fromt the hash as per RFC 6238
    $offset = ord($hash[19]) & 0xf;
    $otp = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, 6);

    // Add any missing zeros to the left of the numerical output
    $otp = str_pad($otp, 6, "0", STR_PAD_LEFT);

    // Display it
    return ($otp);
}


//Convert a string to a hexidecimal value
function convert_string_to_hex($string = NULL)
{
    //Check params
    if (empty($string)) {
        loggit(3, "Bad string value given for hex conversion.");
        return (FALSE);
    }

    $hex = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
}


//Convert a hex value to a string
function convert_hex_to_string($hex = NULL)
{
    //Check params
    if (empty($hex)) {
        loggit(3, "Bad hexidecimal input value.");
        return (FALSE);
    }

    $string = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $string;
}


//Generates a random string of the specified length
function random_gen($length = 8, $chars = NULL, $seed = NULL)
{
    // start with a blank string
    $rstring = "";

    // define possible characters
    if ($chars == NULL) {
        $possible = "98765432QWERTYUPASDFGHJKLZXCVBNMqwertyupasdfghjkzxcvbnm";
    } else {
        $possible = $chars;
    }

    // set up a counter
    $i = 0;

    // seed the generator if requested
    if ($seed != NULL) {
        if (settype($seed, "integer")) {
            mt_srand($seed);
        }
    }

    // add random characters to string until the length is reached
    while ($i < $length) {

        // pick a random character from the possible ones
        $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
        $rstring .= $char;
        $i++;
    }

    // done!
    return $rstring;
}


//Get error message
function get_system_message($id = NULL, $type = NULL)
{

    //If id is zero then balk
    if ($id == NULL) {
        loggit(2, "Can't lookup this string id: [$id]");
        return (FALSE);
    }
    if ($type == NULL) {
        loggit(2, "Can't lookup this string type: [$type]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "SELECT message FROM $table_string WHERE id=? AND type=?";
    $sql = $dbh->prepare($stmt) or loggit(2, $dbh->error);
    $sql->bind_param("ii", $id, $type) or loggit(2, $dbh->error);
    $sql->execute() or loggit(2, $dbh->error);
    $sql->store_result() or loggit(2, $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close() or loggit(2, $dbh->error);
        loggit(2, "Bad error message lookup attempt for id/type: [$id/$type] using: [$stmt]");
        return (FALSE);
    }
    $sql->bind_result($message) or loggit(2, $dbh->error);
    $sql->fetch() or loggit(2, $dbh->error);
    $sql->close() or loggit(2, $dbh->error);

    loggit(1, "Returned error message: [$message] for id: [$id]");

    //Return the error string
    return $message;
}


//Search an array with a regular expression
function array_ereg_search($val, $array)
{

    $i = 0;
    $return = array();

    foreach ($array as $v) {
        if (preg_match("/$val/i", serialize($v)) > 0) $return[] = $i;
        $i++;
    }

    return $return;
}


//Clean the memo input by doing a proper html-safe encoding on it.
function clean_html($htmldata = NULL)
{
    //If htmldata is zero then balk
    if ($htmldata == NULL) {
        loggit(2, "Can't work on the given html data: [$htmldata]");
        return (FALSE);
    }

    //Return a properly encoded HTML-safe string
    return Str::gpc2html($htmldata);

}


//Truncate the text in a string to a specific word count
function limit_words($text, $limit)
{
    if (strlen($text) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]);
    }
    return $text;
}


//Truncate the text in a string to a specific word count
function limit_text($text, $limit)
{
    if (strlen($text) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}


//Truncate the text in a string to a certain character count
function truncate_text($string = NULL, $length)
{
    if ($string == NULL) {
        return ("");
    }
    if (strlen($string) < $length) {
        return ($string);
    }
    $output = "";
    settype($string, 'string');
    settype($length, 'integer');
    for ($a = 0; $a < $length AND $a < strlen($string); $a++) {
        $output .= $string[$a];
    }
    return ($output);
}


//Truncate html to a specific word count, but keep it valid
//via: http://stackoverflow.com/questions/1193500/php-truncate-html-ignoring-tags
function truncate_html($html, $max_words)
{
    if (str_word_count($html) > $max_words) {
        $buffer = tidy_repair_string(limit_words($html, $max_words) . "...", array('wrap' => 0, 'show-body-only' => TRUE), 'utf8');
        $buffer = preg_replace("/\<.*\.\.\..*\>/", "", $buffer);
        return ($buffer);
    }

    return ($html);
}


function extract_urls_from_text($text) {

    if(empty($text)) {
        loggit(2, "The text to analyze for urls was blank or corrupt: [$text]");
    }

    

    return(array());
}


//Extract img, video, audio and iframe tags from a piece of html
function extract_media($html)
{
    $media_tags = array();
    $tag_types = array('a', 'img', 'audio', 'video', 'iframe');
    $idx = 0;

    //Load the document
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML(tidy_repair_string($html));
    libxml_clear_errors();

    //Loop through the given tag types and determine what type of media they reference
    foreach ($tag_types as $tagname) {
        $tags = $dom->getElementsByTagName($tagname);

        //Loop through any extracted tags of this type
        foreach ($tags as $tag) {

            //Check for an href first
            $src = (string)$tag->getAttribute('href');
            $src = trim($src);
            if (strlen($src) < 8 || empty($src)) {
                //If href value looks bogus, try for a src value
                $src = (string)$tag->getAttribute('src');
                if (strlen($src) < 8) {
                    //That looks bad too. Skip to next tag found.
                    continue;
                }
            }

            //Ignore junk
            if (strpos($src, 'feedsportal.com') !== FALSE) {
                continue;
            }
            if (strpos($src, 'feedburner.com') !== FALSE) {
                continue;
            }
            if (strpos($src, 'jw-share-this') !== FALSE) {
                continue;
            }
            if (strpos($src, 'add-to-any') !== FALSE) {
                continue;
            }

            //See if this is an external url that we've never seen before
            if (strpos($src, 'http') === 0 && !in_array_r($src, $media_tags)) {

                //Now check what type it is based on the url text
                $thistype = url_is_media($src);
                if ($thistype == 'image' || $tagname == 'img') {
                    $media_tags[$idx] = array('tag' => 'img',
                        'stag' => $tagname,
                        'type' => 'image',
                        'src' => $src,
                        'raw' => "<img src=\"$src\" />");
                    $idx++;
                } else
                if ($thistype == 'audio' || $tagname == 'audio') {
                    $media_tags[$idx] = array('tag' => 'audio',
                        'stag' => $tagname,
                        'type' => 'audio',
                        'src' => $src,
                        'raw' => "<audio src=\"$src\"></audio>");
                    loggit(3, "AUDIOSCRAPE: [$src]");
                    $idx++;
                } else
                if ($thistype == 'video' || $tagname == 'video') {
                    $media_tags[$idx] = array('tag' => 'video',
                        'stag' => $tagname,
                        'type' => 'video',
                        'src' => $src,
                        'raw' => "<video src=\"$src\"></video>");
                    $idx++;
                } else
                if ($tagname == 'iframe') {
                    $media_tags[$idx] = array('tag' => 'iframe',
                        'stag' => $tagname,
                        'type' => 'text/html',
                        'src' => $src,
                        'raw' => "<iframe src=\"$src\"></iframe>");
                    $idx++;
                } else {
                    continue;
                }
                //loggit(3, "DEBUG: $src");
            }
        }
    }

    //loggit(3, "DEBUG: media_tags: ".print_r($media_tags, TRUE));
    return ($media_tags);
}


//Take a chunk of text or html, clean it up and make is safe for the aggregator
function clean_feed_item_content($content = "", $length = 0, $asarray = FALSE, $withmedia = TRUE)
{
    if (empty($content)) {
        if ($asarray == TRUE) {
            return (array('text' => '', 'media' => ''));
        }
        return ('');
    }

    //Let's not waste time if this is not html
    if (!this_is_html($content)) {
        //loggit(3, "DEBUG: No html found in item body.");
        if ($asarray == TRUE) {
            return (array('text' => trim($content), 'media' => ''));
        }
        return (trim($content));
    }

    //First, extract all the media related tags
    $media_tags = extract_media($content);

    //Strip all line breaks since breakage is controlled by the markup
    $content = preg_replace("/[\r\n]+/", "", $content);

    //Replace encoded html with real html
    $content = str_replace('&amp;', '&', $content);
    $content = str_replace(array('&lt;', '&gt;', '&nbsp;'), array('<', '>', ' '), $content);

    //Strip out all the html tags except for the ones that control textual layout
    $content = strip_tags($content, '<p><br><hr><h1><h2><h3><h4>');

    //Strip the attributes from remaining tags
    $content = stripAttributes($content);

    //Replace continuous whitespace with just one space
    $content = preg_replace("/\ \ +/", " ", $content);

    //Replace all tags that would normally cause line breaks with actual line break codes
    $content = str_replace(array('<p>', '<p >', '</p>', '<br>', '<br/>', '<br />', '<hr>', '<hr/>', '<hr />',
        '<h1>', '</h1>', '<h2>', '</h2>', '<h3>', '</h3>', '<h4>', '</h4>'), "\n\n", $content);

    //Strip tab codes
    $content = preg_replace('/\t+/', '', $content);

    //Consolidate all strings of multiple linebreaks down to just 2
    $content = preg_replace("/\ +\n\n\ +/", "\n\n", $content);
    $content = preg_replace("/\n\ +\n/", "\n\n", $content);
    $content = preg_replace("/[\r\n]\n+/", "\n\n", $content);

    //If a length was requested, chop it
    if ($length > 0) {
        $content = truncate_html($content, $length);
    }

    //Now trim leading and trailing line breaks and white space
    $content = trim($content);

    //Return the clean content and the media tags in an array
    if ($asarray == TRUE) {
        if ($withmedia == TRUE) {
            return (array('text' => $content, 'media' => $media_tags));
        } else {
            return (array('text' => $content));
        }
    }

    //Build the string to pass back
    if ($withmedia == TRUE) {
        foreach ($media_tags as $tag) {
            $content .= $tag['raw'];
        }
    }

    return ($content);
}


//Take the html of an article and clean it up
function clean_article_content($content = "", $length = 0, $asarray = FALSE, $withmedia = TRUE)
{
    if (empty($content)) {
        if ($asarray == TRUE) {
            return (array('text' => '', 'media' => ''));
        }
        return ('');
    }

    //Let's not waste time if this is not html
    if (!this_is_html($content)) {
        //loggit(3, "DEBUG: No html found in item body.");
        if ($asarray == TRUE) {
            return (array('text' => $content, 'media' => ''));
        }
        return ($content);
    }

    //First, extract all the media related tags
    if ($withmedia == TRUE) {
        $media_tags = extract_media($content);
    }

    //Strip all line breaks since breakage is controlled by the markup
    $content = preg_replace("/[\r\n]+/", "", $content);

    //Replace encoded html with real html
    $content = str_replace('&amp;', '&', $content);
    $content = str_replace(array('&lt;', '&gt;', '&nbsp;'), array('<', '>', ' '), $content);

    //Strip out all the html tags except for the ones that control textual layout
    $content = strip_tags($content, '<p><h1><h2><h3><h4><ul><ol><li><table><thead><tbody><tr><td><th><a><img><blockquote><i><em><b><span>');

    //Pad the clean span tags with spaces to retain formatting
    $content = str_replace(array('<span>', '</span>'), array(' <span>', '</span> '), $content);

    //Strip the attributes from remaining tags
    $content = stripAttributes($content, array('href', 'src'));

    //Replace continuous whitespace with just one space
    $content = preg_replace("/\ \ +/", ' ', $content);

    //Strip tab codes
    $content = preg_replace('/\t+/', '', $content);

    //If a length was requested, chop it
    if ($length > 0) {
        $content = truncate_html($content, $length);
    }


    //Return the clean content and the media tags in an array
    if ($asarray == TRUE) {
        if ($withmedia == TRUE) {
            return (array('text' => trim($content), 'media' => $media_tags));
        } else {
            return (array('text' => trim($content)));
        }
    }

    //Build the string to pass back
    if ($withmedia == TRUE) {
        foreach ($media_tags as $tag) {
            $content .= $tag['raw'];
        }
    }

    return (trim($content));
}


//Send a status update to twitter
function tweet($uid = NULL, $content = NULL, $link = "", $media_id = "")
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($content == NULL) {
        loggit(2, "The post content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Globals
    $prefs = get_user_prefs($uid);
    //loggit(3, "Twitter key: [".$prefs['twitterkey']."]");
    //loggit(3, "Twitter secret: [".$prefs['twittersecret']."]");
    //loggit(3, "Twitter user token: [".$prefs['twittertoken']."]");
    //loggit(3, "Twitter user secret: [".$prefs['twittertokensecret']."]");
    $charcount = 138;

    //Connect to twitter using oAuth
    $connection = new tmhOAuth(array(
        'consumer_key' => $prefs['twitterkey'],
        'consumer_secret' => $prefs['twittersecret'],
        'user_token' => $prefs['twittertoken'],
        'user_secret' => $prefs['twittertokensecret'],
        'curl_ssl_verifypeer' => false
    ));

    if (!empty($link)) {
        $charcount -= 22;
    }

    if (!empty($media_id)) {
        $charcount -= 22;
    }

    //Truncate text if too long to fit in remaining space
    if (strlen($content) > $charcount) {
        $twcontent = truncate_text($content, ($charcount - 3)) . "...";
        loggit(1, "Had to truncate tweet: [$content] to: [$twcontent] for user: [$uid].");
    } else {
        $twcontent = $content;
    }

    //Assemble tweet
    $tweet = $twcontent . " " . $link;

    $twstatus = array('status' => $tweet);
    if (!empty($media_id)) {
        $twstatus['media_ids'] = array($media_id);
    }

    //Make an API call to post the tweet
    $code = $connection->request('POST', $connection->url('1.1/statuses/update'), $twstatus);

    //Log and return
    if ($code == 200) {
        loggit(1, "Tweeted a new post: [$tweet] for user: [$uid].");
        //loggit(3,"Tweeted a new post: [$tweet] for user: [$uid].");
        return (TRUE);
    } else {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(2, "Twitter post did not work posting: [$tweet] for user: [$uid]. Response code: [$twrcode|$twresponse].");
        //loggit(3,"Twitter post did not work posting: [$tweet] for user: [$uid]. Response code: [$twrcode|$twresponse].");
        return (FALSE);
    }
}


//Send a status update to twitter
function tweet_upload_picture($uid = NULL, $content = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if (empty($content)) {
        loggit(2, "The media content is blank or corrupt.");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Globals
    $prefs = get_user_prefs($uid);
    //loggit(3, "Twitter key: [".$prefs['twitterkey']."]");
    //loggit(3, "Twitter secret: [".$prefs['twittersecret']."]");
    //loggit(3, "Twitter user token: [".$prefs['twittertoken']."]");
    //loggit(3, "Twitter user secret: [".$prefs['twittertokensecret']."]");
    $charcount = 138;

    //Connect to twitter using oAuth
    $connection = new tmhOAuth(array(
        'host' => 'upload.twitter.com',
        'consumer_key' => $prefs['twitterkey'],
        'consumer_secret' => $prefs['twittersecret'],
        'user_token' => $prefs['twittertoken'],
        'user_secret' => $prefs['twittertokensecret'],
        'curl_ssl_verifypeer' => false
    ));

    //Make an API call to post the tweet
    $code = $connection->request('POST', $connection->url('1.1/media/upload'), array('media' => $content));

    //Log and return
    if ($code == 200) {
        loggit(1, "Uploaded a picture to twitter for user: [$uid].");
        loggit(3, "DEBUG: " . print_r($connection->response['response'], TRUE));
        //loggit(3,"Tweeted a new post: [$tweet] for user: [$uid].");

        //Decode the json
        $response = json_decode($connection->response['response'], TRUE);
        return ($response['media_id_string']);
    } else {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(2, "Twitter post did not work uploading picture for user: [$uid]. Response code: [$twrcode|$twresponse].");
        //loggit(3,"Twitter post did not work posting: [$tweet] for user: [$uid]. Response code: [$twrcode|$twresponse].");
        return (FALSE);
    }
}


//Get a twitter user's profile
function get_twitter_profile($username = NULL)
{
    //Check parameters
    if ($username == NULL) {
        loggit(2, "The username is blank or corrupt: [$username]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Globals
    if (!sys_twitter_is_enabled()) {
        loggit(2, "System level Twitter credentials are not enabled.  Check configuration file.");
        return (FALSE);
    }

    //Connect to twitter using oAuth
    $connection = new tmhOAuth(array(
        'consumer_key' => $tw_sys_key,
        'consumer_secret' => $tw_sys_secret,
        'user_token' => $tw_sys_token,
        'user_secret' => $tw_sys_tokensecret,
        'curl_ssl_verifypeer' => false
    ));

    //Make an API call to get the information in JSON format
    $code = $connection->request('GET', $connection->url('1.1/users/show'), array('screen_name' => $username));


    //Log and return
    if ($code == 200) {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(1, "Got twitter profile for user: [$username] on behalf of user: [$uid]. Response code: [$twrcode].");
        //loggit(3,"Got twitter profile for user: [$username]. Response code: [$twrcode|$twresponse].");
        return (json_decode($twresponse, TRUE));
    } else {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(2, "Failed to get twitter profile for user: [$username]. Response code: [$twrcode].");
        //loggit(3,"Failed to get twitter profile for user: [$username] on behalf of [$uid]. Response code: [$twrcode|$twresponse].");
        return (FALSE);
    }
}


//Search twitter for a given term and return the response as an rss feed
function twitter_search_to_rss($query = NULL)
{
    //Check parameters
    if ($query == NULL) {
        loggit(2, "The query is blank or corrupt: [$query]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Globals
    if (!sys_twitter_is_enabled()) {
        loggit(2, "System level Twitter credentials are not enabled.  Check configuration file.");
        return (FALSE);
    }

    //Connect to twitter using oAuth
    $connection = new tmhOAuth(array(
        'consumer_key' => $tw_sys_key,
        'consumer_secret' => $tw_sys_secret,
        'user_token' => $tw_sys_token,
        'user_secret' => $tw_sys_tokensecret,
        'curl_ssl_verifypeer' => false
    ));

    //Make an API call to get the information in JSON format
    $code = $connection->request('GET',
        $connection->url('1.1/search/tweets'),
        array('q' => $query)
    );

    //Log and return
    if ($code == 200) {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(3, "Twitter search for [$query] returned code: [$twrcode].");

        $twr = json_decode($twresponse, TRUE);

        loggit(1, print_r($twr, TRUE));

        $xml = new SimpleXMLElement('<rss version="2.0"></rss>');
        $xml->addChild('channel');
        $xml->channel->addChild('title', 'Twitter Search - [' . $query . ']');
        $xml->channel->addChild('link', 'http://twitter.com');
        $xml->channel->addChild('description', 'Twitter search for [' . $query . ']');
        if (isset($twr['statuses'][0]['created_at'])) {
            $xml->channel->addChild('pubDate', date(DATE_RSS, strtotime($twr['statuses'][0]['created_at'])));
        }
        foreach ($twr['statuses'] as $tweet) {
            $item = $xml->channel->addChild('item');
            $item->addChild('description', $tweet['text']);
            $item->addChild('pubDate', date(DATE_RSS, strtotime($tweet['created_at'])));
            $item->addChild('guid', 'http://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str']);
            $item->addChild('link', 'http://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str']);
        }

        return ($xml);
    } else {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(2, "Failed to perform twitter search for: [$query]. Response code: [$twrcode].");
        return (FALSE);
    }
}


//Get a timeline from twitter and return the response as an rss feed
function twitter_timeline_to_rss($user = NULL)
{
    //Check parameters
    if ($user == NULL) {
        loggit(2, "The user is blank or corrupt: [$user]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Globals
    if (!sys_twitter_is_enabled()) {
        loggit(2, "System level Twitter credentials are not enabled.  Check configuration file.");
        return (FALSE);
    }

    //Connect to twitter using oAuth
    $connection = new tmhOAuth(array(
        'consumer_key' => $tw_sys_key,
        'consumer_secret' => $tw_sys_secret,
        'user_token' => $tw_sys_token,
        'user_secret' => $tw_sys_tokensecret,
        'curl_ssl_verifypeer' => false
    ));

    //Make an API call to get the information in JSON format
    $code = $connection->request('GET',
        $connection->url('1.1/statuses/user_timeline'),
        array('screen_name' => $user)
    );

    //Log and return
    if ($code == 200) {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(3, "Twitter search for [$user] returned code: [$twrcode].");

        $twr = json_decode($twresponse, TRUE);

        $xml = new SimpleXMLElement('<rss version="2.0"></rss>');
        $xml->addChild('channel');
        $xml->channel->addChild('title', 'Twitter Timeline - [' . $user . ']');
        $xml->channel->addChild('link', 'http://twitter.com');
        $xml->channel->addChild('description', 'Twitter timeline for [' . $user . ']');
        if (isset($twr[0]['created_at'])) {
            $xml->channel->addChild('pubDate', date(DATE_RSS, strtotime($twr[0]['created_at'])));
        }
        foreach ($twr as $tweet) {
            $item = $xml->channel->addChild('item');
            $item->addChild('description', $tweet['text']);
            $item->addChild('pubDate', date(DATE_RSS, strtotime($tweet['created_at'])));
            $item->addChild('guid', 'http://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str']);
            $item->addChild('link', 'http://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id_str']);
        }

        return ($xml);
    } else {
        $twresponse = $connection->response['response'];
        $twrcode = $connection->response['code'];
        loggit(2, "Failed to perform twitter search for: [$query]. Response code: [$twrcode].");
        return (FALSE);
    }
}


//Do a HEAD request on a url to see what the Last-Modified time is
function check_head_lastmod($url, $timeout = 5)
{

    //Check parameters
    if ($url == NULL) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    $url = clean_url($url);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //don't fetch the actual page, you only want headers
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    //stop it from outputting stuff to stdout
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

    // attempt to retrieve the modification date
    curl_setopt($curl, CURLOPT_FILETIME, true);

    $result = curl_exec($curl);

    $info = curl_getinfo($curl);

    if ($info['filetime'] != -1) { //otherwise unknown
        return ($info['filetime']);
    } else {
        loggit(1, "Unknown Last-Modified time returned during head check.");
        return (FALSE);
    }

}


//Check if content at a url has been modified since a certain time
function check_url_if_modified($url, $lastmod, $timeout = 5)
{

    //Check parameters
    if ($url == NULL) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    $url = clean_url($url);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("If-Modified-Since: $lastmod"));
    //don't fetch the actual page, you only want headers
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    //stop it from outputting stuff to stdout
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

    // attempt to retrieve the modification date
    curl_setopt($curl, CURLOPT_FILETIME, true);

    $result = curl_exec($curl);

    $info = curl_getinfo($curl);

    echo print_r($info, TRUE);

}


//Do a HEAD request on a url to see what the Last-Modified time is
function check_head_size($url, $timeout = 5)
{

    //Check parameters
    if ($url == NULL) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    $url = clean_url($url);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //don't fetch the actual page, you only want headers
    //curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    //stop it from outputting stuff to stdout
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    loggit(1, "CURL: Head check on: [$url]");

    $result = curl_exec($curl);

    $info = curl_getinfo($curl);

    if ($info['download_content_length'] != -1) { //otherwise unknown
        return ($info['download_content_length']);
    } else {
        loggit(1, "CURL: " . print_r($info, TRUE));
        return ('');
    }

}


//Do a HEAD request on a url to see what the Last-Modified time is
function check_head_content_type($url, $timeout = 5)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    $url = clean_url($url);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //don't fetch the actual page, you only want headers
    //curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    //stop it from outputting stuff to stdout
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    curl_exec($curl);

    $ct = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    //loggit(3, "DEBUG: CURL: Content-type check on: [$url] returned: [$ct]");

    return ($ct);
}


//Clean up potentially problematic urls
function clean_url($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is missing or empty: [$url].");
        return (FALSE);
    }

    //Detect whether or not this url is encoded
    if (strpos($url, "%") !== FALSE) {
        //loggit(3, "Url in: [$url].");
        $url = urldecode($url);
        //loggit(3, "Url out: [$url].");
    }

    $url = str_replace("&amp;", "&", trim($url));
    $url = str_replace("feed://", "http://", $url);
    $url = str_replace(' ', '%20', $url);

    return ($url);
}


//Follow redirects to get to the final, good url
function get_final_url($url, $timeout = 5, $count = 0)
{
    $count++;
    $url = clean_url($url);
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';

    if($count > 5) {
        loggit(2, "Error: Too many redirects for url: [$url]. Abandoning...");
        return($url);
    }

    $cookie = tempnam("/tmp", "CURLCOOKIE");
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $content = curl_exec($curl);
    $response = curl_getinfo($curl);
    curl_close($curl);
    unlink($cookie);

    //loggit(3, "DEBUG: get_final_url($url) returned: [".$response['http_code']."]");

    //Normal re-direct
    if ($response['http_code'] == 301 || $response['http_code'] == 302 || $response['http_code'] == 303) {
        //loggit(3, "DEBUG: ".print_r($response, TRUE));
        ini_set("user_agent", $ua);
        $headers = get_headers($response['url']);

        $location = "";
        foreach ($headers as $value) {
            //loggit(3, "HEADER: [[".trim(substr($value, 9, strlen($value)))."]]");
            if (substr(strtolower($value), 0, 9) == "location:") {
                //loggit(3, "DEBUG: This was a normal http redirect.");
                loggit(3, "HEADER: [[" . trim(substr($value, 9, strlen($value))) . "]]");
                return get_final_url(trim(substr($value, 9, strlen($value))), 8, $count);
            }
        }
    }

    //Meta-refresh redirect
    if (preg_match("/meta.*refresh.*URL=.*(http[^'\"]*)/i", $content, $value)) {
        loggit(3, "DEBUG: This was a meta-refresh redirect.");
        if (strpos($value[1], "http") !== FALSE) {
            return get_final_url($value[1], NULL, $count);
        }
    }

    //Javascript re-direct
    if (preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/window\.location\=\"(.*)\"/i", $content, $value)) {
        //loggit(3, "DEBUG: This was a javascript redirect.");
        if (strpos($value[1], "http") !== FALSE) {
            return get_final_url($value[1], NULL, $count);
        } else {
            return $response['url'];
        }
    } else {
        //loggit(3, "DEBUG: No redirection.");
        return $response['url'];
    }
}


//Follow redirects to get to the final, good url
function get_final_url_with_cookie($url, $timeout = 5, $count = 0, $cookie = "")
{
    echo "get_final_url_with_cookie($url, $timeout, $count, $cookie)\n";

    $count++;
    if ($count == 10) {
        loggit(3, "Too many redirects for url: [$url]");
        return ("");
    }
    $url = clean_url($url);
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    if (empty($cookie)) {
        $cookie = tempnam("/tmp", "CURLCOOKIE");
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
    } else {
        //echo "COOKIEFILE: [".$cookie."]\n";
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
    }
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $content = curl_exec($curl);
    $response = curl_getinfo($curl);
    curl_close($curl);
    //unlink($cookie);

    //loggit(3, "DEBUG: get_final_url($url) returned: [".$response['http_code']."]");

    //Normal re-direct
    if ($response['http_code'] == 301 || $response['http_code'] == 302 || $response['http_code'] == 303) {
        //loggit(3, "DEBUG get_final_url($url): ".print_r($response, TRUE));
        ini_set("user_agent", $ua);
        $headers = get_headers($response['url']);

        $location = "";
        foreach ($headers as $value) {
            //loggit(3, "HEADER: [[".trim(substr($value, 9, strlen($value)))."]]");
            if (substr(strtolower($value), 0, 9) == "location:") {
                //loggit(3, "DEBUG: This was a normal http redirect.");
                //loggit(3, "HEADER: [[".trim(substr($value, 9, strlen($value)))."]]");
                return array('url' => get_final_url_with_cookie(trim(substr($value, 9, strlen($value))), 8, $count, $cookie), 'cookie' => $cookie);
            }
        }
    }

    return array('url' => $response['url'], 'cookie' => $cookie);
}


//Gets the data from a URL
function fetchUrl($url, $timeout = 30)
{
    $url = clean_url($url);

    $curl = curl_init();
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);
    curl_close($curl);
    if (!empty($cookie)) {
        //unlink($cookie);
    }

    $rcode = $response['http_code'];
    if ($rcode != 200) {
        loggit(2, "Got back response code: [$rcode] while fetching: [$url].");
        return (FALSE);
    }

    return $data;
}


//Gets the data from a URL with SSL verification
function fetchUrlSafe($url, $timeout = 30)
{
    $url = clean_url($url);

    $curl = curl_init();
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);
    curl_close($curl);
    if (!empty($cookie)) {
        //unlink($cookie);
    }

    $rcode = $response['http_code'];
    if ($rcode != 200) {
        loggit(2, "Got back response code: [$rcode] while fetching: [$url].");
        return (FALSE);
    }

    return $data;
}


//Gets the data from a URL with SSL verification
function getMastodonTimeline($hosturl, $token, $timeout = 30)
{
    if (stripos($hosturl, "http://") !== 0 && stripos($hosturl, "https://") !== 0) {
        loggit(2, "The host url must start with a protocol spec: [$hosturl].");
        return (FALSE);
    }

    $url = trim($hosturl, "/ ")."/api/v1/timelines/home";
    $url = clean_url($url);

    $curl = curl_init();
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer ".trim($token)
    ));

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);
    curl_close($curl);
    if (!empty($cookie)) {
        //unlink($cookie);
    }

    $rcode = $response['http_code'];
    if ($rcode != 200) {
        loggit(2, "Got back response code: [$rcode] while fetching: [$url].");
        return (FALSE);
    }

    return $data;
}


//Send a status update to twitter
function toot($uid = NULL, $content = NULL, $link = "", $media_id = "")
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($content == NULL) {
        loggit(2, "The post content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Globals
    $prefs = get_user_prefs($uid);
    $charcount = 498;

    if (!empty($link)) {
        $charcount -= 22;
    }

    if (!empty($media_id)) {
        $charcount -= 22;
    }

    //Truncate text if too long to fit in remaining space
    if (strlen($content) > $charcount) {
        $twcontent = truncate_text($content, ($charcount - 3)) . "...";
        loggit(1, "Had to truncate tweet: [$content] to: [$twcontent] for user: [$uid].");
    } else {
        $twcontent = $content;
    }

    //Assemble tweet
    $tweet = $twcontent . " " . $link;

    $twstatus = array('status' => $tweet);
    //$twstatus['visibility'] = "unlisted";
    if (!empty($media_id)) {
        $twstatus['media_ids'] = array($media_id);
    }

    loggit(3, "DEBUG: Toot upload: [".print_r($twstatus, TRUE)."]");

    //Make an API call to post the tweet
    $result = postUrlExtra(trim($prefs['mastodon_url'])."/api/v1/statuses", $twstatus, array(
        "Authorization: Bearer ".$prefs['mastodon_access_token']
    ), TRUE);

    //Log and return
    if ($result != FALSE) {
        loggit(1, "Tooted a new post: [$tweet] for user: [$uid].");
        return (TRUE);
    } else {
        loggit(2, "Tooting post did not work posting: [$tweet] for user: [$uid].");
        return (FALSE);
    }
}


//Upload a media file to mastodon
function toot_upload_picture($uid = NULL, $filepath = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if (empty($filepath)) {
        loggit(2, "The file path is blank or corrupt: [$filepath]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Globals
    $prefs = get_user_prefs($uid);

    //Make an API call to post the tweet
    $result = httpUploadFile(trim($prefs['mastodon_url'])."/api/v1/media", array(), array(
        "Authorization: Bearer ".$prefs['mastodon_access_token']
    ), $filepath);

    if( $result['status_code'] != 200 ) {
        loggit(2, "Uploading media file: [$filepath] to mastodon for user: [$uid] didn't work. Response code: [".$result['status_code']."]");
        return (FALSE);
    }

    $response = json_decode($result['body'], TRUE);
    $mediaid = $response['id'];

    loggit(3, "DEBUG: toot_upload_picture: [".print_r($response, TRUE)."]");

    //Log and return
    if ($result != FALSE) {
        loggit(3, "Uploaded a media file to mastodon for user: [$uid]. Got back media id: [$mediaid].");
        return ($mediaid);
    } else {
        loggit(2, "Uploading media file: [$filepath] to mastodon for user: [$uid] didn't work. Response code: [".$result['status_code']."]");
        return (FALSE);
    }
}


//Gets a feed from a URL
function fetchFeedUrl($url, $subcount = 0, $sysver = '', $timeout = 30)
{
    //Assemble a User-agent string that will report stats to the
    //feed owner if possible
    $ua = "FreedomController-Cartulary/" . $sysver;
    $uadetails = "";
    if ($subcount > 0) {
        $uadetails .= $subcount . " subscribers; ";
    }
    if (!empty($uadetails)) {
        $ua .= " (" . trim($uadetails) . ")";
    }

    //Clean up the url
    $url = clean_url($url);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);
    curl_close($curl);

    $rcode = $response['http_code'];
    if ($rcode != 200) {
        loggit(2, "Got back response code: [$rcode] while fetching: [$url].");
        return (FALSE);
    }

    return $data;
}


//Gets the data from a URL along with extra info returned */
function fetchUrlExtra($url, $timeout = 30)
{
    $url = clean_url($url);

    $curl = curl_init();
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);

    list($response['headers'], $response['body']) = explode("\r\n\r\n", $data, 2);

    $response['effective_url'] = $url;
    $response['status_code'] = $response['http_code'];

    curl_close($curl);

    //loggit(3, "DEBUG: [" . substr($response['body'], 0, 10) . "]");

    return $response;
}


//Gets the data from a URL along with extra info returned */
function postUrlExtra($url, $post_parameters = array(), $post_headers = array(), $as_json = FALSE, $timeout = 30)
{
    $url = clean_url($url);

    $curl = curl_init();
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_POST, 1);

    if($as_json) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_parameters));
        $post_headers[] = "Content-Type: application/json";
    } else {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_parameters);
    }


    if(!empty($post_headers)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $post_headers);
    }

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);

    $response['effective_url'] = $url;
    $response['status_code'] = $response['http_code'];
    $response['body'] = $data;

    curl_close($curl);

    //loggit(3, "DEBUG: [" . substr($response['body'], 0, 10) . "]");

    //return $response;
    return $response;
}


//Upload a file over http using curl
function httpUploadFile($url, $post_parameters = array(), $post_headers = array(), $filepath = NULL, $timeout = 30)
{
    //Check parameters
    if (empty($filepath)) {
        loggit(2, "The file path is blank or corrupt: [$filepath]");
        return (FALSE);
    }

    $url = clean_url($url);

    $curl = curl_init();
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_COOKIEFILE, "");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, "");
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_POST, 1);

    if(class_exists("CurlFile")) {
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, TRUE);
        $post_parameters['file'] = new CurlFile($filepath);
    } else {
        $post_parameters['file'] = "@".$filepath;
    }
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_parameters);

    if(!empty($post_headers)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $post_headers);
    }

    $data = curl_exec($curl);
    $response = curl_getinfo($curl);

    $response['effective_url'] = $url;
    $response['status_code'] = $response['http_code'];
    $response['body'] = $data;

    curl_close($curl);

    loggit(3, "DEBUG: httpUploadFile: [".print_r($response, TRUE)."]");

    //return $response;
    return $response;
}


//Better EOF handling in stream
function safe_feof($fp, &$start = NULL)
{
    $start = microtime(true);

    return feof($fp);
}


//Make an HTTP call of any type
function httpRequest($host, $port, $method, $path, $params, $timeout = 30)
{
    // Params are a map from names to values
    $paramStr = "";
    foreach ($params as $name => $val) {
        $paramStr .= $name . "=";
        $paramStr .= urlencode($val);
        $paramStr .= "&";
    }

    // Assign defaults to $method and $port, if needed
    if (empty($method)) {
        $method = 'GET';
    }
    $method = strtoupper($method);
    if (empty($port)) {
        $port = 80; // Default HTTP port
    }

    // Create the connection
    $sock = fsockopen($host, $port, $errno, $errst, $timeout);
    stream_set_timeout($sock, $timeout);
    if ($method == "GET") {
        $path .= "?" . $data;
    }
    fputs($sock, "$method $path HTTP/1.1\r\n");
    fputs($sock, "Host: $host\r\n");
    fputs($sock, "Content-type: " . "application/x-www-form-urlencoded\r\n");
    if ($method == "POST") {
        fputs($sock, "Content-length: " . strlen($paramStr) . "\r\n");
    }
    fputs($sock, "Connection: close\r\n\r\n");
    if ($method == "POST") {
        fputs($sock, $paramStr);
    }

    // Buffer the result
    $start = NULL;
    $timeout = ini_get('default_socket_timeout');
    $result = "";
    while ((!safe_feof($sock, $start) && (microtime(true) - $start) < $timeout) && ($sock != FALSE)) {
        $result .= fgets($sock, 1024);
        if ($result == FALSE) {
            break;
        }
    }

    fclose($sock);
    return $result;
}


//Construct a short url
function get_short_url($uid = NULL, $longurl = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is missing or empty: [$uid].");
        return (FALSE);
    }
    if (empty($longurl)) {
        loggit(2, "The long url is missing or empty: [$longurl].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Get the prefs for this user
    $prefs = get_user_prefs($uid);

    //Internal url shortener always takes precedence
    if (!empty($prefs['s3shortbucket'])) {
        $shortcode = get_next_short_url($prefs['lastshortcode']);
        $file = create_short_url_file($longurl, $uid);
        $result = putInS3($file, $shortcode, $prefs['s3shortbucket'], $prefs['s3key'], $prefs['s3secret'], "text/html");
        if ($result == FALSE) {
            return (FALSE);
        } else {
            update_last_shortcode($uid, $shortcode);
            return ("http://" . $prefs['s3shortbucket'] . "/" . $shortcode);
        }
    }

    //Use the given external shortener if one was specified
    if (!empty($prefs['urlshortener'])) {
        $apicall = str_replace('%@', rawurlencode($longurl), $prefs['urlshortener'], $shorted);
        $shorturl = @file_get_contents($apicall);

        if ($shorturl == FALSE || $shorted < 1) {
            loggit(2, "Failed to get a short url for long url: [$longurl]. Check shortener server: [$apicall].");
            return (FALSE);
        } else {
            loggit(1, "Got a short url: [$shorturl] for long url: [$longurl].");
            return ($shorturl);
        }
    }

    return (FALSE);
}


//Create a meta-refresh html stub to act as a short url
function create_short_url_file($url = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($url)) {
        return (FALSE);
    }

    $ac = "";
    if (!empty($uid)) {
        $prefs = get_user_prefs($uid);
        $ac = $prefs['analyticscode'];
    }

    return ("<html><head><meta http-equiv=\"refresh\" content=\"0;URL='$url'\"></head><body>$ac</body></html>");
}


//Get a list of S3 buckets for a given account
function get_s3_buckets($key, $secret)
{
    //Check parameters
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);

    //Get a list of buckets
    $buckets = $s3->listBuckets();

    //Were we able to get a list?
    if ($buckets == FALSE) {
        loggit(2, "Could not get a bucket list using: [$key | $secret].");
        return (FALSE);
    }

    //Give back the buckets array
    return ($buckets);
}


//Test to see if we have read/write access to a bucket
function test_s3_bucket_access($key, $secret, $bucket)
{
    //Check parameters
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);

    //Get a list of buckets
    $s3res = $s3->putObject("Test write from FC", $bucket, "fctestwrite", S3::ACL_PRIVATE, array());

    //Were we able to get a list?
    if (!$s3res) {
        loggit(2, "Could not write to bucket: [$bucket] using supplied credentials.");
        return (FALSE);
    }

    //Give back the buckets array
    return (TRUE);
}


//Create a bucket in s3
function create_s3_bucket($key, $secret, $bucket)
{
    //Check parameters
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);

    //Get a list of buckets
    $s3res = $s3->putBucket($bucket, S3::ACL_PUBLIC_READ);

    //Were we able to get a list?
    if (!$s3res) {
        loggit(2, "Could not create s3 bucket: [$bucket] using supplied credentials.");
        return (FALSE);
    }

    //Give back the buckets array
    return (TRUE);
}


//Get the regional location of an S3 bucket
function get_s3_bucket_location($key, $secret, $bucket)
{
    //Check parameters
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);

    //Get a list of buckets
    $bucketloc = $s3->getBucketLocation($bucket);

    //Were we able to get a list?
    if ($bucketloc == FALSE) {
        loggit(2, "Could not get a bucket location using: [$key | $secret | $bucket].");
        return (FALSE);
    }

    //Give back the buckets array
    //loggit(3, "DEBUG: Bucket: [$bucket] is located in: [$bucketloc].");
    return ($bucketloc);
}


//Set the CORS configuration on a bucket
function set_s3_bucket_cors($key, $secret, $bucket)
{
    //Check parameters
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Set up
    require_once "$confroot/$libraries/aws/aws-autoloader.php";

    $client = S3Client::factory(array("key" => $key, "secret" => $secret));

    //Set the CORS policy of this bucket
    try {
        $res = $client->putBucketCors(array(
            'Bucket' => $bucket,
            'CORSRules' => array(
                array(
                    'AllowedHeaders' => array('*'),
                    'AllowedMethods' => array('GET'),
                    'AllowedOrigins' => array('*'),
                    'ExposeHeaders' => array('Content-Type', 'Content-Length', 'Date'),
                    'MaxAgeSeconds' => 3000
                )
            )
        ));
    } catch (\Aws\S3\Exception\S3Exception $e) {
        loggit(3, "Error setting s3 cors config: " . $e->getAwsErrorCode() . " : " . $e->getMessage());
        return (FALSE);
    }

    //Give back the buckets array
    loggit(3, "S3 CORS config is now set for bucket: [$bucket]");
    return ($res);
}


//Get the regional location of an S3 bucket
function get_s3_bucket_cors($key, $secret, $bucket)
{
    //Check parameters
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);

    //Get the CORS policy of this bucket
    $cors = $s3->getBucketCrossOriginConfiguration($bucket);

    //Were we able to get it?
    if (!$cors) {
        loggit(2, "Could not get the cors policy of the bucket using: [$key | $secret | $bucket].");
        return (FALSE);
    }

    //Give back the buckets array
    //loggit(3, "DEBUG: Bucket: [$bucket] is located in: [$bucketloc].");
    return ($cors);
}


//Get the regional endpoint name for an S3 bucket
function get_s3_regional_dns($location)
{

    //Check parameters
    if (empty($location)) {
        loggit(2, "Location missing from S3 call: [$location].");
        return (FALSE);
    }

    //Open the file
    switch ($location) {
        case "EU":
            return ("s3-eu-west-1.amazonaws.com");
            break;
        case 2:
            break;
        case 3:
            break;
        default:
            return ("s3.amazonaws.com");
            break;
    }

    return ("s3.amazonaws.com");
}


//Put a string of data into an S3 file
function putInS3($content, $filename, $bucket, $key, $secret, $headers = NULL, $private = FALSE)
{

    //Check parameters
    if (empty($content)) {
        loggit(2, "Content missing from S3 put call: [$content].");
        return (FALSE);
    }
    if (empty($filename)) {
        loggit(2, "Filename missing from S3 put call: [$filename].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);


    //Construct bucket subfolder path, if any
    $s3bucket = $bucket;
    if (stripos($s3bucket, '/', 1) === FALSE) {
        $subpath = "";
    } else {
        $spstart = stripos($s3bucket, '/', 1);
        $bucket = str_replace('/', '', substr($s3bucket, 0, stripos($s3bucket, '/', 1)));
        $subpath = rtrim(substr($s3bucket, $spstart + 1), '/') . "/";
    }

    loggit(1, "putInS3(): Putting file in S3: [$filename], going to attempt bucket: [$bucket] and subpath: [$subpath].");
    //get_s3_bucket_location($key, $secret, $bucket);

    if ($private) {
        $s3res = $s3->putObject($content, $bucket, $subpath . $filename, S3::ACL_PRIVATE, array(), $headers);
    } else {
        $s3res = $s3->putObject($content, $bucket, $subpath . $filename, S3::ACL_PUBLIC_READ, array(), $headers);
    }
    if (!$s3res) {
        loggit(2, "Could not create S3 file: [$bucket/$subpath$filename].");
        //loggit(3, "Could not create S3 file: [$bucket/$subpath$filename].");
        return (FALSE);
    } else {
        $s3url = "http://s3.amazonaws.com/$bucket/$subpath$filename";
        loggit(1, "Wrote feed to S3: [$s3url].");
    }

    return (TRUE);
}


//This puts a file into S3 from an actual file
function putFileInS3($file, $filename, $bucket, $key, $secret, $contenttype = NULL, $private = FALSE)
{

    //Check parameters
    if (empty($file)) {
        loggit(2, "File missing from S3 put call: [$file].");
        return (FALSE);
    }
    if (empty($filename)) {
        loggit(2, "Filename missing from S3 put call: [$filename].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 put call: [$bucket].");
        return (FALSE);
    }
    if (empty($key)) {
        loggit(2, "Key missing from S3 put call: [$key].");
        return (FALSE);
    }
    if (empty($secret)) {
        loggit(2, "Secret missing from S3 put call: [$secret].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";

    //Set up
    require_once "$confroot/$libraries/s3/S3.php";
    $s3 = new S3($key, $secret);

    //Construct bucket subfolder path, if any
    $s3bucket = $bucket;
    if (stripos($s3bucket, '/', 1) === FALSE) {
        $subpath = "";
    } else {
        $spstart = stripos($s3bucket, '/', 1);
        $bucket = str_replace('/', '', substr($s3bucket, 0, stripos($s3bucket, '/', 1)));
        $subpath = rtrim(substr($s3bucket, $spstart + 1), '/') . "/";
    }

    loggit(1, "Putting file in S3: [$filename], going to attempt bucket: [$bucket] and subpath: [$subpath].");
    //get_s3_bucket_location($key, $secret, $bucket);

    $content = $s3->inputFile($file);
    if ($contenttype == NULL) {
        if ($private == FALSE) {
            $s3res = $s3->putObject($content, $bucket, $subpath . $filename, S3::ACL_PUBLIC_READ);
        } else {
            $s3res = $s3->putObject($content, $bucket, $subpath . $filename, S3::ACL_PRIVATE);
        }
    } else {
        if ($private == FALSE) {
            $s3res = $s3->putObject($content, $bucket, $subpath . $filename, S3::ACL_PUBLIC_READ, array(), $contenttype);
        } else {
            $s3res = $s3->putObject($content, $bucket, $subpath . $filename, S3::ACL_PRIVATE, array(), $contenttype);
        }
    }
    if (!$s3res) {
        loggit(2, "Could not create S3 file: [$bucket/$subpath$filename].");
        loggit(3, "Could not create S3 file: [$bucket/$subpath$filename].");
        return (FALSE);
    } else {
        $s3url = "http://s3.amazonaws.com/$bucket/$subpath$filename";
        loggit(1, "Wrote file to S3: [$s3url].");
    }

    return (TRUE);
}


//Calculate the next short url code in a sequence
function get_next_short_url($previousNumber)
{
    // Begin Config
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $bannedWords = "fuck,ass,dick,balls,pussy,tits,bitch,shit,cunt";
    $bannedWordCaseSensitive = FALSE;
    // End Config

    // Turn strings into arrays
    $characters = str_split($characters);
    $bannedWords = explode(",", $bannedWords);

    $previousNumberArray = array_reverse(str_split($previousNumber)); // Return an array containing the previous number, ready for incrementation

    // Check that the previous number only contains valid characters
    for ($i1 = 0; $i1 < count($previousNumberArray); $i1++) {
        if (array_search($previousNumberArray[$i1], $characters) === FALSE) {
            return "Error, please enter only alphanumeric characters"; // Throw toys out the pram
        }
    }

    $currentColumn = 0;

    do {
        // Get the character of the letter in the previous number in the character array
        if (isset($previousNumberArray[$currentColumn])) {
            $characterPosition = array_search($previousNumberArray[$currentColumn], $characters);
        } else {
            $characterPosition = array_search($previousNumberArray[$currentColumn - 1], $characters);
        }

        // Figure out if more than 1 column needs changed
        if (count($characters) - 1 != $characterPosition) {
            $newCharacter = $characters[$characterPosition + 1];
            $incrementNextColumn = false;
        } else {
            $newCharacter = $characters[0];
            $incrementNextColumn = true;
        }

        // Perform the swap
        $previousNumberArray[$currentColumn] = $newCharacter;

        // Increment column number
        $currentColumn++;
    } while ($incrementNextColumn); // Repeat if another column needs swapped

    // Reassemble number array into a string
    $previousNumberArray = array_reverse($previousNumberArray);
    $newNumber = implode("", $previousNumberArray);

    // Keep generating new numbers if a banned word is found until the number is no longer banned
    while (isBannedWord($newNumber, $bannedWords, $bannedWordCaseSensitive)) {
        $newNumber = get_next_short_url($newNumber);
    }

    // Return the new number
    return $newNumber;
}


// Banned word checker
function isBannedWord($word, $bannedWords, $caseSensitive)
{
    $isBanned = false;

    foreach ($bannedWords as $bannedWord) {
        if ($caseSensitive) {
            if ($word == $bannedWord) {
                $isBanned = true;
            }
        } else {
            if (strtolower($word) == strtolower($bannedWord)) {
                $isBanned = true;
            }
        }
    }

    return $isBanned;
}


//Update the last shortcode pref for a user
function update_last_shortcode($uid = NULL, $shortcode = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if (empty($shortcode)) {
        loggit(2, "The shortcode is blank or corrupt: [$shortcode]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_prefs SET lastshortcode=? WHERE uid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $shortcode, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Set last shortcode to: [$shortcode] for user: [$uid].");
    return (TRUE);
}


//Start a background shell process (terrifying)
function launchBackgroundProcess($call = NULL)
{

    pclose(popen($call . ' /dev/null &', 'r'));

    return true;
}


//Encode entities safely for xml output
if (!function_exists('xmlentities')) {
    function xmlentities($string)
    {
        $not_in_list = "A-Z0-9a-z\s_-";
        return preg_replace_callback("/[^{$not_in_list}]/", 'get_xml_entity_at_index_0', $string);
    }

    function get_xml_entity_at_index_0($CHAR)
    {
        if (!is_string($CHAR[0]) || (strlen($CHAR[0]) > 1)) {
            loggit(2, "XMLENTITIES: 'get_xml_entity_at_index_0' requires data type: 'char' (single character). '{$CHAR[0]}' does not match this type.");
        }
        switch ($CHAR[0]) {
            case '"':
            case '&':
            case '<':
            case '>':
                return htmlspecialchars($CHAR[0], ENT_QUOTES);
                break;
            default:
                $rch = numeric_entity_4_char($CHAR[0]);
                $apre = array('&#036;', '&#044;', '&#046;', '&#194;', '&#171;', '&#124;', '&#058;', '&#226;', '&#128;', '&#153;',
                    '&#039;', '&#047;', '&#061;', '&#156;', '&#157;', '&#148;', '&#091;', '&#093;', '&#160;', '&#063;',
                    '&#037;', '&#040;', '&#041;', '&#059;', '&#147;', '&#035;', '&#043;', '&#064;', '&#13;', '&#123;',
                    '&#125;', '&amp;#13;', '&#162;', '&#033;', '&#132;', '&#179;', '&#187;', '&#195;', '&#161;', '&#166;',
                    '&#239;', '&#191;', '&#152;', "'", '&#042;', '&#169;', '&#136;', '&#146;');
                $apst = array('$', ',', '.', '', '', '|', ':', '&apos;', '', '', '&apos;', '/', '=', '&apos;', '&apos;', '--', '[', ']', ' ', '?', '%',
                    '(', ')', ';', '&apos;', '#', '+', '@', '', '{', '}', '', '', '!', '', '&quot;', '&gt;&gt;', '', '', '...', '', '', '', '&apos;',
                    '*', '(C)', '', '&apos;');

                $rch = str_replace($apre, $apst, $rch);

                return $rch;
                break;
        }
    }

    function numeric_entity_4_char($char)
    {
        return "&#" . str_pad(ord($char), 3, '0', STR_PAD_LEFT) . ";";
    }
}


//First do a full replacement, then selectively re-convert safe tags
function safe_html($content = NULL, $tags = array())
{

    $content = str_replace("\n", '<br/>', $content);

    $content = xmlentities($content);

    $content = str_replace('&lt;br/&gt;', '<br/>', $content);


    return ($content);
}


//Search in a multidimensional array
function in_array_r($needle, $haystack, $strict = true)
{
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}


//Sanitize a url for XML inclusion
function xml_safe_url($url = NULL)
{
    if (empty($url)) {
        loggit(2, "XMLSAFEURL: Corrupt url passed in: [$url].");
        return '';
    }

    $up = explode('?', $url, 2);

    return $up[0] . rawurlencode($up[1]);

}


//Return the last item of an array
function endc($array)
{
    return end($array);
}


//Build an s3 url off of a given users prefs and a path and filename
function get_s3_url($uid = NULL, $path = NULL, $filename = NULL)
{

    if (empty($uid)) {
        loggit(2, "The user id was empty: [$uid].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get key s3 info
    $s3info = get_s3_info($uid);
    $slashpos = strpos($s3info['bucket'], "/");
    if ($slashpos === FALSE) {
        $mybucket = $s3info['bucket'];
        $myfolder = "";
    } else {
        $mybucket = substr($s3info['bucket'], 0, $slashpos);
        $myfolder = substr($s3info['bucket'], $slashpos + 1);
    }

    //Globals
    $url = '';
    $prot = 'http://';
    $host = $mybucket . '.s3.amazonaws.com';
    $path = trim($path, '/');
    $filename = ltrim($filename, '/');

    //First let's get a proper hostname value
    if (!empty($s3info['cname'])) {
        if ($s3info['sys'] == TRUE) {
            $url = $prot . trim($s3info['cname'], '/') . '/' . $s3info['uname'];
        } else {
            $url = $prot . trim($s3info['cname'], '/');
        }
    } else {
        $url = $prot . $host;
        if (!empty($s3info['bucket'])) {
            //$url .= "/".trim($s3info['bucket'], '/');
            $url .= "/" . $myfolder;
        }
    }

    $url = trim($url, "/");

    if (!empty($path)) {
        $url .= "/" . $path;
    }

    if (!empty($filename)) {
        $url .= "/" . $filename;
    }

    //loggit(3, "DEBUG: ".print_r($s3info, TRUE));
    //loggit(3, "DEBUG: $url");
    return ($url);
}


//Build an s3 url off of a given bucket
/*
function get_s3_bucket_url($bucket = NULL, $path = NULL, $filename = NULL)
{

    if (empty($bucket)) {
        loggit(2, "The bucket string was empty: [$bucket].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';


    //Globals
    $url = '';
    $prot = 'http://';
    $host = $bucket . '.s3.amazonaws.com';
    $path = trim($path, '/');
    $filename = ltrim($filename, '/');

    //First let's get a proper hostname value
    $url = $prot . $host . "/" . $path . "/" .$filename;


    $url = trim($url, "/");

    if (!empty($path)) {
        $url .= "/" . $path;
    }

    if (!empty($filename)) {
        $url .= "/" . $filename;
    }

    //loggit(3, "DEBUG: ".print_r($s3info, TRUE));
    //loggit(3, "DEBUG: $url");
    return ($url);
}
*/

//Build an s3 url for the server's river files
function get_server_river_s3_url($path = NULL, $filename = NULL)
{

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Globals
    $url = '';
    $prot = 'http://';
    $host = 's3.amazonaws.com';
    $path = trim($path, '/');
    $filename = ltrim($filename, '/');

    //Get key s3 info
    $s3info = get_sys_s3_info();

    //First let's get a proper hostname value
    if (!empty($s3info['rivercname'])) {
        $url = $prot . trim($s3info['rivercname'], '/');
    } else {
        $url = $prot . $host;
        if (!empty($s3info['riverbucket'])) {
            $url .= "/" . trim($s3info['riverbucket'], '/');
        }
    }

    if (!empty($path)) {
        $url .= "/" . $path;
    }

    if (!empty($filename)) {
        $url .= "/" . $filename;
    }

    return ($url);
}


//Remove all characters except alphanum and dashes
function stripText($text, $nl = TRUE, $leave = "", $dashes = TRUE)
{
    if ($nl) {
        $text = strtolower(trim($text));
    }
    // replace all white space sections with a dash
    if ($dashes) {
        $text = str_replace(' ', '-', $text);
    }

    // strip all non alphanum or -
    if ($dashes) {
        $clean = preg_replace('/[^A-Za-z0-9".$leave."\-]/', "", $text);
    } else {
        $clean = preg_replace('/[^A-Za-z0-9".$leave."]/', "", $text);
    }


    return $clean;
}


//Make a filename html/script safe by taking out funky stuff
function cleanFilename($text)
{
    $text = strtolower(trim($text));

    // strip all except these characters
    $clean = preg_replace('/[^A-Za-z0-9\-\.]/', "", $text);

    return $clean;
}


//Clean data for json_encode
//via: http://www.php.net/manual/en/function.json-decode.php#107107
function prepareJSON($input)
{

    //This will convert ASCII/ISO-8859-1 to UTF-8.
    //Be careful with the third parameter (encoding detect list), because
    //if set wrong, some input encodings will get garbled (including UTF-8!)
    $imput = mb_convert_encoding($input, 'UTF-8', 'ASCII,UTF-8,ISO-8859-1');

    //Remove UTF-8 BOM if present, json_decode() does not like it.
    if (substr($input, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) $input = substr($input, 3);

    return stripslashes($input);
}


//Get the alternate link from a link element in XML
function getAlternateLinkUrl($html = NULL)
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $links = array();
    $seenurls = array();
    $count = 0;

    //First look for the right way to do it
    $tags = $xpath->query('//link[@rel="alternate" and contains(@type, "rss") or contains(@type, "atom") or contains(@type, "opml")]');
    foreach ($tags as $tag) {
        $url = (string)trim($tag->getAttribute("href"));
        if (!in_array($url, $seenurls)) {
            $links[$count] = array('url' => $url,
                'type' => (string)trim($tag->getAttribute("type")),
                'title' => (string)trim($tag->getAttribute("title")),
                'text' => '',
                'element' => 'link');
            $seenurls[$count] = $url;
            $count++;
        }
    }

    //Now try and find any anchors that have rss in their hrefs
    $tags = $xpath->query('//a[contains(@href, "rss") or contains(@href, "feed")]');
    foreach ($tags as $tag) {
        $url = (string)trim($tag->getAttribute("href"));
        if (!in_array($url, $seenurls)) {
            $links[$count] = array('url' => $url,
                'type' => 'href',
                'title' => (string)trim($tag->getAttribute("title")),
                'text' => (string)trim($tag->getAttribute("title")),
                'element' => 'a');
            $seenurls[$count] = $url;
            $count++;
        }
    }

    //Now try and find any anchors that have rss in their class name
    $tags = $xpath->query('//a[contains(@class, "rss")]');
    foreach ($tags as $tag) {
        $url = (string)trim($tag->getAttribute("href"));
        if (!in_array($url, $seenurls)) {
            $links[$count] = array('url' => $url,
                'type' => 'class',
                'title' => (string)trim($tag->getAttribute("title")),
                'text' => (string)trim($tag->nodeValue),
                'element' => 'a');
            $seenurls[$count] = $url;
            $count++;
        }
    }

    //Return the array
    return ($links);
}


//Make a relative url absolute if possible
function absolutizeUrl($url = NULL, $rurl = NULL)
{
    //loggit(3, "Absolutizing url: [$url] with referer: [$rurl].");

    //Check if the url is good first
    $url = clean_url($url);
    $pos = strpos($url, 'http');
    if ($pos !== FALSE && $pos == 0) {
        return ($url);
    }

    //Check if url has a preceding slash
    $pos = strpos($url, '//');
    if ($pos !== FALSE && $pos == 0) {
        loggit(3, "Url: [$url] is scheme-relative.");
        $rp = parse_url($rurl);
        if ($rp != FALSE) {
            return ($rp['scheme'] . ":" . $url);
        } else {
            return ($url);
        }
    }

    //Check if url has a preceding slash
    $pos = strpos($url, '/');
    if ($pos !== FALSE && $pos == 0) {
        //loggit(3, "Url: [$url] is root-relative.");
        $rp = parse_url($rurl);
        if ($rp != FALSE) {
            return ($rp['scheme'] . "://" . $rp['host'] . $url);
        } else {
            return ($url);
        }
    }

    //Check if url has preceding dots
    $pos = strpos($url, '../');
    if ($pos !== FALSE && $pos == 0) {
        //loggit(3, "Url: [$url] is dot-relative.");
        $rp = parse_url($rurl);
        if ($rp != FALSE) {
            $slashpos = strrpos(rtrim($rp['path'], '/'), '/');
            $newpath = substr($rp['path'], 0, $slashpos) . "/";
            $slashpos = strrpos(rtrim($newpath, '/'), '/');
            $newpath = substr($newpath, 0, $slashpos) . "/";
            $newurl = substr($url, 3);
            return ($rp['scheme'] . "://" . $rp['host'] . $newpath . $newurl);
        } else {
            return ($url);
        }
    }

    //Fix up the referring url as a base url
    //loggit(3, "Url: [$url] is truly relative.");
    $rp = parse_url($rurl);
    if ($rp != FALSE) {
        return (rtrim($rp['scheme'] . "://" . $rp['host'] . $rp['path'], '/') . "/" . $url);
    }

    //loggit(3, "Url: [$url] was messed up.");
    return ($url);
}


//Remove duplicate array entries
function remove_dup($matriz)
{
    $aux_ini = array();
    $entrega = array();
    for ($n = 0; $n < count($matriz); $n++) {
        $aux_ini[] = serialize($matriz[$n]);
    }
    $mat = array_unique($aux_ini);
    for ($n = 0; $n < count($matriz); $n++) {
        $entrega[] = unserialize($mat[$n]);
    }
    return $entrega;
}


//Indents a flat JSON string to make it more human-readable.
//via: http://recursive-design.com/blog/2008/03/11/format-json-with-php/
function format_json($json)
{

    $result = '';
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = '    ';
    $newLine = "\n";
    $prevChar = '';
    $outOfQuotes = true;

    for ($i = 0; $i <= $strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element,
            // output a new line and indent the next line.
        } else if (($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos--;
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}


//Discover what kind of device this is
function get_device_type()
{
    $device = "";

    //Be nice to the error logs
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return ($device);
    }

    if (strstr($_SERVER['HTTP_USER_AGENT'], "iPad")) {
        $device = "ipad";
    } else if (strstr($_SERVER['HTTP_USER_AGENT'], "iPhone")) {
        $device = "iphone";
    } else if (strstr($_SERVER['HTTP_USER_AGENT'], "Android")) {
        $device = "android";
    } else if (strstr($_SERVER['HTTP_USER_AGENT'], "Windows Phone")) {
        $device = "wphone";
    }

    return ($device);
}


//Discover what kind of platform this is
function get_platform_type()
{
    $platform = "";

    //Be nice to the error logs
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return ($platform);
    }

    if (strstr($_SERVER['HTTP_USER_AGENT'], "iPad")) {
        $platform = "tablet";
    } else if (strstr($_SERVER['HTTP_USER_AGENT'], "iPhone")) {
        $platform = "mobile";
    } else if (strstr($_SERVER['HTTP_USER_AGENT'], "Android")) {
        $platform = "mobile";
    } else if (strstr($_SERVER['HTTP_USER_AGENT'], "Windows Phone")) {
        $platform = "mobile";
    }

    return ($platform);
}


//Discover what version of device this is
function get_device_version()
{
    $device = "";

    //Be nice to the error logs
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return ($device);
    }

    if (strstr($_SERVER['HTTP_USER_AGENT'], "Android 2")) {
        $device = "2";
    } else
        if (strstr($_SERVER['HTTP_USER_AGENT'], "Android 3")) {
            $device = "3";
        } else
            if (strstr($_SERVER['HTTP_USER_AGENT'], "Android 4")) {
                $device = "4";
            } else
                if (strstr($_SERVER['HTTP_USER_AGENT'], "OS 3")) {
                    $device = "3";
                } else
                    if (strstr($_SERVER['HTTP_USER_AGENT'], "OS 4")) {
                        $device = "4";
                    } else
                        if (strstr($_SERVER['HTTP_USER_AGENT'], "OS 5")) {
                            $device = "5";
                        } else
                            if (strstr($_SERVER['HTTP_USER_AGENT'], "OS 6")) {
                                $device = "6";
                            }


    return ($device);
}


//Make a friendlier version of long byte strings
//via: http://www.php.net/manual/en/function.filesize.php#106935
function format_bytes($a_bytes)
{
    if ($a_bytes < 1024) {
        return $a_bytes . ' B';
    } elseif ($a_bytes < 1048576) {
        return round($a_bytes / 1024, 2) . ' KiB';
    } elseif ($a_bytes < 1073741824) {
        return round($a_bytes / 1048576, 2) . ' MiB';
    } elseif ($a_bytes < 1099511627776) {
        return round($a_bytes / 1073741824, 2) . ' GiB';
    } elseif ($a_bytes < 1125899906842624) {
        return round($a_bytes / 1099511627776, 2) . ' TiB';
    } elseif ($a_bytes < 1152921504606846976) {
        return round($a_bytes / 1125899906842624, 2) . ' PiB';
    } elseif ($a_bytes < 1180591620717411303424) {
        return round($a_bytes / 1152921504606846976, 2) . ' EiB';
    } elseif ($a_bytes < 1208925819614629174706176) {
        return round($a_bytes / 1180591620717411303424, 2) . ' ZiB';
    } else {
        return round($a_bytes / 1208925819614629174706176, 2) . ' YiB';
    }
}


//Get some user input from the command line
function get_user_response()
{
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);

    return (trim($line));
}


//Get a filename without any trailing extensions
function chop_extension($file = NULL)
{
    $info = pathinfo($file);
    $file_name = @basename($file, '.' . $info['extension']);

    return ($file_name);
}


//Search for a substring with an array as the needle
//via: http://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos
function strposa($haystack, $needles = array(), $offset = 0)
{
    $chr = array();
    foreach ($needles as $needle) {
        $res = strpos($haystack, $needle, $offset);
        if ($res !== false) $chr[$needle] = $res;
    }
    if (empty($chr)) return false;
    return min($chr);
}


//Do our best to make a good mime type for a given url/type
function make_mime_type($url = NULL, $type = NULL)
{

    //Check params
    if (empty($url)) {
        loggit(2, "The url was blank or corrupt: [$url]");
        return (FALSE);
    }

    //Let's be clean
    $url = clean_url($url);
    $type = trim($type);

    // ----- Pictures
    if (strposa($url, array('.jpg', '.jpeg')) !== FALSE) {
        return ('image/jpeg');
    }
    if (strposa($url, array('.png')) !== FALSE) {
        return ('image/png');
    }
    if (strposa($url, array('.gif')) !== FALSE) {
        return ('image/gif');
    }
    if (strposa($url, array('.bmp')) !== FALSE) {
        return ('image/bmp');
    }
    if (strposa($url, array('gravatar.com/avatar')) !== FALSE) {
        return ('image/jpeg');
    }

    // ----- Textual
    if (strposa($url, array('.htm', '.html')) !== FALSE) {
        return ('text/html');
    }
    if (strposa($url, array('.pdf')) !== FALSE) {
        return ('application/pdf');
    }

    // ----- Audio
    if (strposa($url, array('.mp3')) !== FALSE) {
        return ('audio/mpeg');
    }
    if (strposa($url, array('.wav')) !== FALSE) {
        return ('audio/wav');
    }
    if (strposa($url, array('.m4a')) !== FALSE) {
        return ('audio/mp4');
    }
    if (strposa($url, array('.aac')) !== FALSE) {
        return ('audio/mp4');
    }
    if (strposa($url, array('.wma')) !== FALSE) {
        return ('audio/x-ms-wma');
    }
    if (strposa($url, array('.ogg')) !== FALSE) {
        return ('audio/ogg');
    }
    if (strposa($url, array('.oga')) !== FALSE) {
        return ('audio/ogg');
    }

    // ----- Video
    if (strposa($url, array('.m4v')) !== FALSE) {
        return ('video/mp4');
    }
    if (strposa($url, array('.mp4')) !== FALSE) {
        return ('video/mp4');
    }
    if (strposa($url, array('.wmv')) !== FALSE) {
        return ('video/x-ms-wmv');
    }
    if (strposa($url, array('.ogv')) !== FALSE) {
        return ('video/ogg');
    }
    if (strposa($url, array('.avi')) !== FALSE) {
        return ('video/avi');
    }
    if (strposa($url, array('.mov')) !== FALSE) {
        return ('video/quicktime');
    }
    if (strposa($url, array('.mkv')) !== FALSE) {
        return ('video/unknown');
    }

    //If nothing matched, do a head check and see if we can get it
    $ct = check_head_content_type($url);
    if (strpos($ct, '/') !== FALSE) {
        return ($ct);
    }

    //If none of those matched, see if there was a type hint
    if (!empty($type)) {
        //Does this look like a good type already?
        if (strpos($type, '/') !== FALSE) {
            return ($type);
        }
        //Is this a text type?
        if (strpos($type, 'text') !== FALSE) {
            return ("text/plain");
        }

        //Guess not.  Let's assume that what's already
        //given is a primary type and just add x-unknown
        return ($type . "/x-unknown");
    }


    //Give up
    return ("application/octet-stream");
}


//Determine what type of media a url points to based on the extension in the url
function url_is_media($url = NULL)
{

    //Be clean
    $url = clean_url($url);

    //Pictures
    if (strposa($url, array('.jpg', '.png', '.jpeg', '.gif')) !== FALSE) {
        return ('image');
    }
    //Audio
    if (strposa($url, array('.mp3', '.m4a', '.wav', '.ogg', '.wmv')) !== FALSE) {
        return ('audio');
    }
    //Video
    if (strposa($url, array('.m4v', '.mp4', '.avi', '.mov')) !== FALSE) {
        return ('video');
    }

    return (FALSE);
}


//Determine if a url points to a picture file based on the extension
function url_is_a_picture($url = NULL)
{

    if (strposa($url, array('.jpg', '.png', '.jpeg', '.gif')) !== FALSE) {
        return (TRUE);
    }

    return (FALSE);
}


//Determine if a url points to an audio file based on the extension
function url_is_audio($url = NULL)
{

    if (strposa($url, array('.mp3', '.m4a', '.wav', '.ogg', '.wmv')) !== FALSE) {
        return (TRUE);
    }

    return (FALSE);
}


//Determine if a url points to a video file based on the extension
function url_is_video($url = NULL)
{

    if (strposa($url, array('.m4v', '.mp4', '.avi', '.mov')) !== FALSE) {
        return (TRUE);
    }

    return (FALSE);
}


function get_mimetype_parent($mt = NULL)
{
    if (empty($mt)) {
        loggit(2, "Mimetype parameter is either blank or corrupt: [$mt].");
        return ("");
    }

    return (strtok($mt, '/'));
}


//Strip attributes from an html tag
//via: http://stackoverflow.com/questions/770219/how-can-i-remove-attributes-from-an-html-tag
function stripAttributes($s, $allowedattr = array())
{
    if (preg_match_all("/<[^>]*\\s([^>]*)\\/*>/msiU", $s, $res, PREG_SET_ORDER)) {
        foreach ($res as $r) {
            $tag = $r[0];
            $attrs = array();
            preg_match_all("/\\s.*=(['\"]).*\\1/msiU", " " . $r[1], $split, PREG_SET_ORDER);
            foreach ($split as $spl) {
                $attrs[] = $spl[0];
            }
            $newattrs = array();
            foreach ($attrs as $a) {
                $tmp = explode("=", $a);
                if (trim($a) != "" && (!isset($tmp[1]) || (trim($tmp[0]) != "" && !in_array(strtolower(trim($tmp[0])), $allowedattr)))) {

                } else {
                    $newattrs[] = $a;
                }
            }
            $attrs = implode(" ", $newattrs);
            $rpl = str_replace($r[1], $attrs, $tag);
            $s = str_replace($tag, $rpl, $s);
        }
    }
    return $s;
}


//Detect whether a string contains html tags
//via: http://stackoverflow.com/questions/5732758/detect-html-tags-in-a-string
function this_is_html($string)
{
    if ($string != strip_tags($string)) {
        return (TRUE);
    }
    return (FALSE);
}


//Get an external ip address for this server
function get_external_ip_address($reflector_url = NULL)
{
    //Check params
    if (empty($reflector_url)) {
        loggit(2, "The ip reflector url was blank or corrupt: [$reflector_url]");
        return (FALSE);
    }

    $data = fetchUrl($reflector_url);
    $ret = preg_match('/\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}/', $data, $ipaddr);

    if ($ret === FALSE || $ret == 0) {
        return (FALSE);
    }

    return (trim($ipaddr[0]));
}


//Create a redirect file for S3 that sends external users to the external ip address of this host
function create_external_access_file($ipaddr = NULL, $bucket = NULL, $filename = NULL)
{
    //Check params
    if (empty($ipaddr)) {
        loggit(2, "The ip address was blank or corrupt: [$ipaddr]");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "The bucket name was blank or corrupt: [$bucket]");
        return (FALSE);
    }
    if (empty($filename)) {
        loggit(2, "The file name was blank or corrupt: [$filename]");
        return (FALSE);
    }

    //Make sure we have the library
    require_once "$confroot/$libraries/s3/S3.php";

    //Get system S3 info
    $s3info = get_sys_s3_info();

    //Create a redirect stub
    $file = create_short_url_file("http://" . $ipaddr . "/");
    $result = putInS3($file, $filename, $bucket, $s3info['key'], $s3info['secret'], "text/html");


    return ($result);
}


//Set an amazon bucket to redirect to another location
function set_bucket_redirect($bucket = NULL, $location = NULL)
{

    //Check parameters
    if (empty($location)) {
        loggit(2, "Location missing from S3 redirect call: [$location].");
        return (FALSE);
    }
    if (empty($bucket)) {
        loggit(2, "Bucket missing from S3 redirect call: [$bucket].");
        return (FALSE);
    }


    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/oauth/tmhOAuth.php";
    require_once "$confroot/$libraries/s3/S3.php";

    //Get system S3 info
    $s3info = get_sys_s3_info();

    //Set up
    $s3 = new S3($s3info['key'], $s3info['secret']);

    $s3res = $s3->setBucketRedirect($bucket, $location);
    if (!$s3res) {
        loggit(3, "Could not create S3 bucket redirect: [$bucket -> $location].");
        return (FALSE);
    }

    //loggit(3, "Redirected S3 bucket: [$bucket] to [$location].");
    return (TRUE);
}


// Take an array of objects and build an rss feed from them
function convert_to_rss($items = array())
{

    //Root object
    $xml = new SimpleXMLElement('<rss version="2.0"></rss>');

    //Channel
    $xml->addChild('channel');

    //Channel attributes
    $xml->channel->addChild('title', 'title');
    $xml->channel->addChild('link', 'link');
    $xml->channel->addChild('description', 'description');
    $xml->channel->addChild('pubDate', 'pubDate');

    //Add the items
    foreach ($items as $item) {
        //Create the item
        $item = $xml->channel->addChild('item');

        //Add item attributes
        $item->addChild('title', 'title');
        $item->addChild('link', 'link');
        $item->addChild('description', 'description');
        $item->addChild('pubDate', 'pubDate');
    }

    //Make the formatting pretty
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    $feed = $dom->saveXML();


    //Give back the feed as a string
    return ($feed);
}


//Search a multi-dimensional array and return info about it if found
//via: http://www.php.net/manual/vote-note.php?id=69826&page=function.array-search&vote=up
function array_search_ext($arr, $search, $exact = true, $trav_keys = null)
{
    if (!is_array($arr) || !$search || ($trav_keys && !is_array($trav_keys))) return false;
    $res_arr = array();
    foreach ($arr as $key => $val) {
        $used_keys = $trav_keys ? array_merge($trav_keys, array($key)) : array($key);
        if (($key === $search) || (!$exact && (strpos(strtolower($key), strtolower($search)) !== false))) $res_arr[] = array('type' => "key", 'hit' => $key, 'keys' => $used_keys, 'val' => $val);
        if (is_array($val) && ($children_res = array_search_ext($val, $search, $exact, $used_keys))) $res_arr = array_merge($res_arr, $children_res);
        else if (($val === $search) || (!$exact && (strpos(strtolower($val), strtolower($search)) !== false))) $res_arr[] = array('type' => "val", 'hit' => $val, 'keys' => $used_keys, 'val' => $val);
    }
    return $res_arr ? $res_arr : false;
}


//Break a search query down into it's proper parts
function parse_search_query($inq = NULL, $section = NULL)
{
    //Punt if the query is blank
    if (empty($inq)) {
        loggit(2, "The search query was blank: [$inq].");
        return (FALSE);
    }

    //Clean input
    $inq = trim($inq);

    //Get started with an empty array
    $psearch = array(
        'section' => '',
        'like' => array(),
        'not' => array(),
        'flat' => '',
        'max' => 100
    );

    //Was a max results specified?
    $str = preg_replace('/"[^"]*"/s', '', $inq); //Strip out quoted text
    $max = stripos($str, "max:"); //Look for the position of a colon
    if ($max !== FALSE) {
        preg_match('/max:([0-9]*)/', $str, $vmax);
        $inq = preg_replace('/max:[0-9]{0,9}/s', '', $inq); //Strip out quoted text
        $psearch['max'] = $vmax[1];
    }

    //Is there a section prefix embedded in the search?
    $str = preg_replace('/"[^"]*"/s', '', $inq); //Strip out quoted text
    $col = stripos($str, ":"); //Look for the position of a colon
    if ($col === FALSE) {
        if (empty($section)) {
            loggit(3, "No section found in query and none passed to search parser.");
        } else {
            $psearch['section'] = $section;
        }
    } else {
        $psearch['section'] = trim(substr($str, 0, $col));
        //Strip off the section part
        $inq = substr($inq, $col + 1);
    }
    $psearch['section'] = strtolower($psearch['section']);

    //Search terms
    $str = trim(preg_replace('/\s+/', ' ', $inq)); //Strip repetative whitespace
    preg_match_all('/(-?"[^"]*")/', $str, $qterms);
    $str = preg_replace('/-?"[^"]*"/s', '', $str); //Strip out quoted text

    //First get non-quoted terms
    $terms = explode(' ', $str);
    foreach ($terms as $term) {
        if (!empty($term)) {
            if ($term[0] == '-') {
                $psearch['not'][] = trim(str_replace(array('"', '-'), '', $term));
            } else {
                $psearch['like'][] = trim(str_replace('"', '', $term));
            }
        }
    }
    //Now get the entire contents of each quote pair as a term
    foreach ($qterms[1] as $term) {
        if (!empty($term)) {
            if ($term[0] == '-') {
                $psearch['not'][] = trim(str_replace(array('"', '-'), '', $term));
            } else {
                $psearch['like'][] = trim(str_replace('"', '', $term));
            }
        }
    }

    //Put a flat version of just the likes in the array
    $psearch['flat'] = implode(' ', $psearch['like']);

    //Remove any empty elements from like and not
    $psearch['like'] = array_filter($psearch['like']);
    $psearch['not'] = array_filter($psearch['not']);

    loggit(3, "SEARCH: " . print_r($psearch, TRUE));
    return ($psearch);
}


//Build a SQL statement off of a parsed search query
function build_search_sql($q = NULL, $colnames = NULL)
{
    //Punt if the query is blank
    if (empty($q)) {
        loggit(2, "The search query was blank: [$q].");
        return (FALSE);
    }
    if (empty($colnames)) {
        loggit(2, "The column names were blank: [$colnames].");
        return (FALSE);
    }

    //This will be in an array
    $sqla = array(
        'text' => '',
        'bind' => ''
    );

    //Assemble sql
    $like1 = '';
    $like2 = '';
    $not1 = '';
    $not2 = '';
    $op = "AND";
    $subop = "OR";
    //Put likes together
    foreach ($q['like'] as $like) {
        $like1 .= " AND (";
        $count = 0;
        foreach ($colnames as $colname) {
            if ($count != 0) {
                $like1 .= " OR";
            }
            $like1 .= " $colname LIKE CONCAT('%', ?, '%')";
            $like2 .= "s";
            $count++;
        }
        $like1 .= ")";
    }
    //Put nots together
    foreach ($q['not'] as $not) {
        $not1 .= " AND (";
        $count = 0;
        foreach ($colnames as $colname) {
            if ($count != 0) {
                $not1 .= " AND";
            }
            $not1 .= " $colname NOT LIKE CONCAT('%', ?, '%')";
            $not2 .= "s";
            $count++;
        }
        $not1 .= ")";
    }

    //Append search criteria
    $sqla['text'] .= $like1 . $not1;

    //Assemble the bindings
    $setup = "$like2$not2";
    $refArr = array(&$setup);
    $rl = makeValuesReferenced($q['like']);
    $rn = makeValuesReferenced($q['not']);
    foreach ($rl as &$qlar) {
        foreach ($colnames as $colname) {
            $refArr[] = $qlar;
        }
    }
    foreach ($rn as &$qnar) {
        foreach ($colnames as $colname) {
            $refArr[] = $qnar;
        }
    }
    $sqla['bind'] = $refArr;

    loggit(1, "SEARCH: " . print_r($sqla, TRUE));

    return ($sqla);
}


//Convert an array of values into referenced values
//via: http://stackoverflow.com/questions/7382645/converting-array-of-values-to-an-array-of-references
function makeValuesReferenced(&$arr)
{
    $refs = array();
    foreach ($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs;

}


// Nice string conversion utility class picked up from the comments on php.net
define('STR_SYBASE', false);

class Str
{
    function gpc2sql($gpc, $maxLength = false)
    {
        return Str::pure2sql(Str::gpc2pure($gpc), $maxLength);
    }

    function gpc2html($gpc, $maxLength = false)
    {
        return Str::pure2html(Str::gpc2pure($gpc), $maxLength);
    }

    function gpc2pure($gpc)
    {
        if (ini_get('magic_quotes_sybase'))
            $pure = str_replace("''", "'", $gpc);
        else $pure = $gpc;
        return $pure;
    }

    function html2pure($html)
    {
        return html_entity_decode($html);
    }

    function html2sql($html, $maxLength = false)
    {
        return Str::pure2sql(Str::html2pure($html), $maxLength);
    }

    function pure2html($pure, $maxLength = false)
    {
        return $maxLength ? htmlentities(substr($pure, 0, $maxLength))
            : htmlentities($pure);
    }

    function pure2sql($pure, $maxLength = false)
    {
        if ($maxLength) $pure = substr($pure, 0, $maxLength);
        return (STR_SYBASE)
            ? str_replace("'", "''", $pure)
            : addslashes($pure);
    }

    function sql2html($sql, $maxLength = false)
    {
        $pure = Str::sql2pure($sql);
        if ($maxLength) $pure = substr($pure, 0, $maxLength);
        return Str::pure2html($pure);
    }

    function sql2pure($sql)
    {
        return (STR_SYBASE)
            ? str_replace("''", "'", $sql)
            : stripslashes($sql);
    }
}

//Extensions to the mysqli class to allow returning fetch_assoc possible
//via: http://www.php.net/manual/en/mysqli-stmt.fetch.php#72720
class mysqli_Extended extends mysqli
{
    protected $selfReference;

    public function __construct($dbHost, $dbUsername, $dbPassword, $dbDatabase)
    {
        parent::__construct($dbHost, $dbUsername, $dbPassword, $dbDatabase);

    }

    public function prepare($query)
    {
        $stmt = new stmt_Extended($this, $query);

        return $stmt;
    }
}

class stmt_Extended extends mysqli_stmt
{
    protected $varsBound = false;
    protected $results;

    public function __construct($link, $query)
    {
        parent::__construct($link, $query);
    }

    public function fetch_assoc()
    {
        // checks to see if the variables have been bound, this is so that when
        //  using a while ($row = $this->stmt->fetch_assoc()) loop the following
        // code is only executed the first time
        if (!$this->varsBound) {
            $meta = $this->result_metadata();
            while ($column = $meta->fetch_field()) {
                // this is to stop a syntax error if a column name has a space in
                // e.g. "This Column". 'Typer85 at gmail dot com' pointed this out
                $columnName = str_replace(' ', '_', $column->name);
                $bindVarArray[] = &$this->results[$columnName];
            }
            call_user_func_array(array($this, 'bind_result'), $bindVarArray);
            $this->varsBound = true;
        }

        if ($this->fetch() != null) {
            // this is a hack. The problem is that the array $this->results is full
            // of references not actual data, therefore when doing the following:
            // while ($row = $this->stmt->fetch_assoc()) {
            // $results[] = $row;
            // }
            // $results[0], $results[1], etc, were all references and pointed to
            // the last dataset
            foreach ($this->results as $k => $v) {
                $results[$k] = $v;
            }
            return $results;
        } else {
            return null;
        }
    }
}


//A lock helper class to make sure cron jobs don't overlap
class cronHelper
{

    private static $pid;

    function __construct()
    {
    }

    function __clone()
    {
    }

    private static function isrunning()
    {
        $pids = explode(PHP_EOL, `ps -e | awk '{print $1}'`);
        if (in_array(self::$pid, $pids))
            return TRUE;
        return FALSE;
    }

    public static function lock()
    {
        global $argv;

        //Includes
        include get_cfg_var("cartulary_conf") . '/includes/env.php';

        $lock_file = $lockdir . basename($argv[0]) . $locksuffix;

        if (file_exists($lock_file)) {
            //return FALSE;

            // Is running?
            self::$pid = file_get_contents($lock_file);
            if (self::isrunning()) {
                loggit(2, "==" . self::$pid . "== Already in progress...");
                return FALSE;
            } else {
                loggit(2, "==" . self::$pid . "== Previous job died abruptly...");
            }
        }

        self::$pid = getmypid();
        file_put_contents($lock_file, self::$pid);
        loggit(1, "==" . self::$pid . "== Lock acquired, processing the job...");
        return self::$pid;
    }

    public static function unlock()
    {
        global $argv;

        //Includes
        include get_cfg_var("cartulary_conf") . '/includes/env.php';

        $lock_file = $lockdir . basename($argv[0]) . $locksuffix;

        if (file_exists($lock_file))
            unlink($lock_file);

        loggit(1, "==" . self::$pid . "== Releasing lock...");
        return TRUE;
    }

}


//Hash a string with bcrypt via:
//via: http://stackoverflow.com/questions/4795385/how-do-you-use-bcrypt-for-hashing-passwords-in-php
class Bcrypt
{
    private $rounds;

    public function __construct($rounds = 12)
    {
        if (CRYPT_BLOWFISH != 1) {
            throw new Exception("bcrypt not supported in this installation. See http://php.net/crypt");
        }

        $this->rounds = $rounds;
    }

    public function hash($input)
    {
        $hash = crypt($input, $this->getSalt());

        if (strlen($hash) > 13)
            return $hash;

        return false;
    }

    public function verify($input, $existingHash)
    {
        $hash = crypt($input, $existingHash);

        return $hash === $existingHash;
    }

    private function getSalt()
    {
        $salt = sprintf('$2a$%02d$', $this->rounds);

        $bytes = $this->getRandomBytes(16);

        $salt .= $this->encodeBytes($bytes);

        return $salt;
    }

    private $randomState;

    private function getRandomBytes($count)
    {
        $bytes = '';

        if (function_exists('openssl_random_pseudo_bytes') &&
            (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
        ) { // OpenSSL slow on Win
            $bytes = openssl_random_pseudo_bytes($count);
        }

        if ($bytes === '' && is_readable('/dev/urandom') &&
            ($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE
        ) {
            $bytes = fread($hRand, $count);
            fclose($hRand);
        }

        if (strlen($bytes) < $count) {
            $bytes = '';

            if ($this->randomState === null) {
                $this->randomState = microtime();
                if (function_exists('getmypid')) {
                    $this->randomState .= getmypid();
                }
            }

            for ($i = 0; $i < $count; $i += 16) {
                $this->randomState = md5(microtime() . $this->randomState);

                if (PHP_VERSION >= '5') {
                    $bytes .= md5($this->randomState, true);
                } else {
                    $bytes .= pack('H*', md5($this->randomState));
                }
            }

            $bytes = substr($bytes, 0, $count);
        }

        return $bytes;
    }

    private function encodeBytes($input)
    {
        // The following is code from the PHP Password Hashing Framework
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '';
        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }
}


class Base32
{

    private static $map = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
        'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
        '='  // padding char
    );

    private static $flippedMap = array(
        'A' => '0', 'B' => '1', 'C' => '2', 'D' => '3', 'E' => '4', 'F' => '5', 'G' => '6', 'H' => '7',
        'I' => '8', 'J' => '9', 'K' => '10', 'L' => '11', 'M' => '12', 'N' => '13', 'O' => '14', 'P' => '15',
        'Q' => '16', 'R' => '17', 'S' => '18', 'T' => '19', 'U' => '20', 'V' => '21', 'W' => '22', 'X' => '23',
        'Y' => '24', 'Z' => '25', '2' => '26', '3' => '27', '4' => '28', '5' => '29', '6' => '30', '7' => '31'
    );

    /**
     *    Use padding false when encoding for urls
     *
     * @return base32 encoded string
     * @author Bryan Ruiz
     **/
    public static function encode($input, $padding = true)
    {
        if (empty($input)) return "";
        $input = str_split($input);
        $binaryString = "";
        for ($i = 0; $i < count($input); $i++) {
            $binaryString .= str_pad(base_convert(ord($input[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
        }
        $fiveBitBinaryArray = str_split($binaryString, 5);
        $base32 = "";
        $i = 0;
        while ($i < count($fiveBitBinaryArray)) {
            $base32 .= self::$map[base_convert(str_pad($fiveBitBinaryArray[$i], 5, '0'), 2, 10)];
            $i++;
        }
        if ($padding && ($x = strlen($binaryString) % 40) != 0) {
            if ($x == 8) $base32 .= str_repeat(self::$map[32], 6);
            else if ($x == 16) $base32 .= str_repeat(self::$map[32], 4);
            else if ($x == 24) $base32 .= str_repeat(self::$map[32], 3);
            else if ($x == 32) $base32 .= self::$map[32];
        }
        return $base32;
    }

    public static function decode($input)
    {
        if (empty($input)) return;
        $paddingCharCount = substr_count($input, self::$map[32]);
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($input, -($allowedValues[$i])) != str_repeat(self::$map[32], $allowedValues[$i])
            ) return false;
        }
        $input = str_replace('=', '', $input);
        $input = str_split($input);
        $binaryString = "";
        for ($i = 0; $i < count($input); $i = $i + 8) {
            $x = "";
            if (!in_array($input[$i], self::$map)) return false;
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert(@self::$flippedMap[@$input[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : "";
            }
        }
        return $binaryString;
    }
}

//Strip out nonvalid characters from xml data
//_____via http://stackoverflow.com/questions/3466035/how-to-skip-invalid-characters-in-xml-file-using-php
function stripInvalidXml($value)
{
    $ret = "";
    $current;
    if (empty($value)) {
        return $ret;
    }

    $length = strlen($value);
    for ($i = 0; $i < $length; $i++) {
        $current = ord($value{$i});
        if (($current == 0x9) ||
            ($current == 0xA) ||
            ($current == 0xD) ||
            (($current >= 0x20) && ($current <= 0xD7FF)) ||
            (($current >= 0xE000) && ($current <= 0xFFFD)) ||
            (($current >= 0x10000) && ($current <= 0x10FFFF))
        ) {
            $ret .= chr($current);
        } else {
            $ret .= " ";
        }
    }
    return $ret;
}


//Escape strings for passing into javascript
//_____via https://sixohthree.com/241/escaping
function javascript_escape($str)
{
    $new_str = '';

    $str_len = strlen($str);
    for ($i = 0; $i < $str_len; $i++) {
        $new_str .= '\\x' . dechex(ord(substr($str, $i, 1)));
    }

    return $new_str;
}


function create_s3_qrcode_from_url($uid = NULL, $value = "", $qrfilename = "")
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The uid is blank: [$uid]");
        return (FALSE);
    }
    if (empty($value)) {
        loggit(2, "The value is blank: [$value]");
        return (FALSE);
    }
    if (empty($qrfilename)) {
        loggit(1, "The qrfilename is blank: [$qrfilename]");
        $qrfilename = time() . "-" . random_gen(16) . ".png";
    }

    //Bring in qr code library
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    set_include_path("$confroot/$libraries" . PATH_SEPARATOR . get_include_path());
    include "phpqrcode/qrlib.php";
    $qrtmpfile = sys_get_temp_dir() . "/" . $uid . time() . "_qr.png";
    QRcode::png($value, $qrtmpfile);

    //Put the qrcode in s3
    if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Subpath?  Must begin with a slash
        $subpath = "/img/";
        $qrdata = @file_get_contents($qrtmpfile);

        //Put the desktop file
        $filename = $qrfilename;
        $s3res = putInS3(gzencode($qrdata), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "image/png", "Content-Encoding" => "gzip"));
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        } else {
            $s3url = get_s3_url($uid, $subpath, $filename);
            loggit(1, "Wrote desktop river to S3 at url: [$s3url].");
        }
    } else {
        return "";
    }

    //Clean up
    unlink($qrtmpfile);

    return $s3url;
}


class Rest
{
    public function __construct($req = NULL)
    {
        //Try to grab the global REQUEST if none passed in
        if (empty($req)) {
            $req = $_REQUEST;
        }
    }
}

//Diff engine for text
//__via: https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
function diff($old, $new)
{
    $matrix = array();
    $maxlen = 0;
    foreach ($old as $oindex => $ovalue) {
        $nkeys = array_keys($new, $ovalue);
        foreach ($nkeys as $nindex) {
            $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            if ($matrix[$oindex][$nindex] > $maxlen) {
                $maxlen = $matrix[$oindex][$nindex];
                $omax = $oindex + 1 - $maxlen;
                $nmax = $nindex + 1 - $maxlen;
            }
        }
    }
    if ($maxlen == 0) return array(array('d' => $old, 'i' => $new));
    return array_merge(
        diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}


//Diff engine for html
//__via: https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
function htmlDiff($old, $new)
{
    $ret = '';
    $diff = diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
    foreach ($diff as $k) {
        if (is_array($k))
            $ret .= (!empty($k['d']) ? "<del>" . implode(' ', $k['d']) . "</del> " : '') .
                (!empty($k['i']) ? "<ins>" . implode(' ', $k['i']) . "</ins> " : '');
        else $ret .= $k . ' ';
    }
    return $ret;
}


//Make sure all strings are utf-8 valid for json
function utf8ize($d)
{
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string($d)) {
        return iconv(mb_detect_encoding($d, mb_detect_order(), true), "UTF-8", $d);
    }
    return $d;
}


function to_utf8($string)
{
// From http://w3.org/International/questions/qa-forms-utf-8.html
    if (preg_match('%^(?:
      [\x09\x0A\x0D\x20-\x7E]            # ASCII
    | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
    | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
    | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
    | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
    | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
    | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
    | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
    )*$%xs', $string)) {
        return $string;
    } else {
        return iconv('CP1252', 'UTF-8', $string);
    }
}


//Add a file to the ipfs DHT space
function add_file_to_ipfs($filepath = NULL)
{
    //Check parameters
    if (empty($filepath)) {
        loggit(2, "Location missing from S3 redirect call: [$location].");
        return (FALSE);
    }


    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/ipfs/ipfs.class.php";

    //Create an IPFS object
    $ipfs = new IPFS("localhost", "8080", "5001");

    $imageContent = file_get_contents($filepath);
    $hash = $ipfs->add($imageContent);

    loggit(3, "Added file: [$filepath] to IPFS with hash: [$hash].");
    return ($hash);
}


//Add a file to the ipfs DHT space
function add_content_to_ipfs($content = NULL)
{
    //Check parameters
    if (empty($content)) {
        loggit(2, "Content for adding to ipfs was blank: [$content].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/ipfs/ipfs.class.php";

    //Create an IPFS object
    $ipfs = new IPFS("localhost", "8080", "5001");

    $hash = $ipfs->add($content);

    loggit(3, "Added content to IPFS with hash: [$hash].");
    return ($hash);
}


//Get content from the ipfs DHT space
function get_content_from_ipfs($hash = NULL)
{
    //Check parameters
    if (empty($hash)) {
        loggit(2, "Hash to get from ipfs is blank: [$hash].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/ipfs/ipfs.class.php";

    //Create an IPFS object
    $ipfs = new IPFS("localhost", "8080", "5001");

    $size = $ipfs->size($hash);

    if ($size > 7866189) {
        loggit(3, "Content from IPFS is too big: [$size] for hash: [$hash].");
        $content = "";
    } else {
        $content = $ipfs->cat($hash);
    }


    loggit(3, "Got content from IPFS with hash: [$hash].");
    return ($content);
}


//Encode just the ampersands from a url to make them xml safe
function clean_url_for_xml($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "URL is blank: [$hash].");
        return (FALSE);
    }


    $newurl = htmlspecialchars(str_replace('&', '%26', $url));

    loggit(1, "Returned url: [$newurl] for url: [$url].");
    return ($newurl);
}


//Remove whitespace between markup tags
function remove_non_tag_space($string)
{
    $pattern = '/>\s+</';
    $replacement = '><';
    return preg_replace($pattern, $replacement, $string);
}