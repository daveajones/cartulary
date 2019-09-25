<?php
//########################################################################################
// API for managing feeds and feed items
//########################################################################################


//Test if the given content is an opml outline
function is_feed($content = NULL)
{
    //Check parameters
    if ($content == NULL) {
        loggit(2, "The content to test is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Load the content into a simplexml object
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($x === FALSE) {
        loggit(2, "The content didn't parse correctly: [".libxml_get_last_error()->message."]");
        libxml_clear_errors();
        return (FALSE);
    }
    libxml_clear_errors();

    //Look for xml type nodes
    if ((string)$x->getName() == "rss") {
        loggit(1, "Found a channel element. Looks like an RSS feed.");
        return ("application/rss+xml");
    }
    if ((string)$x->getName() == "feed") {
        loggit(1, "Found a feed element. Looks like an ATOM feed.");
        return ("application/atom+xml");
    }

    //None of the tests passed so return FALSE
    loggit(1, "The content tested was not an xml-based feed.");
    return (FALSE);
}


//Test if the given content is an opml outline
function is_jsonfeed($content = NULL)
{
    //Check parameters
    if (empty($content)) {
        loggit(2, "The content to test is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Is it even json?
    if( !is_json($content, FALSE)) {
        loggit(2, "This feed is not valid jsonfeed.");
        return (FALSE);
    }

    $jsonFeed = json_decode($content, TRUE);
    //loggit(3, "JSONFEED: ".print_r($jsonFeed, TRUE));

    //Look for key elements
    if( !isset($jsonFeed['version']) ) return FALSE;
    if( !isset($jsonFeed['title']) ) return FALSE;
    if( !isset($jsonFeed['items']) ) return FALSE;

    //None of the tests passed so return FALSE
    loggit(1, "The content seems to be a valid jsonfeed.");
    return ("application/json");
}


//Test if the given content is a valid feed
function feed_is_valid($content = NULL)
{
    //Check parameters
    if (empty($content)) {
        loggit(2, "The content to test is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Load the content into a simplexml object
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Look for rss nodes
    if (isset($x->channel)) {
        loggit(1, "Found a channel node. This content looks like RSS or RDF.");
        return (TRUE);
    }

    //Look for atom nodes
    if (isset($x->entry)) {
        loggit(1, "Found and entry node. This content looks like ATOM.");
        return (TRUE);
    }

    //See if it's JSONfeed
    if (is_jsonfeed($content)) {
        loggit(1, "This content is JSON. Assuming it's a JSONfeed.");
        return (TRUE);
    }

    //None of the tests passed so return FALSE
    loggit(2, "The content tested was not a valid feed.");
    return (FALSE);
}


//Get the title of a feed from it's content
function get_feed_title($content = NULL)
{
    //Check parameters
    if ($content == NULL) {
        loggit(2, "The content of the feed is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Load the content into a simplexml object
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Look for a title node in the rss
    if (isset($x->channel->title)) {
        foreach ($x->channel->title as $entry) {
            loggit(1, "Found a title node: [$entry].");
            return ((string)$entry);
        }
    }

    //Look for atom nodes
    if (isset($x->title)) {
        foreach ($x->title as $entry) {
            loggit(1, "Found a title node: [$entry].");
            return ((string)$entry);
        }
    }

    //None of the tests passed so return FALSE
    loggit(1, "Could not find a title for this feed.");
    return (FALSE);
}


//Get the description element of a feed from it's content
function get_feed_description($content = NULL)
{
    //Check parameters
    if ($content == NULL) {
        loggit(2, "The content of the feed is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Load the content into a simplexml object
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Look for a title node in the rss
    foreach ($x->channel->description as $entry) {
        loggit(1, "Found a description node: [$entry].");
        return ((string)$entry);
    }

    //Look for atom nodes
    foreach ($x->subtitle as $entry) {
        loggit(1, "Found a subtitle(description) node: [$entry].");
        return ((string)$entry);
    }

    //None of the tests passed so return FALSE
    loggit(1, "Could not find a description/subtitle for this feed.");
    return (FALSE);
}


//Get the link of a feed from it's content
function get_feed_link($content = NULL)
{
    //Check parameters
    if ($content == NULL) {
        loggit(2, "The content of the feed is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Load the content into a simplexml object
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Look for a link node in the rss
    if (isset($x->channel->link)) {
        foreach ($x->channel->link as $entry) {
            loggit(1, "Found a link node: [$entry].");
            $link = (string)$entry;
            if (!empty($link)) {
                return ($link);
            }
        }
    }

    //Look for atom nodes
    if (isset($x->link)) {
        foreach ($x->link as $entry) {
            loggit(1, "Found a link node: [$entry].");
            $link = (string)$entry['href'];
            if (!empty($link)) {
                return ($link);
            }
        }
    }

    //None of the tests passed so return FALSE
    loggit(1, "Could not find a link for this feed.");
    return (FALSE);
}


//Does the feed contain a source:avatar or sopml:avatar?
//TODO: this function needs some serious updating
function get_feed_avatar($x = NULL)
{
    //Check parameters
    if (empty($x)) {
        loggit(2, "The xml object of the feed is blank or corrupt: [$x]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Grab namespaces
    $namespaces = $x->getDocNamespaces();

    //If there's no namespace
    if (!isset($namespaces['source'])) {
        //None of the tests passed so return FALSE
        loggit(1, "No microblog namespace defined for this feed.");
        return ("");
    }

    //Search for an avatar
    $ns_microblog = $x->channel->children($namespaces['source']);
    if (isset($ns_microblog->avatar)) {
        $url = $ns_microblog->avatar;
        loggit(1, "The avatar of this feed is at: [$url].");
        return ($url);
    }

    //Search for an avatar
    $ns_sopml = $x->channel->children($namespaces['sopml']);
    if (isset($ns_sopml->avatar)) {
        $url = $ns_sopml->avatar;
        loggit(1, "The avatar of this feed is at: [$url].");
        return ($url);
    }

    //We can get avatars for twitter too
    if (stripos($x->channel->link, '/twitter.com') !== FALSE) {
        //Extract the username from the link element
        $twusername = substr($x->channel->link, stripos($x->channel->link, 'twitter.com/') + 12);
        $twprofile = get_twitter_profile($twusername);
        $url = $twprofile['profile_image_url'];
        //loggit(3, "The avatar of this twitter feed is at: [$url].");
        return ($url);
    }

    //None of the tests passed so return FALSE
    loggit(1, "Could not find an avatar for this feed.");
    return ("");
}


//Check if a feed is already in the newsfeed repository
function feed_exists($url = NULL)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The feed url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Clean the url
    $url = clean_url($url);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id FROM $table_newsfeed WHERE url=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close();
        loggit(2, "The feed at url: [$url] does not exist in the repository.");
        return (FALSE);
    }
    $sql->bind_result($feedid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    loggit(1, "The feed: [$feedid] at url: [$url] is already in the repository.");
    if(!empty($feedid)) {
        return ($feedid);
    } else {
        return FALSE;
    }
}


//Check if a given feed id is already in use in the feed table
function feed_id_exists($id = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The id is blank or corrupt: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the feed table
    $sql = $dbh->prepare("SELECT id FROM $table_newsfeed WHERE id=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The id: [$id] does not exist in the feed table.");
        return (FALSE);
    }
    $sql->bind_result($feedid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    loggit(1, "The feed: [$id] is already in use in the feed table.");
    return (TRUE);
}


//See how many items are in the database for a given feed
function get_item_count($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id FROM $table_nfitem WHERE feedid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    $itemcount = $sql->num_rows();
    $sql->close();

    loggit(1, "The feed: [$fid] has: [$itemcount] items.");
    return ($itemcount);
}


//Retrieve a static river from the rivers table for the given user
function get_river($uid = NULL, $mobile = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);
    $dbh->set_charset("utf8");

    //Look for the sid in the session table
    if ($mobile == TRUE) {
        $sql = $dbh->prepare("SELECT mriver FROM $table_river WHERE userid=?") or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql = $dbh->prepare("SELECT river FROM $table_river WHERE userid=?") or loggit(2, "MySql error: " . $dbh->error);
    }
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve static river for user: [$uid]");
        return (FALSE);
    }
    $sql->bind_result($sriver) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Unserialize it
    $river = unserialize($sriver);

    loggit(1, "Returning static river array for user: [$uid]");
    return ($river);
}


//Get the river formatted in JSONP
function get_river_as_json($uid = NULL, $mobile = FALSE, $pretty = FALSE)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    if($pretty) {
        return(format_json(json_encode(utf8ize(get_river($uid, $mobile)))));
    }
    return(json_encode(utf8ize(get_river($uid, $mobile))));
}


//Retrieve an array of info about the feed
function get_feed_info($id = NULL, $convert_content = TRUE)
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The feed id given is corrupt or blank: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "SELECT $table_newsfeed.url,
		            $table_newsfeed.title,
		            $table_newsfeed.content,
		            $table_newsfeed.lastcheck,
	                $table_newsfeed.lastupdate,
                    $table_newsfeed.lastmod,
                    $table_newsfeed.createdon,
                    $table_newsfeed.link,
                    $table_newsfeed.updated,
                    $table_newsfeed.lastitemid,
                    $table_newsfeed.oid,
		            $table_newsfeed.pubdate,
                    $table_newsfeed.errors,
                    $table_newsfeed.avatarurl,
		            $table_newsfeed.id,
		            $table_newsfeed.type,
		            $table_newsfeed.lasthttpstatus,
		            $table_newsfeed.lastgoodhttpstatus
           FROM $table_newsfeed
           WHERE $table_newsfeed.id=?";

    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve feed info for feed id: [$id]");
        return (FALSE);
    }
    $feed = array();
    $sql->bind_result($feed['url'], $feed['title'], $feed['content'], $feed['lastcheck'], $feed['lastupdate'],
        $feed['lastmod'], $feed['createdon'], $feed['link'], $feed['updated'], $feed['lastitemid'], $feed['oid'],
        $feed['pubdate'], $feed['errors'], $feed['avatarurl'], $feed['id'], $feed['type'], $feed['lasthttpstatus'],
        $feed['lastgoodhttpstatus']) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    $feed['subscribers'] = get_feed_subscribers($id);
    $feed['subscribercount'] = count($feed['subscribers']);

    //JSONfeed
    if ($feed['type'] == 1 && $convert_content) {
        loggit(3, "Converting JSONfeed to RSS for feed: [".$feed['url']."]");
        $feed['content'] = convert_jsonfeed_to_rss($feed['content']);
    }

    //loggit(1,"Returning feed info for feed: [$id]");
    return ($feed);
}


//Retrieve an array of info about the river
function get_river_info($uid = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "SELECT $table_river.id,
		            $table_river.userid,
		            $table_river.lastbuild,
		            $table_river.river,
	                $table_river.conthash,
                    $table_river.firstid,
                    $table_river.updated
            FROM $table_river
            WHERE $table_river.userid=?";

    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if we got a result
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "Failed to retrieve river info for user id: [$uid]");
        return (array());
    }
    $river = array();
    $sql->bind_result($river['id'], $river['userid'], $river['lastbuild'], $river['river'], $river['conthash'], $river['firstid'], $river['updated']) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //loggit(3,"Returning river info for user: [$uid]: ".print_r($river, TRUE));
    return ($river);
}


//Add a feed to the database
function add_feed($url = NULL, $uid = NULL, $get = FALSE, $oid = NULL, $type = 0)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The article url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Clean the url
    $url = clean_url($url);

    //Timestamp
    $createdon = time();

    //Each feed needs a unique id
    $id = random_gen(64);
    while (feed_id_exists($id)) {
        $id = random_gen(64);
    }

    //Does this feed exist already?
    $fid = feed_exists($url);
    if (!$fid) {
        $existed = FALSE;
        //Now that we have a good id, put the article into the database
        $stmt = "INSERT INTO $table_newsfeed (id,url,createdon, type) VALUES (?,?,?,?)";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("ssdd", $id, $url, $createdon, $type) or loggit(2, "MySql error: " . $dbh->error);
        $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
        $sql->close();
    } else {
        $existed = TRUE;
        unmark_feed_as_dead($fid);
        $id = $fid;
    }

    //Now link the feed id to this user's id if that was requested
    if ($uid != NULL) {
        link_feed_to_user($id, $uid);
    }

    //Link this feed to an outline if that was requested
    if ($oid != NULL) {
        link_feed_to_outline($id, $oid, $uid);
    }

    //Was feed item retrieval requested?
    if ($get == TRUE && $existed == FALSE) {
        get_feed_items($id, $default_new_subscription_item_count);
    }

    //Log and return
    if ($existed == TRUE) {
        loggit(1, "Feed: [$id] with url [$url] already existed in the database.");
    } else {
        loggit(1, "Put a new feed in the repository: [$id] with url [$url].");
    }
    return ($id);
}


//Change the title of a feed
function update_feed_title($fid = NULL, $title = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($title == NULL) {
        loggit(2, "The feed title is blank or corrupt: [$title]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Deal with blank feed titles
    if (empty($title)) {
        $title = "Untitled Feed";
    } else {
        $title = trim(htmlspecialchars($title));
    }

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET title=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $title, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed feed:[$fid]'s title to: [$title].");
   return (TRUE);
}


//Change the avatar url of a feed
function update_feed_avatar($fid = NULL, $url = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Deal with blank feed titles
    if (empty($url)) {
        $url = "";
    } else {
        $url = trim(htmlspecialchars(clean_url($url)));
    }

    //Put the avatar in
    $stmt = "UPDATE $table_newsfeed SET avatarurl=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $url, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed feed:[$fid]'s avatar url to: [$url].");
    return (TRUE);
}


//Initialize an empty set of stats for this feed
function init_feed_stats($fid = NULL)
{

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Make sure uid isn't empty
    if ($fid == NULL) {
        loggit(2, "The given feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "INSERT IGNORE INTO $table_feedstats (id) VALUES (?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Get the new stats record
    if ($updcount > 0) {
        $stats = get_feed_stats($fid);
    } else {
        $stats = FALSE;
    }

    //Log and return
    //loggit(3,"Initialized a default set of stats for feed: [$fid]");
    return ($stats);
}


//Change the stats for the feed
function set_feed_stats($fid = NULL, $stats = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($stats)) {
        loggit(2, "The stats array is blank or corrupt: [$stats]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "INSERT INTO $table_feedstats
                      (id,
           	       checkcount,
                       checktime,
		       avgchecktime,
                       avgnewitems,
		       newitems,
                       avgnewinterval,
		       lastnewtime,
                       subscribers)
		  VALUES (?,?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE
           	       checkcount=?,
                       checktime=?,
		       avgchecktime=?,
                       avgnewitems=?,
		       newitems=?,
                       avgnewinterval=?,
		       lastnewtime=?,
                       subscribers=?
  ";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sddddddsdddddddsd", $fid,
        $stats['checkcount'],
        $stats['checktime'],
        $stats['avgchecktime'],
        $stats['avgnewitems'],
        $stats['newitems'],
        $stats['avgnewinterval'],
        $stats['lastnewtime'],
        $stats['subscribers'],
        $stats['checkcount'],
        $stats['checktime'],
        $stats['avgchecktime'],
        $stats['avgnewitems'],
        $stats['newitems'],
        $stats['avgnewinterval'],
        $stats['lastnewtime'],
        $stats['subscribers']
    ) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Set stats for feed: [$fid].");
    return (TRUE);
}


//Return an array of all the stats for this feed
function get_feed_stats($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "Feed id given is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli_Extended($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $sql = $dbh->prepare("SELECT * FROM $table_feedstats WHERE id=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() != 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "This feed has no stats: [$fid]");
        return (init_feed_stats($fid));
    }
    $stats = $sql->fetch_assoc();
    $sql->close();

    loggit(1, "Returning stats array for feed: [$fid]");
    return ($stats);
}


//Change the reported pub date of a feed
function update_feed_pubdate($fid = NULL, $pubdate = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($pubdate == NULL) {
        loggit(2, "The pubdate is blank or corrupt: [$pubdate]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET pubdate=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $pubdate, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed feed:[$fid]'s pubdate to: [$pubdate].");
    return (TRUE);
}


//Change the lastupdate column of a feed
function update_feed_lastupdate($fid = NULL, $time = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($time == NULL) {
        loggit(2, "The time is blank or corrupt: [$time]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET lastupdate=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ds", $time, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed feed:[$fid]'s lastupdate time to: [$time].");
    return (TRUE);
}


//Increment the error count on this feed
function increment_feed_error_count($fid = NULL, $amount = 1)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($amount < 1) {
        loggit(2, "You can't increment the feed error count by a non-positive number: [$amount]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET errors=errors+? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ds", $amount, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Incremented error count for feed:[$fid].");
    return (TRUE);
}


//Set the feed error count on this feed to a given value
function set_feed_error_count($fid = NULL, $count = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($count == NULL) {
        loggit(2, "The error count is blank or corrupt: [$count]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET errors=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ds", $count, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Set error count for feed:[$fid] to: [$count].");
    return (TRUE);
}


//Reset feed error count to zero
function reset_feed_error_count($fid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $stmt = "UPDATE $table_newsfeed SET errors=0 WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Reset error count for feed:[$fid] to zero.");
    return (TRUE);
}


//Change the lastcheck column of a feed
function update_feed_lastcheck($fid = NULL, $time = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($time == NULL) {
        loggit(2, "The time is blank or corrupt: [$time]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET lastcheck=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ds", $time, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed feed:[$fid]'s lastcheck time to: [$time].");
    return (TRUE);
}


//Flip the flag on items marked purge to mark as old instead
function flip_purge_to_old($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitem SET `old`=1 WHERE feedid=? AND `purge`=1";
    $sql = $dbh->prepare($stmt) or loggit(2, $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flipped: [$updcount] items in feed:[$fid] from purge to old.");
    return ($updcount);
}


//Flip flag on a river to signify it needs rebuilding
function mark_river_as_updated($uid = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_river SET updated=1 WHERE userid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged river for user: [$uid] as needing to be rebuilt.");
    return ($updcount);
}


//Flip the flag on a newsfeed to make it known that it needs updating
function mark_feed_as_updated($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET updated=1 WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as needing to be scanned.");
    return ($updcount);
}


//Flip the flag on a newsfeed to make it known that it does NOT need updating
function unmark_feed_as_updated($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET updated=0 WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);

    $sql->execute();// or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    //loggit(3,"Cleared update flag on feed: [$fid].");
    return ($updcount);
}


//Flip the flag on a newsfeed to make it known that it is dead and should never be checked
function mark_feed_as_dead($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $stmt = "UPDATE $table_newsfeed SET dead=1 WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as dead.");
    return ($updcount);
}


//Flip the flag on a newsfeed to make it known that it is not dead and should be checked
function unmark_feed_as_dead($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $stmt = "UPDATE $table_newsfeed SET dead=0 WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);

    $sql->execute();// or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    //loggit(3,"Cleared update flag on feed: [$fid].");
    return ($updcount);
}


//Mark a feed's items as purge
function mark_all_feed_items_to_purge($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitem SET `purge`=1 WHERE feedid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Marked: [$updcount] items in feed:[$fid] to purge.");
    return ($updcount);
}


//Unmark a feed's items as purge
function unmark_all_feed_items_to_purge($fid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Update all the items
    $stmt = "UPDATE $table_nfitem SET `purge`=0 WHERE feedid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Un-marked: [$updcount] items in feed:[$fid] to purge.");
    return ($updcount);
}


//Mark a feed item as purge
function mark_feed_item_to_purge($fid = NULL, $guid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The item feed it is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($guid == NULL) {
        loggit(2, "The item guid is blank or corrupt: [$guid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitem SET `purge`=1 WHERE feedid=? AND guid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $guid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    if ($updcount < 1) {
        //loggit(2,"Failed to mark feed item: [$guid] to purge for feed: [$fid].");
        return (FALSE);
    } else {
        //loggit(1,"Marked feed item: [$guid] to purge for feed: [$fid].");
        return (TRUE);
    }
}


//Unmark a feed item as purge
function unmark_feed_item_to_purge($fid = NULL, $guid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The item feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($guid == NULL) {
        loggit(2, "The item guid is blank or corrupt: [$guid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitem SET `purge`=0 WHERE feedid=? AND guid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $guid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    if ($updcount < 1) {
        //loggit(2,"Failed to unmark feed item: [$guid] to purge for feed: [$fid].");
        return (FALSE);
    } else {
        loggit(1, "Unmarked feed item: [$guid] to purge for feed: [$fid].");
        return (TRUE);
    }
}


//Change the link of a feed
function update_feed_link($fid = NULL, $link = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($link == NULL) {
        loggit(2, "The feed link is blank or corrupt: [$link] for feed: [$fid].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET link=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $link, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed feed:[$fid]'s link to: [$link].");
    return (TRUE);
}


//Change the content of a feed
function update_feed_content($fid = NULL, $content = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($content == NULL) {
        loggit(2, "The feed content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET content=?, contenthash=SHA1(?) WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $content, $content, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Updated content for feed: [$fid].");
    return (TRUE);
}


//Change the url of a feed
function update_feed_url($fid = NULL, $url = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($url)) {
        loggit(2, "The feed url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET url=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $url, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Updated url for feed: [$url].");
    return (TRUE);
}


//Change the last modified time of a feed
function update_feed_lastmod($fid = NULL, $lastmod = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($lastmod == NULL) {
        loggit(2, "The feed last-modified time is blank or corrupt: [$lastmod]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_newsfeed SET lastmod=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ds", $lastmod, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Updated last-modified time to: [$lastmod] for feed: [$fid].");
    return (TRUE);
}


//Remove the specified number of items from a feed
function trim_feed($fid = NULL, $max = NULL)
{
    //Check params
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($max == NULL) {
        loggit(2, "The max count is blank or corrupt: [$aid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_nfitem WHERE feedid=? ORDER BY timeadded DESC LIMIT ?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $max) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    loggit(1, "Deleted: [$delcount] items from feed: [$fid].");
    return (TRUE);
}


//Remove feed items over a certain alloted amount based on date
function trim_feed_items($fid = NULL, $max = NULL)
{
    //Check params
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($max == NULL) {
        loggit(2, "The max item count is blank or corrupt: [$max]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_nfitem WHERE id NOT IN (SELECT id FROM (SELECT id FROM $table_nfitem ORDER BY timestamp DESC LIMIT ?) x) AND feedid=?;";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $max, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    loggit(1, "Deleted: [$delcount] items from feed: [$fid].");
    return (TRUE);
}


//Purge items from the items table that have the purge flag set
function purge_feed_items($fid = NULL)
{
    //Check params
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_nfitem WHERE feedid=? AND `purge`=1;";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    loggit(1, "Deleted: [$delcount] items from feed: [$fid].");
    return ($delcount);
}


//Delete old feed items from the items table that have the old flag
function delete_old_feed_items($fid = NULL)
{
    //Check params
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    $thirtydaysago = (time() - ($max_newsfeed_item_age * 86400));

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_nfitem WHERE feedid=? AND `old`=1 AND timeadded < ?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $thirtydaysago) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    if ($delcount > 0) {
        loggit(1, "Deleted: [$delcount] old items from feed: [$fid].");
    }
    return ($delcount);
}


//Delete all the items for a particular feed
function delete_feed_items($fid = NULL)
{
    //Check params
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE nfi.*,nfp.* FROM $table_nfitem nfi INNER JOIN $table_nfitemprop nfp ON nfi.id = nfp.itemid WHERE nfi.feedid=? ";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    if ($delcount > 0) {
        loggit(1, "Deleted: [$delcount] items for orphaned feed: [$fid].");
    }
    return ($delcount);
}


//Remove the sticky flags for all of a user's feed items
function unmark_all_feed_items_as_sticky($uid = NULL)
{
    //Check params
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM nfitemprops WHERE userid=? AND sticky=1";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    if ($delcount > 0) {
        loggit(1, "Unmarked: [$delcount] items as sticky for user: [$uid].");
    }
    return ($delcount);
}


//Delete a feed
function delete_feed($fid = NULL)
{
    //Check params
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_newsfeed WHERE id=? ";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    if ($delcount < 1) {
        loggit(2, "Failed to delete feed: [$fid] from feed table.");
        return (FALSE);
    }

    //Log and leave
    loggit(1, "Deleted feed: [$fid] from feed table.");
    return (TRUE);
}


//Fetch the newest content for a feed
function fetch_feed_content($fid = NULL, $force = TRUE)
{
    //Check params
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the content of the feed
    $feed = get_feed_info($fid);
    $url = $feed['url'];

    //Check for bad feed url
    if (empty($url)) {
        loggit(2, "Feed: [$fid] has a blank url: [$url].");
        return (FALSE);
    }

    //Let's do some intelligent header checking so we don't waste time and bandwidth
    update_feed_lastcheck($fid, time());
    $lastmodtime = check_head_lastmod($url);
    //loggit(3, "DEBUG: Last-modified: [$lastmodtime]");
    if (($lastmodtime == $feed['lastmod']) && !empty($lastmodtime) && $force == FALSE) {
        loggit(1, "Feed: [($url) $fid] hasn't been updated. Skipping.");
        return (FALSE);
    }

    //Feed has been changed so grab the new content
    if (!empty($lastmodtime)) {
        update_feed_lastmod($fid, $lastmodtime);
    }
    $goodurl = get_final_url($url);
    if ($goodurl != $url && stripos($goodurl, 'http') === 0) {
        loggit(3, "Changing feed url: [$url] to: [$goodurl].");
        update_feed_url($fid, $goodurl);
    }
    $tstart = time();
    $content = fetchFeedUrl($goodurl, get_feed_subscriber_count($fid), $cg_sys_version);
    //loggit(3, "FEED SCAN: GET request took [".(time() - $tstart)."] seconds.");
    if( empty($content) ) {
        loggit(2, "Error attempting to get url: [$url]. See log for details.");
        increment_feed_error_count($fid);
        return (FALSE);
    }
    update_feed_content($fid, $content);
    $consize = strlen($content);

    //Flag feed as updated
    mark_feed_as_updated($fid);

    //Log and leave
    loggit(1, "Fetched: [$consize] new bytes of content for feed: [$goodurl].");
    return ($consize);
}


//Get and parse out the content of an RSS feed
function get_feed_items($fid = NULL, $max = NULL, $force = FALSE)
{
    //Check params
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($max)) {
        //loggit(2,"No max value declared, so set it high: [9,999].");
        $max = 9999;
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Un-flag this feed
    unmark_feed_as_updated($fid);

    //Get the content of the feed
    $feed = get_feed_info($fid);
    $stats = get_feed_stats($fid);
    $stats['subscribers'] = get_feed_subscriber_count($fid);
    $stats['checkcount']++;
    $fstart = time();
    $url = $feed['url'];

    loggit(1, "FEEDSCAN: Scanning feed: [$url].");

    //Pull the latest content blob from the database
    if( empty($feed['content']) && !empty($feed['lastcheck']) ) {
        loggit(2, "  Feed: [$url] has no content: [".$feed['content']."]");
        //Set the last check time in the stats
        $stats['checktime'] += (time() - $fstart);
        set_feed_stats($fid, $stats);
        //Increment the error count
        increment_feed_error_count($fid);
        return (-1);
    }

    //Fix up content issues before run
    loggit(1, "Fixing up common structural problems in feed: [$url]");
    $feed['content'] = trim($feed['content']);
    $invalid_characters = '/[^\x9\xa\x20-\xD7FF\xE000-\xFFFD]/';
    $feed['content'] = preg_replace($invalid_characters, '', $feed['content'] );

    //Remove content before the <?xml declaration
    $elPos = stripos(substr($feed['content'], 0, 255), '<?xml');
    if($elPos > 0) {
        loggit(2, "  Removing stuff before the <?xml header in: [$url]");
        $feed['content'] = substr($feed['content'], $elPos);
    }

    //Remove stuff after the closing xml root tags
    $elPos = stripos($feed['content'], '</rss>');
    if($elPos !== FALSE) {
        $feed['content'] = substr($feed['content'], 0, $elPos + 6);
    }
    $elPos = stripos($feed['content'], '</feed>');
    if($elPos !== FALSE) {
        $feed['content'] = substr($feed['content'], 0, $elPos + 7);
    }

    //Fix feeds that don't have a correct <?xml prefix at the beginning
    $tstart = time();
    if(stripos(substr($feed['content'], 0, 255), '<?xml') === FALSE) {
        loggit(2, "  Missing <?xml> header in feed: [$url]. Let's dig deeper...");

        $elPos = stripos(substr($feed['content'], 0, 255), '<rss');
        if($elPos !== FALSE) {
            loggit(1, "    Fixing missing <?xml> header in RSS feed: [$url]");
            $feed['content'] = "<?xml version=\"1.0\"?>\n".substr($feed['content'], $elPos);
        }
        $elPos = stripos(substr($feed['content'], 0, 255), '<feed');
        if($elPos !== FALSE) {
            loggit(1, "    Fixing missing <?xml> header in RSS feed: [$url]");
            $feed['content'] = "<?xml version=\"1.0\"?>\n".substr($feed['content'], $elPos);
        }
    }
    if((time() - $tstart) > 5) {
        loggit(1, "Fixed feed: [$url] in [".(time() - $tstart)."] seconds.");
    }

    //Debug
    //loggit(3, "DEBUG: [".$feed['content']."]");

    //Only get the first few entries if this is a new feed
    if (empty($feed['lastcheck'])) {
        $max = $default_new_subscription_item_count;
    }

    //Set the last check time in the stats
    $stats['checktime'] += (time() - $fstart);
    set_feed_stats($fid, $stats);

    //Is the feed any good?
    if (!feed_is_valid($feed['content']) && !is_feed($feed['content'])) {
        //Fix stray ampersands in the xml
        if( stripos($feed['content'], '& ') !== FALSE ) {
            $feed['content'] = preg_replace('/\&\s/m', '&amp; ', $feed['content']);
            loggit(2, "    Fixing poorly encoded entities (&,<,>) in XML feed: [$url]");
            if(!feed_is_valid($feed['content']) && !is_feed($feed['content'])) {
                //Try to correct the encoding
                if( stripos($feed['content'], 'encoding="utf-8"') && mb_detect_encoding($feed['content'], 'UTF-8', true) === FALSE ) {
                    $feed['content'] = utf8_encode($feed['content']);
                    if(!feed_is_valid($feed['content']) && !is_feed($feed['content'])) {
                        loggit(2, "  Feed: [" . $url . "] doesn't seem to be a known feed format. Skipping it. (1)");
                        increment_feed_error_count($fid, 5);
                        return (-1);
                    }
                } else {
                    loggit(2, "  Feed: [" . $url . "] doesn't seem to be a known feed format. Skipping it. (2)");
                    increment_feed_error_count($fid, 5);
                    return (-1);
                }
            }
        }
    }

    //Parse it
    $tstart = time();
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($feed['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
    //loggit(3, "FEED SCAN: SimpleXML parse load took [".(time() - $tstart)."] seconds.");

    //Was there a fatal error during parsing?
    if (!$x) {
        loggit(2, "  Error parsing feed: [$url] Error: [".libxml_get_last_error()->message."]");
        loggit(1, "  Failed to parse XML for feed: [$url].  Let's run it through Tidy() and try again.");
        libxml_clear_errors();
        $tidy = new tidy();
        $xr = $tidy->repairString($feed['content'], array('output-xml' => true, 'input-xml' => true));
        libxml_use_internal_errors(true);
        $x = simplexml_load_string($xr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$x) {
            loggit(2, "  Error parsing feed XML for feed: [$url].  Error: [".libxml_get_last_error()->message."]");
            libxml_clear_errors();
            increment_feed_error_count($fid, 5);
            return (-1);
        }
        libxml_clear_errors();
    }
    libxml_clear_errors();

    //Pass any namespaces to the item add routine
    $namespaces = $x->getDocNamespaces(TRUE);

    //Look for some kind of publish date
    $pubdate = time();
    $pubdatesource = "fallback to time()";
    if (isset($x->channel->pubDate) && !empty((string)$x->channel->pubDate)) {
        //Channel date for rss2 feed
        $pubdate = (string)$x->channel->pubDate;
        $pubdatesource = "RSS2.0 Channel <pubDate> value";
    } else
    if (isset($x->channel->lastBuildDate) && !empty($x->channel->lastBuildDate)) {
        //Channel build date for rss2 feed
        $pubdate = (string)$x->channel->lastBuildDate;
        $pubdatesource = "RSS2.0 Channel <lastBuildDate> value";
    } else
    if (isset($namespaces['dc']) && isset($x->children($namespaces['dc'])->date) && !empty($x->children($namespaces['dc'])->date)) {
        //Channel date for RDF(rss1) feed
        $pubdate = (string)$x->children($namespaces['dc'])->date;
        $pubdatesource = "RSS1/RDF feed level <dc:date> value";
    } else
    if (isset($x->updated) && !empty($x->updated)) {
        //Channel date for atom feed
        $pubdate = (string)$x->updated;
        $pubdatesource = "ATOM feed level <updated> value";
    } else {
        //We didn't get a feed level date, so dig into the items for dates
        if(isset($x->channel->item[0]) && isset($x->channel->item[0]->pubDate) && !empty($x->channel->item[0]->pubDate)) {
            //First item in rss2 feed
            $pubdate = (string)$x->channel->item[0]->pubDate;
            $pubdatesource = "RSS2.0 first item <pubDate> value";
        } else
        if(isset($x->item[0]) && isset($namespaces['dc']) && isset($x->item[0]->children($namespaces['dc'])->date) && !empty($x->item[0]->children($namespaces['dc'])->date)) {
            //First item in RDF(rss1) feed
            $pubdate = (string)$x->item[0]->children($namespaces['dc'])->date;
            $pubdatesource = "RSS1/RDF first item <dc:date> value";
        } else
        if(isset($x->item[0]) && isset($x->item[0]->date) && !empty($x->item[0]->date)) {
            //First item in RDF(rss1) feeds that dont follow spec
            $pubdate = (string)$x->item[0]->date;
            $pubdatesource = "RSS1/RDF first item <date> value";
        } else
        if(isset($x->entry[0]) && isset($x->entry[0]->updated) && !empty($x->entry[0]->updated)) {
            //First item in atom feed
            $pubdate = (string)$x->entry[0]->updated;
            $pubdatesource = "ATOM first item <updated> value";
        }
    }
    $pubdate = trim($pubdate);
    //loggit(3, "DEBUG: Pubdate: [$pubdate]");
    if (trim($feed['pubdate']) == $pubdate && !empty($pubdate) && $force == FALSE) {
        //The feed says that it hasn't been updated
        //loggit(3, "  PUBDATE: [$pubdate | ".$feed['pubdate']."] Source: [$pubdatesource] not changed.");
        reset_feed_error_count($fid);
        unmark_feed_as_dead($fid);
        return (-3);
    }
    loggit(1, "  PUBDATE: [$pubdate | ".$feed['pubdate']."] Source: [$pubdatesource] changed.");
    update_feed_pubdate($fid, $pubdate);

    //Freshen feed title
    $ftitle = parse_url($url, PHP_URL_HOST);
    if (isset($x->channel) && isset($x->channel->title)) {
        $ftitle = $x->channel->title;
    }
    if (isset($x->title)) {
        $ftitle = $x->title;
    }
    if( $ftitle != $feed['title'] ) {
        update_feed_title($fid, trim($ftitle));
    }

    //Freshen feed link
    $flink = parse_url($url, PHP_URL_HOST);
    if (isset($x->channel) && isset($x->channel->link)) {
        $flink = $x->channel->link;
    }
    if (isset($x->link) && isset($x->link->attributes()->href)) {
        $flink = (string)$x->link->attributes()->href;
    }
    if( $flink != $feed['link'] ) {
        update_feed_link($fid, trim($flink));
    }

    //Freshen feed avatar
    $avatarurl = get_feed_avatar($x);
    if($avatarurl != $feed['avatarurl']) {
        update_feed_avatar($fid, trim($avatarurl));
    }

    //Mark all of this feed's items as purge
    mark_all_feed_items_to_purge($fid);

    //Get all of the feed item guids
    $guids = get_feed_item_guids_by_feed_id($fid);

    //Put all of the items in an array
    $tstart = time();
    $items = array();
    $count = 0;
    $newcount = 0;
    if (strtolower($x->getName()) == "rdf") {
        //Probably an RDF feed
        loggit(1, "RDF Feed: ".$ftitle);
        foreach ($x->item as $entry) {
            $items[$count] = $entry;
            $guid = get_unique_id_for_rdf_feed_item($entry, $namespaces);
            if (array_search($guid, $guids) === FALSE) {
                update_feed_lastupdate($fid, time());
                add_feed_item($fid, $entry, "rss", $namespaces);
                $newcount++;
                if ($newcount > $max) {
                    mark_feed_item_to_purge($fid, $entry->guid);
                }
            } else {
                unmark_feed_item_to_purge($fid, $entry->guid);
            }
            $count++;
        }
    } else
    if (empty($x->channel->item)) {
        //This is an atom feed
        foreach ($x->entry as $entry) {
            $items[$count] = $entry;
            $guid = $entry->id;
            if (array_search($guid, $guids) === FALSE) { //testing
                update_feed_lastupdate($fid, time());
                add_feed_item($fid, $entry, "atom", $namespaces);
                $newcount++;
                if ($newcount > $max) {
                    mark_feed_item_to_purge($fid, $entry->id);
                }
            } else {
                unmark_feed_item_to_purge($fid, $entry->id);
            }
            $count++;
        }
    } else {
        //This is an rss feed
        foreach ($x->channel->item as $entry) {
            $items[$count] = $entry;
            $guid = get_unique_id_for_feed_item($entry);
            if (array_search($guid, $guids) === FALSE) {
                update_feed_lastupdate($fid, time());
                add_feed_item($fid, $entry, "rss", $namespaces);
                $newcount++;
                if ($newcount > $max) {
                    mark_feed_item_to_purge($fid, $entry->guid);
                }
            } else {
                unmark_feed_item_to_purge($fid, $entry->guid);
            }
            $count++;
        }
    }
    loggit(1, "  Feed item storage took [".(time() - $tstart)."] seconds.");

    //Flip the purge flags to old
    flip_purge_to_old($fid);

    //Delete old items
    delete_old_feed_items($fid);

    //Calculate new stats for this feed
    $stats['checktime'] += (time() - $fstart);
    $stats['avgchecktime'] = ($stats['checktime'] / $stats['checkcount']);
    $stats['newitems'] += $newcount;
    $stats['avgnewitems'] = ($stats['newitems'] / $stats['checkcount']);
    $stats['avgnewinterval'] = ($stats['checkcount'] / $stats['newitems']);
    $stats['lastnewtime'] = $fstart;
    set_feed_stats($fid, $stats);

    //Evidently the scan was successful so reset the error counter
    reset_feed_error_count($fid);
    unmark_feed_as_dead($fid);

    //Is the feed empty?
    if ($count == 0) {
        loggit(1, "  DONE: There were no items in this feed: [$url].");
        //increment_feed_error_count($fid, 1);
        return (-2);
    }

    //Log and leave
    loggit(3, "  DONE: [$newcount] out of: [$count] items from feed: [$url] were new.");
    return ($newcount);
}


//Retrieve a list of all the feeds in the database
function get_all_feeds($max = NULL, $witherrors = FALSE, $withold = FALSE, $withdead = FALSE)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get a month ago timestamp
    $monthago = (time() - (28 * 86400));

    //Get all feeds with a low error count
    $sqltxt = "SELECT id,title,url,createdon FROM $table_newsfeed";

    //Include high error feeds?
    if ($witherrors == FALSE) {
        $sqltxt .= " WHERE errors < 10";
    } else {
        $sqltxt .= " WHERE 1=1 ";
    }

    //Include old feeds?
    if ($withold == FALSE) {
        $sqltxt .= " AND (lastupdate > $monthago OR lastcheck = 0)";
    }

    //Include dead feeds?
    if ($withdead == FALSE) {
        $sqltxt .= " AND dead=0";
    }

    //Sort by last check time
    $sqltxt .= " ORDER BY $table_newsfeed.lastcheck ASC";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no feeds in the system.");
        return (array());
    }

    $sql->bind_result($fid, $ftitle, $furl, $fcreatedon) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array(
            'id' => $fid,
            'title' => $ftitle,
            'url' => $furl,
            'createdon' => $fcreatedon
        );
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] feeds in the system.");
    return ($feeds);
}


//Retrieve a list of all the feed items in the database
function get_all_feed_item_ids($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get all feed items up to max
    $sqltxt = "SELECT id FROM $table_nfitem";

    //Work newest to oldest
    $sqltxt .= " ORDER BY id";

    //Max given?
    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //Run the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feed items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no feed items in the system.");
        return (array());
    }

    $sql->bind_result($iid) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[] = $iid;
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] feed items in the system.");
    return ($items);
}


//Retrieve details of a certain feed item
function get_feed_item($id = NULL)
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The id is blank or corrupt: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get all feed items up to max
    $sqltxt = "SELECT id,title,url,description FROM $table_nfitem WHERE id=?";

    //Run the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feed items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "No feed item found.");
        return (array());
    }

    $sql->bind_result($iid, $ititle, $iurl, $idescription) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[$count] = array('id' => $iid, 'title' => $ititle, 'url' => $iurl, 'description' => $idescription);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning feed item: [$id].");
    return ($items[0]);
}


//Retrieve details for a list of items
//  !!!!! new function incomplete !!!!!! //
function get_nfitems($ids = NULL)
{
    //Check parameters
    if (empty($ids) || !is_array($ids)) {
        loggit(2, "The id is blank or corrupt: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get all feed items up to max
    $sqltxt = "SELECT id,title,url,description FROM $table_nfitem WHERE id=?";

    //Run the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feed items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "No feed item found.");
        return (array());
    }

    $sql->bind_result($iid, $ititle, $iurl, $idescription) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[$count] = array('id' => $iid, 'title' => $ititle, 'url' => $iurl, 'description' => $idescription);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning feed item: [$id].");
    return ($items[0]);
}


//Retrieve a list of all the feed items in the database
function get_feed_item_guids_by_feed_id($fid = NULL, $max = 0)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get all feed items up to max
    $sqltxt = "SELECT guid FROM $table_nfitem WHERE feedid=?";

    //Max given?
    if ( $max > 0 && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //Run the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feed items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no feed items stored for feed: [$fid].");
        return (array());
    }

    $sql->bind_result($guid) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[] = $guid;
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] feed items for feed: [$fid].");
    return ($items);
}


//Retrieve a list of all the feed items in the database
function get_all_feed_items($max = NULL, $collapsebyurl = TRUE)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get all feed items up to max
    $sqltxt = "SELECT id,title,url,description FROM $table_nfitem";

    //Collapse duplicate urls to a single item?
    if( $collapsebyurl ) {
        $sqltxt .= " GROUP BY url";
    }

    //Work newest to oldest
    $sqltxt .= " ORDER BY timeadded DESC";

    //Max given?
    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //Run the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feed items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no feed items in the system.");
        return (array());
    }

    $sql->bind_result($iid, $ititle, $iurl, $idescription) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[$count] = array('id' => $iid, 'title' => $ititle, 'url' => $iurl, 'description' => $idescription);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] feed items in the system.");
    return ($items);
}


//Retrieve a list of all the feeds with error counts over the threshold
function get_error_feeds($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get feeds with a high error count
    $sqltxt = "SELECT id,title,url,createdon FROM $table_newsfeed WHERE errors > 10";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no high error feeds in the system.");
        return (FALSE);
    }

    $sql->bind_result($fid, $ftitle, $furl, $fcreatedon) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array('id' => $fid, 'title' => $ftitle, 'url' => $furl, 'createdon' => $fcreatedon);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] high error feeds in the system.");
    return ($feeds);
}


//Retrieve a list of all the feeds with lastupdate times older than a month
function get_old_feeds($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get a month ago timestamp
    $monthago = (time() - (28 * 86400));

    //Get feeds with an old update stamp
    $sqltxt = "SELECT id,title,url,createdon FROM $table_newsfeed WHERE lastupdate < $monthago AND errors < 10";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this criteria
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no old feeds in the system.");
        return (FALSE);
    }

    $sql->bind_result($fid, $ftitle, $furl, $fcreatedon) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array('id' => $fid, 'title' => $ftitle, 'url' => $furl, 'createdon' => $fcreatedon);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] old feeds in the system.");
    return ($feeds);
}


//Retrieve a list of all the feeds in the database with their stats
function get_all_feeds_with_stats($max = 50)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli_Extended($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the
    $sqltxt = "SELECT $table_newsfeed.id,
                    $table_newsfeed.url,
                    $table_newsfeed.title,
                    $table_newsfeed.lastcheck,
                    $table_newsfeed.lastupdate,
                    $table_newsfeed.createdon,
                    $table_newsfeed.errors,
                    $table_feedstats.avgnewinterval,
                    $table_feedstats.avgchecktime,
		    $table_feedstats.avgnewitems,
		    $table_feedstats.subscribers
             FROM $table_newsfeed
	     LEFT JOIN $table_feedstats ON $table_newsfeed.id = $table_feedstats.id
             ORDER BY $table_feedstats.subscribers DESC";


    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "There are no feeds in the system.");
        return (FALSE);
    }

    $sql->bind_result($fid,
        $furl,
        $ftitle,
        $flastcheck,
        $flastupdate,
        $fcreatedon,
        $ferrors,
        $savgnewinterval,
        $savgchecktime,
        $savgnewitems,
        $ssubscribers
    ) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array(
            'id' => $fid,
            'url' => $furl,
            'title' => $ftitle,
            'lastcheck' => $flastcheck,
            'lastupdate' => $flastupdate,
            'createdon' => $fcreatedon,
            'errors' => $ferrors,
            'avgnewinterval' => $savgnewinterval,
            'avgchecktime' => $savgchecktime,
            'avgnewitems' => $savgnewitems,
            'subscribers' => $ssubscribers
        );
        $count++;
    }

    $sql->close();

    //loggit(1,"Returning: [$count] feeds in the system.");
    return ($feeds);
}


//Retrieve a list of all the feeds flagged as needing to be re-scanned
function get_updated_feeds($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the
    $sqltxt = "SELECT id,title,url,createdon FROM $table_newsfeed WHERE updated=1 ORDER BY lastcheck ASC";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        //loggit(2,"There are no feeds that need scanning.");
        return (array());
    }

    $sql->bind_result($fid, $ftitle, $furl, $fcreatedon) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array('id' => $fid, 'title' => $ftitle, 'url' => $furl, 'createdon' => $fcreatedon);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] updated feeds that need scanning.");
    return ($feeds);
}


//Add a feed item to the item storage table
function add_feed_item($fid = NULL, $item = NULL, $format = NULL, $namespaces = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($item == NULL) {
        loggit(2, "The item is blank or corrupt: [$item]");
        return (FALSE);
    }

    //Debug
    //loggit(3, print_r($item, true));

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Timestamp
    $timeadded = time();
    $tstart = time();

    //Contains media?
    $media = 0;

    //Get feed url
    $fd = get_feed_info($fid);
    $fdurl = $fd['url'];

    //Each item needs a unique id
    //$id = random_gen(128);
    $old = FALSE;

    //Find a publish date for the item
    if ($format == "atom") {
        $pubdate = strtotime($item->updated);
    } else {
        $pubdate = strtotime($item->pubDate);
    }

    //Deal with old items that come back into the feed.  Yes, some feeds do this.
    if (!empty($pubdate)) {
        //If this feed item reports a pubdate of greater than 7 days ago, then set
        //timeadded to same value as pubdate.  This keeps old items from re-appearing as new.
        if ($pubdate < (time() - 604800)) {
            loggit(1, "OLD ITEM: Setting timeadded: [$timeadded] to pubdate: [$pubdate].");
            $timeadded = $pubdate;
            $old = TRUE;
        }
    }

    //Now that we have a good id, put the feed item into the database
    $stmt = "INSERT INTO $table_nfitem (feedid,title,url,description,guid,timestamp,timeadded,enclosure,`purge`,sourceurl,sourcetitle,author,origin,media) VALUES (?,?,?,?,?,?,?,?,0,?,?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    if ($format == "atom") {
        //-----ATOM--------------------------------------------------------------------------------------------------------------------------------------------------
        $mcount = count($item->link);

        $title = clean_feed_item_content((string)$item->title, 0, FALSE, FALSE);
        $description = $item->summary;
        if (isset($item->content)) {
            $description = (string)$item->content;
        }

        //Find links and enclosures
        $linkurl = "#";
        $enclosures = array();
        for ($lcount = 0; $lcount < $mcount; $lcount++) {
            //Alternate is the main link
            if ($item->link[$lcount]['rel'] == "alternate" || !isset($item->link->attributes()->rel)) {
                if(isset($item->link[$lcount]) && !isset($item->link[$lcount]['href'])) {
                    $linkurl = (string)$item->link[$lcount][0];
                    if(stripos($linkurl, 'http') !== 0) {
                        $linkurl = "#";
                    }
                } else {
                    $linkurl = $item->link[$lcount]['href'];
                }
            }
            //loggit(3, "ATOM LINK: $linkurl");

            //Some feeds only use id for the link, so if no valid linkurl exists yet, try the id element
            if ( stripos($linkurl, 'http:') === FALSE && stripos($linkurl, 'https:') === FALSE ) {
                if ( isset($item->id) && ( stripos($item->id, 'http:') !== FALSE || stripos($item->id, 'https:') !== FALSE) ) {
                    $linkurl = trim($item->id);
                }
            }

            //Enclosures are links also
            if ($item->link[$lcount]['rel'] == "enclosure") {
                $esize = "";
                $esrc = "";
                $etype = "";

                //Some stupid ass feeds use src instead of href. HuffPo!
                if (isset($item->link[$lcount]->attributes()->src)) {
                    $esrc = (string)$item->link[$lcount]->attributes()->src;
                }
                if (isset($item->link[$lcount]->attributes()->href)) {
                    $esrc = (string)$item->link[$lcount]->attributes()->href;
                }
                if (isset($item->link[$lcount]->attributes()->type)) {
                    $etype = (string)$item->link[$lcount]->attributes()->type;
                }

                //If we couldn't get a decent url don't go any further
                if (!empty($esrc) && strpos($esrc, 'http') !== FALSE) {
                    //If a length is given then use it
                    if (isset($item->link[$lcount]->attributes()->length)) {
                        $esize = (string)$item->link[$lcount]->attributes()->length;
                        //Otherwise, do a head size check over http
                    } else {
                        $esize = check_head_size($esrc);
                    }
                    //If it's not duplicate, add it
                    if (!in_array_r($esrc, $enclosures) && !empty($esrc)) {
                        $enclosures[] = array('url' => $esrc,
                            'length' => 0 + $esize,
                            'type' => make_mime_type($esrc, $etype)
                        );
                        $media = 1;
                    }
                }
            }
        }

        //Fix up twitter links
        if (strpos($linkurl, 'twitter.com') !== FALSE) {
            //Search for an embedded link in the description
            $title = $item->title;
            if (stripos($title, 'http:') !== FALSE || stripos($title, 'https:') !== FALSE) {
                preg_match('/\bhttp\:\/\/([A-Za-z0-9\.\/\+\&\@\~\-\%\?\=\_\#\!]*)/i', $title, $twurl);
                $linkurl = $twurl[0];
            }
        }

        //Clean the incoming description
        $cleaned = clean_feed_item_content($description, 0, TRUE, TRUE);
        $description = $cleaned['text'];

        //Attach extracted media tags as enclosures with correct type
        if (!empty($cleaned['media'])) {
            foreach ($cleaned['media'] as $mediatag) {
                $esize = "";
                if ($mediatag['type'] == 'image' || $mediatag['type'] == 'audio' || $mediatag['type'] == 'video') {
                    $esize = check_head_size($mediatag['src']);
                }
                if ((empty($esize) || $esize > 2500) && !in_array_r($mediatag['src'], $enclosures) && !empty($mediatag['src'])) {
                    $enclosures[] = array('url' => $mediatag['src'],
                                          'length' => 0 + $esize,
                                          'type' => make_mime_type($mediatag['src'], $mediatag['type']),
                                          'extracted' => TRUE
                    );
                    $media = 1;
                }
            }
        }

        //Serialize enclosures
        $enclosure = serialize($enclosures);

        //De-relativize links
        $httploc = strpos($linkurl, 'http');
        if ($httploc === FALSE || $httploc > 1) {
            $linkurl = '';
        }

        //Does this item have a source tag?
        $sourceurl = "";
        if ($item->source->id) {
            $sourceurl = (string)$item->source->id;
        }
        $sourcetitle = "";
        if ($item->source->title) {
            $sourcetitle = strip_tags((string)$item->source->title);
        }

        //Is there an author?
        $author = "";
        if ($item->author->name) {
            $author = strip_tags((string)$item->author->name);
        }

        //Is there an origin? Not for ATOM.
        $origin = "";

        //Eliminate title if it's just a duplicate of the body
        if ($description == $title) {
            $title = "";
        }

        $sql->bind_param("sssssddsssssd", $fid, $title, $linkurl, $description, $item->id, $pubdate, $timeadded, $enclosure, $sourceurl, $sourcetitle, $author, $origin, $media) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        //-----RSS----------------------------------------------------------------------------------------------------------------------------------------------------
        $linkurl = trim((string)$item->link[0]);
        $title = clean_feed_item_content((string)$item->title, 0, FALSE, FALSE);
        $description = $item->description;

        //We also need to find any enclosures
        $mcount = count($item->enclosure);
        $enclosures = array();
        for ($i = 0; $i < $mcount; $i++) {
            if(!empty((string)$item->enclosure[$i]->attributes()->url)) {
                if (!in_array_r((string)$item->enclosure[$i]->attributes()->url, $enclosures)) {
                    $enclosures[$i] = array('url' => (string)$item->enclosure[$i]->attributes()->url,
                        'length' => 0 + (string)$item->enclosure[$i]->attributes()->length,
                        'type' => make_mime_type((string)$item->enclosure[$i]->attributes()->url, (string)$item->enclosure[$i]->attributes()->type)
                    );
                    $media = 1;
                }
            }
        }

        if(stripos($fdurl, "dailymail") !== FALSE) {
            loggit(3, "DAILYMAIL: link is [$linkurl]");
        }

        //Does this item have a media namespace?
        if (isset($namespaces['media'])) {
            $kids = $item->children($namespaces['media'])->thumbnail;
            $kcount = count($kids);
            $ecount = count($enclosures);
            for ($i = 0; $i < $kcount; $i++) {
                if (isset($kids[$i]->attributes()->url) && !empty((string)$kids[$i]->attributes()->url)) {
                    $murl = (string)$kids[$i]->attributes()->url;
                    if (!in_array_r($murl, $enclosures)) {
                        $enclosures[$ecount] = array('url' => (string)$kids[$i]->attributes()->url, 'length' => 0, 'type' => make_mime_type((string)$kids[$i]->attributes()->url));
                        $ecount++;
                        $media = 1;
                    }
                }
            }

            $kids = $item->children($namespaces['media'])->content;
            $kcount = count($kids);
            $ecount = count($enclosures);
            for ($i = 0; $i < $kcount; $i++) {
                if (isset($kids[$i]->attributes()->url) && !empty((string)$kids[$i]->attributes()->url)) {
                    $murl = (string)$kids[$i]->attributes()->url;
                    if (!in_array_r($murl, $enclosures)) {
                        $enclosures[$ecount] = array('url' => (string)$kids[$i]->attributes()->url, 'length' => 0, 'type' => make_mime_type((string)$kids[$i]->attributes()->url));
                        $ecount++;
                        $media = 1;
                    }
                }
            }
        }

        //Does this item have a content namespace?
        if (isset($namespaces['content'])) {
            $content = $item->children($namespaces['content']);
            if (isset($content->encoded) && stripos((string)trim($content->encoded), 'fb:article_style') === FALSE) {
                $description = (string)trim($content->encoded);
                loggit(1, "Content:encoded found: " . print_r($content, TRUE));
            }
        }

        //Link extraction for those "special" feeds
        if (strpos($linkurl, 'twitter.com') !== FALSE) {
            //Search for an embedded link in the description
            $title = $item->title;
            if (strpos($title, 'http:') !== FALSE) {
                preg_match('/\bhttp\:\/\/([A-Za-z0-9\.\/\+\&\@\~\-\%\?\=\_\#\!]*)/i', $title, $twurl);
                $linkurl = $twurl[0];
            }
        }

        //Does this item have a source tag?
        $sourceurl = "";
        $sourcetitle = "";
        if ($item->source && $item->source->attributes()) {
            //loggit(3, "SOURCE: ".print_r($item->source, TRUE));
            $sourceurl = (string)$item->source->attributes()->url;
            $sourcetitle = strip_tags((string)$item->source);
        }

        //Is there an author?
        $author = "";
        if ($item->author) {
            $author = strip_tags((string)$item->author);
        }

        //We need a guid, so if the item doesn't have a guid, then build a unique id by hashing the whole item
        if(isset($namespaces['rdf'])) {
            $uniq = get_unique_id_for_rdf_feed_item($item, $namespaces);
        } else {
            $uniq = get_unique_id_for_feed_item($item);
        }


        //De-relativize links
        $httploc = strpos($linkurl, 'http');
        if ($httploc === FALSE || $httploc > 1) {
            $linkurl = '';
        }

        //Clean the incoming description
        $cleaned = clean_feed_item_content($description, 0, TRUE, TRUE);
        $description = $cleaned['text'];

        //TODO: Experimental!!
        //Do we want to cartulize new feed item links?
        //$article = cartulize($url);

        //Attach extracted media tags as enclosures with correct type
        if (is_array($cleaned['media']) && count($cleaned['media']) > 0) {
            foreach ($cleaned['media'] as $mediatag) {
                $esize = "";
                if ($mediatag['type'] == 'image' || $mediatag['type'] == 'audio' || $mediatag['type'] == 'video') {
                    $esize = check_head_size($mediatag['src']);
                }
                if ((empty($esize) || $esize > 2500) && !in_array_r($mediatag['src'], $enclosures) && !empty($mediatag['src'])) {
                    $enclosures[] = array(
                        'url' => $mediatag['src'],
                        'length' => 0 + $esize,
                        'type' => make_mime_type($mediatag['src'], $mediatag['type']),
                        'extracted' => TRUE
                    );
                    $media = 1;
                }
            }
        }

        //Serialize the enclosure array
        $enclosure = serialize($enclosures);

        //Eliminate title if it's just a duplicate of the body
        if ($description == $title) {
            $title = "";
        }

        //Does this item have an origin?
        $origin = "";
        if (isset($namespaces['feedburner'])) {
            $feedburner = $item->children($namespaces['feedburner']);
            if (isset($feedburner->origLink)) {
                $origin = (string)trim($feedburner->origLink);
                loggit(1, "add_feed_item(): using feedburner:origLink as the origin: " . print_r($feedburner, TRUE));
            }
        }
        if (isset($namespaces['scripting2'])) {
            $scripting2 = $item->children($namespaces['scripting2']);
            if (isset($scripting2->source)) {
                $origin = (string)trim($scripting2->source);
                loggit(1, "add_feed_item(): using scripting2:source as the origin: " . print_r($scripting2, TRUE));
            }
        }
        if (isset($namespaces['source'])) {
            $microblog = $item->children($namespaces['source']);
            if (isset($microblog->linkFull)) {
                $origin = (string)trim($microblog->linkFull);
                loggit(1, "add_feed_item(): using source:linkFull as the origin: " . print_r($microblog, TRUE));
            }
        }
        if (isset($namespaces['sopml'])) {
            $sopml = $item->children($namespaces['sopml']);
            if (isset($sopml->origin)) {
                $origin = (string)trim($sopml->origin);
                loggit(1, "add_feed_item(): sopml:origin found: " . print_r($sopml, TRUE));
            }
        }

        $sql->bind_param("sssssddsssssd", $fid, $title, $linkurl, $description, $uniq, $pubdate, $timeadded, $enclosure, $sourceurl, $sourcetitle, $author, $origin, $media) or loggit(2, "MySql error: " . $dbh->error);
    }
    $sql->execute() or loggit(3, $dbh->error);

    //Get the last item id that was generated
    $id = $sql->insert_id;
    $sql->close();

    //Put enclosures in the database
    if(!empty($enclosures)) {
        foreach($enclosures as $e) {
            add_feed_item_enclosure($id, $e);
        }
    }

    //Map the searchable terms in this item if new search is enabled
    if( $cg_search_v2_enable ) {
        map_feed_item($id, $title." ".$description);
    }

    //Set the item properties per user
    $fusers = get_feed_subscribers($fid);
    if ($fusers != FALSE) {
        foreach ($fusers as $fuser) {
            $fiprops = array('sticky' => FALSE, 'hidden' => FALSE, 'fulltext' => FALSE);
            if (feed_is_sticky($fid, $fuser) && $old != TRUE) {
                $fiprops['sticky'] = TRUE;
            }
            if (feed_is_hidden($fid, $fuser)) {
                $fiprops['hidden'] = TRUE;
            }
            if (feed_is_fulltext($fid, $fuser)) {
                $fiprops['fulltext'] = TRUE;
            }
            set_feed_item_properties($id, $fuser, $fiprops);
            mark_river_as_updated($fuser);
        }
    }

    $ttime = time() - $tstart;
    $identifier = $linkurl;
    if( empty($identifier) ) {
        $identifier = $uniq;
    }
    if( $ttime > 1 ) {
        loggit(3, "   TIMER: add_feed_item() took: [$ttime] seconds for item: [$identifier]");
    }

    //Log and return
    loggit(1, "New feed item for feed: [$fid].");
    return ($id);
}


//Check if a feed item is already in the newsfeed repository
function feed_item_exists($fid = NULL, $guid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($guid == NULL) {
        loggit(2, "The feed item guid is blank or corrupt: [$guid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $stmt = "SELECT id FROM $table_nfitem WHERE feedid=? AND guid=?";
    //loggit(3, $stmt);
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $guid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    $rowcount = $sql->num_rows();
    if ($rowcount < 1) {
        $sql->close() or loggit(2, "MySql error: " . $dbh->error);
        //loggit(3,"The feed item with guid: [$guid] does not exist for feed: [$fid]. Row count: [$rowcount].");
        return (FALSE);
    }
    $sql->bind_result($itemid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //loggit(3,"The feed item: [$itemid] with guid: [$guid] already exists for feed: [$fid].");
    return ($itemid);
}


//Parse a feed item and add it's search term mapping to the feed item map table
function map_feed_item($iid = NULL, $content = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($content)) {
        loggit(2, "The feed item content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$includes/search.php";

    //Parse the content of the item
    $words = array_unique(str_word_count(strip_tags($content), 1));
    //loggit(3, "MAP DEBUG: ".print_r($words, TRUE));

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Put each word into the map table and link it to the newsfeed item in the map catalog table
    foreach( $words as $word ) {
        //We only do this for words that are 3 or more characters long
        //since the catalog table can get huge and nobody would search for
        //those tiny words anyway unless they are part of a phrase.  We want
        //to exclude words that are:  less than 3 chars OR in the word exclude
        //dictionary OR are just repeated strings of the same letter.  For
        //phrases we drop back to standard LIKE search
        $word = strtolower(stripText($word, NULL, NULL, FALSE));
        if( strlen($word) >= 3 && !isset($cg_search_map_ignore[$word]) ) {
            //See if the word exists
            $wordid = map_get_word_id($word);

            if( $wordid < 0 ) {
                //Insert each word into the map table
                $stmt = "INSERT INTO $table_nfitem_map (word,added) VALUES (?,NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), dummy=NOT dummy";
                $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
                $sql->bind_param("s", $word) or loggit(2, "MySql error: " . $dbh->error);
                $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
                $lid = $sql->insert_id;
                $sql->close();
            } else {
                $lid = $wordid;
            }

            if( is_numeric($lid) && $lid > 0 ) {
                //Now catalog the mapping
                $stmt = "INSERT INTO $table_nfitem_map_catalog (wordid,nfitemid,added) VALUES (?,?, NOW()) ON DUPLICATE KEY UPDATE wordid=wordid,added=NOW()";
                $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
                $sql->bind_param("dd", $lid, $iid) or loggit(2, "MySql error: " . $dbh->error);
                $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
                $sql->close();
            }
        } else {
            //loggit(3, "Skipping word map for: [$word]");
        }
    }


    //loggit(3,"The feed item: [$itemid] with guid: [$guid] already exists for feed: [$fid].");
    return (TRUE);
}


//Check if a river needs to be rebuilt
function river_updated($uid = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT userid FROM $table_river WHERE userid=? AND updated=1") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The river for user: [$uid] does not need rebuilding.");
        return (FALSE);
    }
    $sql->bind_result($userid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    loggit(1, "The river for user: [$uid] needs rebuilding.");
    return (TRUE);
}


//Stick a built river array into the rivers table
function update_river($uid = NULL, $river = NULL, $mriver = NULL, $hash = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($river == NULL) {
        loggit(2, "The river array is blank or corrupt: [$river]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the first item id from the array
    $firstid = $river['metadata']['firstId'];

    //Get the lastbuild time
    $lastbuild = $river['metadata']['lastBuildDate'];

    //Turn the river into a storable string object
    $striver = serialize($river);

    //If there is a mobile river, serialize it too
    $mtriver = "";
    if (!empty($mriver)) {
        $mtriver = serialize($mriver);
    }

    //Hash the river for change detection
    //$conthash = md5($striver);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good uid and river, put the river in the database
    $stmt = "REPLACE INTO $table_river (userid,lastbuild,river,conthash,firstid,updated,mriver) VALUES (?,?,?,?,?,0,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssssss", $uid, $lastbuild, $striver, $hash, $firstid, $mtriver) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    //loggit(3,"Updated river array for user: [$uid]. Checksum: [$conthash].");
    return (TRUE);
}


//Build an array of feed items that will be the river for this user
function build_river($uid = NULL, $max = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //The river array
    $river = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the items that belong to feeds this user subscribes to
    $sqltxt = "SELECT $table_nfitem.id,
		    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.guid,
                    $table_nfitem.timestamp,
                    $table_nfitem.timeadded,
                    $table_nfitem.feedid,
                    $table_nfitem.description
	     FROM $table_nfitem,
                  $table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_nfitem.feedid)";
    $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The user: [$uid] has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $guid, $timestamp, $timeadded, $feedid, $description) or loggit(2, "MySql error: " . $dbh->error);

    $count = 0;
    while ($sql->fetch()) {
        $feed = get_feed_info($feedid);
        $feedtitle = $feed['title'];
        $feedlink = $feed['link'];
        $description = truncate_text($description, 255);
        $river[$count] = array(
            'feedid' => $feedid,
            'feedtitle' => $feedtitle,
            'feedlink' => $feedlink,
            'id' => $id,
            'title' => $title,
            'url' => $url,
            'guid' => $guid,
            'timestamp' => $timestamp,
            'timeadded' => $timeadded,
            'description' => $description,
            'pubdate' => date("F j, g:i a", $timestamp)
        );
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] items in user: [$uid]'s river.");
    return ($river);
}


//Build a json array of feed items that will be the river for this user
function build_river_json($uid = NULL, $max = NULL, $force = FALSE, $mobile = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/opml.php";

    //Get the users prefs
    $prefs = get_user_prefs($uid);
    if (!$prefs) {
        loggit(2, "Couldn't get prefs for user: [$uid]");
        return (FALSE);
    }
    $start = time() - ($prefs['riverhours'] * 3600);
    $dmax = $prefs['maxriversize'];
    $mmax = $prefs['maxriversizemobile'];

    //The river array
    $river = array();
    $driver = array();
    $mriver = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Assemble query
    $sqltxt = "SELECT $table_nfitem.id,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.origin,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
                    $table_nfitem.author,
                    $table_nfitemprop.sticky,
                    $table_nfcatalog.sticky,
                    $table_nfitemprop.hidden,
                    $table_nfcatalog.hidden,
                    $table_nfitemprop.`fulltext`,
                    $table_nfcatalog.`fulltext`
             FROM $table_nfitem
             LEFT OUTER JOIN $table_nfitemprop ON $table_nfitemprop.itemid = $table_nfitem.id AND $table_nfitemprop.userid=? AND $table_nfitemprop.sticky = 1
             INNER JOIN $table_nfcatalog ON $table_nfcatalog.feedid = $table_nfitem.feedid
             WHERE $table_nfcatalog.userid=?
             AND ( $table_nfitem.timeadded > ? OR $table_nfitemprop.sticky = 1 )
             AND $table_nfitem.`old` = 0";
    $sqltxt .= " ORDER BY $table_nfitemprop.sticky DESC, $table_nfitem.timeadded DESC";
    //loggit(3, $sqltxt);

    //Make sure to set the LIMIT to the higher of the two max values, so we cover both
    if ($max == NULL) {
        $max = $dmax;
        if ($mmax > $dmax) {
            $max = $mmax;
        }
    }
    $sqltxt .= " LIMIT $max";

    //Execute the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssd", $uid, $uid, $start) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The user: [$uid] has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid,
        $timeadded, $enclosure, $description,
        $guid, $origin, $sourceurl, $sourcetitle,
        $author, $sticky, $fsticky, $hidden,
        $fhidden, $fulltext, $ffulltext) or loggit(2, "MySql error: " . $dbh->error);

    $fcount = -1;
    $icount = 0;
    $ticount = 0;
    $drcount = 0;
    $mrcount = 0;
    $firstid = "";
    $lastfeedid = "";
    $pubdate = time();
    while ($sql->fetch()) {
        $feed = get_feed_info($feedid);

        //Save the time stamp of the first item to use as a pubdate
        if ($firstid == "" && $sticky != 1 && $hidden != 1) {
            $pubdate = $timeadded;
            $firstid = $id;
        }

        //Keep track of which feed we're in along the way
        if ($lastfeedid != $feedid) {
            $fcount++;
            $icount = 0;
            $lastfeedid = $feedid;

            //Insert a new array that will contain the feed
            $river[$fcount] = array(
                'feedId' => $feedid,
                'feedUrl' => $feed['url'],
                'websiteUrl' => $feed['link'],
                'feedTitle' => $feed['title'],
                'feedDescription' => '',
                'feedSticky' => $fsticky,
                'feedHidden' => $fhidden,
                'feedFullText' => $ffulltext,
                'itemIndex' => $ticount,
                'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
            );

            //Check if this feed is linked to an outline this user subscribes to
            $oid = get_feed_outline_by_user($feedid, $uid);
            if ($oid != FALSE) {
                $ou = get_outline_info($oid);
                $river[$fcount]['linkedOutlineId'] = $oid;
                if (!empty($ou['type'])) {
                    $river[$fcount]['linkedOutlineType'] = $ou['type'];
                }
                if (!empty($ou['title'])) {
                    $river[$fcount]['linkedOutlineTitle'] = $ou['title'];
                }
                if (!empty($ou['url'])) {
                    $river[$fcount]['linkedOutlineUrl'] = $ou['url'];
                }
                if (!empty($ou['ownername'])) {
                    $river[$fcount]['ownerName'] = $ou['ownername'];
                }
                if (!empty($ou['avatarurl'])) {
                    $river[$fcount]['avatarUrl'] = $ou['avatarurl'];
                }
            }

            //Does this feed have an avatar url?
            if (!empty($feed['avatarurl'])) {
                $river[$fcount]['avatarUrl'] = $feed['avatarurl'];
            }

            //Start a sub-array in this feed array to hold items
            $river[$fcount]['item'] = array();

        }

        //Construct item body
        if ($prefs['fulltextriver'] == 0) {
            if ($ffulltext == 1) {
                $itembody = $description;
            } else
                if (strlen($description) > 300) {
                    $itembody = truncate_text($description, 300) . "...";
                } else {
                    $itembody = $description;
                }
        } else {
            $itembody = $description;
        }

        //Fill in the details of this item
        $river[$fcount]['item'][$icount] = array(
            'index' => $ticount,
            'body' => $itembody,
            'permaLink' => $url,
            'guid' => $guid,
            'pubDate' => date("D, d M Y H:i:s O", $timeadded),
            'title' => $title,
            'link' => $url,
            'id' => $id
        );

        //Is there an author attribution?
        if (!empty($author)) {
            $river[$fcount]['item'][$icount]['author'] = $author;
        }

        //Does this item specify a source attribution?
        if (!empty($sourceurl)) {
            $river[$fcount]['item'][$icount]['sourceurl'] = $sourceurl;
        }
        if (!empty($sourcetitle)) {
            $river[$fcount]['item'][$icount]['sourcetitle'] = $sourcetitle;
        }

        //Is there an origin?
        if (!empty($origin)) {
            $river[$fcount]['item'][$icount]['origin'] = $origin;
        } else {
            $river[$fcount]['item'][$icount]['origin'] = $feed['url'] . "|" . $guid;
        }

        //Set the sticky bit
        if ($sticky == 1) {
            $river[$fcount]['item'][$icount]['sticky'] = 1;
        }

        //Set the hidden bit
        if ($hidden == 1) {
            $river[$fcount]['item'][$icount]['hidden'] = 1;
        }

        //Set the full text bit
        if ($ffulltext == 1) {
            $river[$fcount]['item'][$icount]['fullText'] = 1;
        }

        //Are there any enclosures?
        $enclosures = unserialize($enclosure);
        if ($enclosures != FALSE) {
            if (!empty($enclosures)) {
                if (!empty($enclosures[0]['url'])) {
                    $river[$fcount]['item'][$icount]['enclosure'] = $enclosures;
                }
            }
        }

        //We're building two rivers here.  One for desktop and one for mobile
        if ($ticount <= $dmax) {
            $driver = $river;
            $drcount++;
        }
        if ($ticount <= $mmax) {
            $mriver = $river;
            $mrcount++;
        }

        $icount++;
        $ticount++;
    }

    $sql->close();

    //Encapsulate the river
    $doutput['updatedFeeds']['updatedFeed'] = $driver;
    $moutput['updatedFeeds']['updatedFeed'] = $mriver;

    //Add metadata
    $doutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );
    $moutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );

    //Json encode the river
    $djsonriver = "onGetRiverStream(" . json_encode($doutput) . ")";
    $mjsonriver = "onGetRiverStream(" . json_encode($moutput) . ")";

    //Let's return the river asked for
    $jsonriver = $djsonriver;
    if ($mobile == TRUE) {
        $jsonriver = $mjsonriver;
    }

    //Let's be smart about this and not re-publish a river that hasn't changed
    $pubriver = get_river_info($uid);
    $newhash = md5(serialize($driver));

    //loggit(3, "River hash: OLD: [".$pubriver['conthash']."]");
    //loggit(3, "River hash: NEW: [$newhash]");

    if (!empty($pubriver) && ($pubriver['firstid'] == $firstid && $force == FALSE) && ($pubriver['conthash'] == $newhash)) {
        loggit(1, "User: [$uid]'s river has not changed. No need to publish.");
        return ($jsonriver);
    }

    //Put this built river in the database
    update_river($uid, $doutput, $moutput, $newhash);

    //If we can get some sane S3 credentials and the user wants a public river then let's go
    if ($prefs['publicriver'] == 1) {
        if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
            //First we get all the key info
            $s3info = get_s3_info($uid);

            //Subpath?  Must begin with a slash
            $subpath = "";

            //Put the desktop file
            $filename = $default_river_json_file_name;
            $s3res = putInS3(gzencode($djsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote desktop river to S3 at url: [$s3url].");
            }
            //Put the mobile file
            $filename = $default_river_json_mobile_file_name;
            $s3res = putInS3(gzencode($mjsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote mobile river to S3 at url: [$s3url].");
            }
        }
    } else {
        loggit(1, "Skipping S3 upload of river json since user wants it private.");
    }

    loggit(1, "Returning: [$drcount] items in user: [$uid]'s desktop river.");
    loggit(1, "Returning: [$mrcount] items in user: [$uid]'s mobile river.");
    return ($jsonriver);
}


//Hash a feed item to construct a unique id for this item
function get_unique_id_for_feed_item($item = NULL)
{
    //Check parameters
    if (empty($item)) {
        loggit(2, "The feed item is blank or corrupt: [" . print_r($item, TRUE) . "]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //If a guid exists then get that instead
    if (!empty($item->guid)) {
        //loggit(3, "Returning guid: [".$item->guid."] as unique id for feed item."]");
        return ($item->guid);
    }

    //Hash it
    $hashed = sha1($item->asXML());

    //Return
    //loggit(3, "Returning hash: [$hashed] as unique id for feed item: [".print_r($item, TRUE)."]");
    return ($hashed);
}


//Hash a feed item to construct a unique id for this item
function get_unique_id_for_rdf_feed_item($item = NULL, $namespaces = array())
{
    //Check parameters
    if (empty($item)) {
        loggit(2, "The feed item is blank or corrupt: [" . print_r($item, TRUE) . "]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //loggit(3, "DEBUG: RDF namespaces: ".print_r($namespaces, TRUE));

    //Get rdf about string
    if( isset($namespaces['rdf']) && !empty($item->attributes($namespaces['rdf'])->about) ) {
        //loggit(3, "RDF item->about attribute: ".$item->attributes($namespaces['rdf'])->about);
        return ($item->attributes($namespaces['rdf'])->about);
    }

    //If a guid exists then get that instead
    if (!empty($item->guid)) {
        //loggit(3, "Returning guid: [".$item->guid."] as unique id for feed item."]");
        return ($item->guid);
    }

    //Hash it
    $hashed = sha1($item->asXML());

    //Return
    //loggit(3, "Returning hash: [$hashed] as unique id for feed item: [".print_r($item, TRUE)."]");
    return ($hashed);
}


//Search for river items that match the query for this user
function search_feed_items($uid = NULL, $query = NULL, $max = NULL, $withopml = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }
    if ($query == NULL) {
        loggit(2, "The query given is corrupt or blank: [$query]");
        return (FALSE);
    }


    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Assemble sql
    $colnames = array(
        "$table_nfitem.description",
        "$table_nfitem.title",
        "$table_nfitem.sourcetitle",
        "$table_nfitem.url",
        "$table_nfitem.author"
    );
    $qsql = build_search_sql($query, $colnames);

    //We search by doing the equivelant of a river build, but we limit based on the query text
    $sqltxt = "SELECT $table_nfitem.id,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
		    $table_nfitem.author,
		    $table_nfitemprop.sticky,
                    $table_nfcatalog.sticky,
		    $table_nfitemprop.hidden,
                    $table_nfcatalog.hidden
             FROM $table_nfitem
	     LEFT OUTER JOIN $table_nfitemprop ON $table_nfitemprop.itemid = $table_nfitem.id AND $table_nfitemprop.userid=? AND $table_nfitemprop.sticky = 1
	     INNER JOIN $table_nfcatalog ON $table_nfcatalog.feedid = $table_nfitem.feedid
             WHERE $table_nfcatalog.userid=?";

    //Append search criteria
    $sqltxt .= $qsql['text'];

    //Sort by date
    $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";

    //Limits
    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT 30";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);

    //Adjust bindings
    $newsetup = "ss" . $qsql['bind'][0];
    $qsql['bind'][0] = & $newsetup;
    array_splice($qsql['bind'], 1, 0, array(&$uid));
    array_splice($qsql['bind'], 1, 0, array(&$uid));

    $ref = new ReflectionClass('mysqli_stmt');
    $method = $ref->getMethod("bind_param");
    $method->invokeArgs($sql, $qsql['bind']);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any articles for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for user: [$uid] with given criteria.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid, $timeadded, $enclosure, $description, $sourceurl, $sourcetitle, $author, $sticky, $fsticky, $hidden, $fhidden) or loggit(2, "MySql error: " . $dbh->error);

    $nfitems = array();
    $count = 0;
    while ($sql->fetch()) {
        if (empty($title)) {
            $title = $description;
        }
        $nfitems[$count] = array('id' => $id, 'title' => $title, 'url' => $url);
        if($withopml) {
            $nfitems[$count]['description'] = $description;
            $nfitems[$count]['timeadded'] = $timeadded;
        }
        $count++;
    }

    $sql->close();

    if($withopml) {
        $s3url = build_opml_nfitem_feed($uid, $max, FALSE, $nfitems, FALSE, "search/riversearch", TRUE, "River search results: [".$query['flat']."]");
        loggit(3, "OPMLURL: $s3url");
        if(is_string($s3url)) {
            $nfitems['opmlurl'] = $s3url;
            loggit(3, "OPMLURL: ".$nfitems['opmlurl']);
        }
    }

    loggit(1, "Returning: [$count] newsfeed items for user: [$uid]");
    return ($nfitems);
}


//Build a json array of feed items for the entire server
function build_server_river_json($max = NULL, $force = FALSE, $mobile = FALSE)
{

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/opml.php";

    $start = time() - (6 * 3600);
    $dmax = 100;
    $mmax = 50;

    //The river array
    $river = array();
    $driver = array();
    $mriver = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get the items
    $sqltxt = "SELECT $table_nfitem.id,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
		    $table_nfitem.author,
		    $table_nfitem.origin
             FROM $table_nfitem
             WHERE $table_nfitem.timeadded > ?
             AND $table_nfitem.`old` = 0";
    $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";
    //loggit(3, $sqltxt);

    //Make sure to set the LIMIT to the higher of the two max values, so we cover both
    if ($max == NULL) {
        $max = $dmax;
        if ($mmax > $dmax) {
            $max = $mmax;
        }
    }
    $sqltxt .= " LIMIT $max";

    //loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("d", $start) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The server has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid, $timeadded, $enclosure, $description, $guid, $sourceurl, $sourcetitle, $author, $origin) or loggit(2, "MySql error: " . $dbh->error);

    $fcount = -1;
    $icount = 0;
    $ticount = 0;
    $drcount = 0;
    $mrcount = 0;
    $firstid = "";
    $lastfeedid = "";
    $pubdate = time();
    while ($sql->fetch()) {
        $feed = get_feed_info($feedid);

        //Let's not put the admin log feed in the public river
        if (strpos($feed['url'], '/adminlog-rss') !== FALSE) {
            continue;
        }

        //Save the time stamp of the first item to use as a pubdate
        if ($firstid == "") {
            $pubdate = $timeadded;
            $firstid = $id;
        }

        //Keep track of which feed we're in along the way
        if ($lastfeedid != $feedid) {
            $fcount++;
            $icount = 0;
            $lastfeedid = $feedid;

            //Insert a new array that will contain the feed
            $river[$fcount] = array(
                'feedId' => $feedid,
                'feedUrl' => $feed['url'],
                'websiteUrl' => $feed['link'],
                'feedTitle' => $feed['title'],
                'feedDescription' => '',
                'itemIndex' => $ticount,
                'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
            );

            //Does this feed have an avatar url?
            if (!empty($feed['avatarurl'])) {
                $river[$fcount]['avatarUrl'] = $feed['avatarurl'];
            }

            //Start a sub-array in this feed array to hold items
            $river[$fcount]['item'] = array();

        }

        //Body text of item
        if (strlen($description) > 512) {
            $itembody = truncate_text($description, 512) . "...";
        } else {
            $itembody = $description;
        }

        //Fill in the details of this item
        $river[$fcount]['item'][$icount] = array(
            'index' => $ticount,
            'body' => $itembody,
            'permaLink' => $url,
            'pubDate' => date("D, d M Y H:i:s O", $timeadded),
            'title' => $title,
            'link' => $url,
            'id' => $id
        );

        //Is there an author attribution?
        if (!empty($author)) {
            $river[$fcount]['item'][$icount]['author'] = $author;
        }

        //Does this item specify a source attribution?
        if (!empty($sourceurl)) {
            $river[$fcount]['item'][$icount]['sourceurl'] = $sourceurl;
        }
        if (!empty($sourcetitle)) {
            $river[$fcount]['item'][$icount]['sourcetitle'] = $sourcetitle;
        }

        //Is there an origin?
        if (!empty($origin)) {
            $river[$fcount]['item'][$icount]['origin'] = $origin;
        } else {
            $river[$fcount]['item'][$icount]['origin'] = $feed['url'] . "|" . $guid;
        }

        //Are there any enclosures?
        $enclosures = unserialize($enclosure);
        if ($enclosures != FALSE) {
            if (!empty($enclosures)) {
                if (!empty($enclosures[0]['url'])) {
                    $river[$fcount]['item'][$icount]['enclosure'] = $enclosures;
                }
            }
        }

        //We're building two rivers here.  One for desktop and one for mobile
        if ($ticount <= $dmax) {
            $driver = $river;
            $drcount++;
        }
        if ($ticount <= $mmax) {
            $mriver = $river;
            $mrcount++;
        }

        $icount++;
        $ticount++;
    }

    $sql->close();

    //Encapsulate the river
    $doutput['updatedFeeds']['updatedFeed'] = $driver;
    $moutput['updatedFeeds']['updatedFeed'] = $mriver;

    //Add metadata
    $doutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );
    $moutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );

    //Json encode the river
    $djsonriver = "onGetRiverStream(" . json_encode($doutput) . ")";
    $mjsonriver = "onGetRiverStream(" . json_encode($moutput) . ")";

    //Let's return the river asked for
    $jsonriver = $djsonriver;
    if ($mobile == TRUE) {
        $jsonriver = $mjsonriver;
    }

    //If we can get some sane S3 credentials then let's go
    if (sys_s3_is_enabled()) {
        //First we get all the key info
        $s3info = get_sys_s3_info();

        //Subpath?  Must begin with a slash
        $subpath = "";

        //Put the json river file
        $filename = $default_river_json_file_name;
        $s3res = putInS3($djsonriver, $filename, $s3info['riverbucket'] . $subpath, $s3info['key'], $s3info['secret'], "application/javascript");
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename].");
            //loggit(3, "Could not create S3 file: [$filename].");
        } else {
            $s3url = get_server_river_s3_url($subpath, $filename);
            loggit(1, "Wrote server river json to S3 at url: [$s3url].");
        }

        //We always put the json file if the bucket is enabled, but only put the html stuff if
        //the riverfile value is non-blank
        if (!empty($s3info['riverfile'])) {
            //Construct the server river html file
            if (empty($s3info['rivertemplate'])) {
                $fh = fopen("$confroot/$templates/$cg_template_html_river", "r");
                $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_template_html_river"));
                fclose($fh);
            } else {
                $rftemplate = fetchUrl($s3info['rivertemplate']);
                if (is_outline($rftemplate)) {
                    $rftemplate = convert_opml_to_text($rftemplate);
                    $rftemplate = str_replace('<%opmlUrl%>', $s3info['rivertemplate'], $rftemplate);
                }
            }
            //Replace the tags
            $rftemplate = str_replace('<%title%>', $s3info['rivertitle'], $rftemplate);
            $rftemplate = str_replace('<%description%>', '', $rftemplate);
            $rftemplate = str_replace('<%jsonUrl%>', $s3url, $rftemplate);
            $rftemplate = str_replace('<%sopmlUrl%>', '', $rftemplate);
            $rftemplate = str_replace('[SOPML_URL]', '', $rftemplate);
            $rftemplate = str_replace('<%pleaseWaitMessage%>', 'Loading news...', $rftemplate);
            $rftemplate = str_replace('[RIVER_TITLE]', $s3info['rivertitle'], $rftemplate);
            $rftemplate = str_replace('[RIVER_JSON_URL]', $s3url, $rftemplate);
            $rftemplate = str_replace('[SCRIPT_JQUERY]', $cg_script_js_jquery, $rftemplate);
            $rftemplate = str_replace('[SCRIPT_JQTEMPLATES]', $cg_script_js_jqtemplates, $rftemplate);
            $rftemplate = str_replace('[DATE]', date("D, d M Y H:i:s O"), $rftemplate);
            $rftemplate = str_replace('[SYS_NAME]', $system_name, $rftemplate);
            $rftemplate = str_replace('[SYS_VERSION]', $cg_sys_version, $rftemplate);
            $rftemplate = str_replace('[OPENSUBS_ADD_LINK]', '', $rftemplate);
            $rftemplate = str_replace('<%openSubsAddLink%>', '', $rftemplate);

            //Put the html template
            $filename = $s3info['riverfile'];
            $s3res = putInS3($rftemplate, $filename, $s3info['riverbucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/html");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_server_river_s3_url($subpath, $filename);
                loggit(1, "Wrote server river html to S3 at url: [$s3url].");
            }

            //Put the support files
            $filename = $cg_template_css_river;
            $s3res = putFileInS3("$confroot/$templates/$cg_template_css_river", $filename, $s3info['riverbucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/css");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_server_river_s3_url($subpath, $filename);
                loggit(1, "Wrote server river html to S3 at url: [$s3url].");
            }
            $filename = $cg_script_js_jquery;
            $s3res = putFileInS3("$confroot/$scripts/$cg_script_js_jquery", $filename, $s3info['riverbucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/javascript");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_server_river_s3_url($subpath, $filename);
                loggit(1, "Wrote jquery script to S3 at url: [$s3url].");
            }
            $filename = $cg_script_js_jqtemplates;
            $s3res = putFileInS3("$confroot/$scripts/$cg_script_js_jqtemplates", $filename, $s3info['riverbucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/javascript");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_server_river_s3_url($subpath, $filename);
                loggit(1, "Wrote jquery templates script to S3 at url: [$s3url].");
            }
        }
    }

    loggit(1, "Returning: [$drcount] items in server river.");
    loggit(1, "Returning: [$mrcount] items in server river.");
    return ($jsonriver);
}


//Build a public river for a user
function build_public_river($uid = NULL, $max = NULL, $force = FALSE, $mobile = FALSE)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/opml.php";

    //Prefs
    $prefs = get_user_prefs($uid);

    $start = time() - (48 * 3600);
    $dmax = 75;
    $mmax = 50;

    //The river array
    $river = array();
    $driver = array();
    $mriver = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Assemble query
    $sqltxt = "SELECT $table_nfitem.id,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.origin,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
                    $table_nfitem.author,
                    $table_nfitemprop.hidden,
                    $table_nfcatalog.hidden
             FROM $table_nfitem
             LEFT OUTER JOIN $table_nfitemprop ON $table_nfitemprop.itemid = $table_nfitem.id AND $table_nfitemprop.userid=?
             INNER JOIN $table_nfcatalog ON $table_nfcatalog.feedid = $table_nfitem.feedid
             WHERE $table_nfcatalog.userid=?
             AND $table_nfitem.timeadded > ?
             AND $table_nfitem.`old` = 0";
    $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";
    //loggit(3, $sqltxt);

    //Make sure to set the LIMIT to the higher of the two max values, so we cover both
    if ($max == NULL) {
        $max = $dmax;
        if ($mmax > $dmax) {
            $max = $mmax;
        }
    }
    $sqltxt .= " LIMIT $max";

    //Execute the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssd", $uid, $uid, $start) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The user: [$uid] has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid,
        $timeadded, $enclosure, $description,
        $guid, $origin, $sourceurl, $sourcetitle,
        $author, $hidden, $fhidden) or loggit(2, "MySql error: " . $dbh->error);

    $fcount = -1;
    $icount = 0;
    $ticount = 0;
    $drcount = 0;
    $mrcount = 0;
    $firstid = "";
    $lastfeedid = "";
    $pubdate = time();
    while ($sql->fetch()) {
        $feed = get_feed_info($feedid);

        //Let's not put the admin log feed in the public river
        if (strpos($feed['url'], '/adminlog-rss') !== FALSE) {
            continue;
        }

        //Save the time stamp of the first item to use as a pubdate
        if ($firstid == "") {
            $pubdate = $timeadded;
            $firstid = $id;
        }

        //Keep track of which feed we're in along the way
        if ($lastfeedid != $feedid) {
            $fcount++;
            $icount = 0;
            $lastfeedid = $feedid;

            //Insert a new array that will contain the feed
            $river[$fcount] = array(
                'feedId' => $feedid,
                'feedUrl' => $feed['url'],
                'websiteUrl' => $feed['link'],
                'feedTitle' => $feed['title'],
                'feedDescription' => '',
                'itemIndex' => $ticount,
                'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
            );

            //Does this feed have an avatar url?
            if (!empty($feed['avatarurl'])) {
                $river[$fcount]['avatarUrl'] = $feed['avatarurl'];
            }

            //Start a sub-array in this feed array to hold items
            $river[$fcount]['item'] = array();

        }

        //Body text of item
        if (strlen($description) > 512) {
            $itembody = truncate_text($description, 512) . "...";
        } else {
            $itembody = $description;
        }

        //Fill in the details of this item
        $river[$fcount]['item'][$icount] = array(
            'index' => $ticount,
            'body' => $itembody,
            'permaLink' => $url,
            'pubDate' => date("D, d M Y H:i:s O", $timeadded),
            'title' => $title,
            'link' => $url,
            'id' => $id
        );

        //Is there an author attribution?
        if (!empty($author)) {
            $river[$fcount]['item'][$icount]['author'] = $author;
        }

        //Does this item specify a source attribution?
        if (!empty($sourceurl)) {
            $river[$fcount]['item'][$icount]['sourceurl'] = $sourceurl;
        }
        if (!empty($sourcetitle)) {
            $river[$fcount]['item'][$icount]['sourcetitle'] = $sourcetitle;
        }

        //Is there an origin?
        if (!empty($origin)) {
            $river[$fcount]['item'][$icount]['origin'] = $origin;
        } else {
            $river[$fcount]['item'][$icount]['origin'] = $feed['url'] . "|" . $guid;
        }

        //Are there any enclosures?
        $enclosures = unserialize($enclosure);
        if ($enclosures != FALSE) {
            if (!empty($enclosures)) {
                if (!empty($enclosures[0]['url'])) {
                    $river[$fcount]['item'][$icount]['enclosure'] = $enclosures;
                }
            }
        }

        //We're building two rivers here.  One for desktop and one for mobile
        if ($ticount <= $dmax) {
            $driver = $river;
            $drcount++;
        }
        if ($ticount <= $mmax) {
            $mriver = $river;
            $mrcount++;
        }

        $icount++;
        $ticount++;
    }

    $sql->close();

    //Encapsulate the river
    $doutput['updatedFeeds']['updatedFeed'] = $driver;
    $moutput['updatedFeeds']['updatedFeed'] = $mriver;

    //Add metadata
    $doutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );
    $moutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );

    //Json encode the river
    $djsonriver = "onGetRiverStream(" . json_encode($doutput) . ")";
    $mjsonriver = "onGetRiverStream(" . json_encode($moutput) . ")";

    //Let's return the river asked for
    $jsonriver = $mjsonriver;

    //If we can get some sane S3 credentials then let's go
    if ((s3_is_enabled($uid) || sys_s3_is_enabled()) && $prefs['publicriver'] == 1) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Subpath?  Must begin with a slash
        $subpath = "";

        //Put the json river file
        $filename = $default_public_river_json_file_name;
        $s3res = putInS3(gzencode($jsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename].");
            //loggit(3, "Could not create S3 file: [$filename].");
        } else {
            $s3url = get_s3_url($uid, $subpath, $filename);
            loggit(1, "Wrote server river json to S3 at url: [$s3url].");
        }

        //We always put the json file if the bucket is enabled, but only put the html stuff if
        //the riverfile value is non-blank
        if (!empty($prefs['pubriverfile'])) {
            //Construct the server river html file
            if (empty($prefs['pubrivertemplate'])) {
                $fh = fopen("$confroot/$templates/$cg_template_html_river", "r");
                $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_template_html_river"));
                fclose($fh);
            } else {
                $rftemplate = fetchUrl($prefs['pubrivertemplate']);
                if (is_outline($rftemplate)) {
                    $rftemplate = convert_opml_to_text($rftemplate);
                    $rftemplate = str_replace('<%opmlUrl%>', $prefs['pubrivertemplate'], $rftemplate);
                }
            }
            //Replace the tags
            $rftemplate = str_replace('<%title%>', $prefs['pubrivertitle'], $rftemplate);
            $rftemplate = str_replace('<%description%>', '', $rftemplate);
            $rftemplate = str_replace('<%jsonUrl%>', $s3url, $rftemplate);
            $rftemplate = str_replace('<%pleaseWaitMessage%>', 'Loading news...', $rftemplate);
            $rftemplate = str_replace('[RIVER_TITLE]', $prefs['pubrivertitle'], $rftemplate);
            $rftemplate = str_replace('[RIVER_JSON_URL]', $s3url, $rftemplate);
            $rftemplate = str_replace('[SCRIPT_JQUERY]', $cg_script_js_jquery, $rftemplate);
            $rftemplate = str_replace('[SCRIPT_JQTEMPLATES]', $cg_script_js_jqtemplates, $rftemplate);
            $rftemplate = str_replace('[DATE]', date("D, d M Y H:i:s"), $rftemplate);
            $rftemplate = str_replace('[SYS_NAME]', $system_name, $rftemplate);
            $rftemplate = str_replace('[SYS_VERSION]', $cg_sys_version, $rftemplate);
            $rftemplate = str_replace('[SYS_HOME]', $cg_producthome, $rftemplate);
            $sopmlurl = get_s3_url($uid, NULL, $default_social_outline_file_name);
            $rftemplate = str_replace('<%sopmlUrl%>', $sopmlurl, $rftemplate);
            $rftemplate = str_replace('[SOPML_URL]', $sopmlurl, $rftemplate);
            $osl = '';
            if ($prefs['opensubs'] == 1) {
                $osl = '<a href="' . rtrim($system_url, '/') . $opensubscribepage . '?u=' . $uid . '">Add a Feed</a>';
            }
            $rftemplate = str_replace('<%openSubsAddLink%>', $osl, $rftemplate);
            $rftemplate = str_replace('[OPENSUBS_ADD_LINK]', $osl, $rftemplate);

            //Put the html index
            $filename = $prefs['pubriverfile'];
            $s3res = putInS3(gzencode($rftemplate), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "text/html", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote server river html to S3 at url: [$s3url].");
            }

            //Put the support files
            $filename = $cg_template_css_river;
            $s3res = putFileInS3("$confroot/$templates/$cg_template_css_river", $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/css");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote server river style to S3 at url: [$s3url].");
            }
            $filename = $cg_script_js_jquery;
            $s3res = putFileInS3("$confroot/$scripts/$cg_script_js_jquery", $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/javascript");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote jquery script to S3 at url: [$s3url].");
            }
            $filename = $cg_script_js_jqtemplates;
            $s3res = putFileInS3("$confroot/$scripts/$cg_script_js_jqtemplates", $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/javascript");
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote jquery templates script to S3 at url: [$s3url].");
            }
        }
    }

    loggit(3, "Returning: [$drcount] items in public river.");
    loggit(3, "Returning: [$mrcount] items in public river.");
    return ($jsonriver);
}


//Retrieve all the items from a particular feed
function get_items_by_feed_id($fid = NULL, $max = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id given is corrupt or blank: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $sqltxt = "SELECT id,
                    url,
                    description,
                    timestamp,
                    enclosure,
                    title,
		    sourceurl,
		    sourcetitle
	     FROM $table_nfitem
	     WHERE feedid=?
             ORDER BY timestamp ASC";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT $default_max_list";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for: [$fid].");
        return (array());
    }

    $sql->bind_result($aid,
        $aurl,
        $adescription,
        $atimestamp,
        $aenclosure,
        $atitle,
        $asourceurl,
        $asourcetitle
    ) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[$count] = array('id' => $aid,
            'url' => $aurl,
            'title' => $atitle,
            'description' => $adescription,
            'timestamp' => $atimestamp,
            'enclosure' => $aenclosure,
            'sourceurl' => $asourceurl,
            'sourcetitle' => $asourcetitle
        );
        $count++;
    }

    $sql->close();

    //loggit(3, print_r($items, TRUE));

    loggit(1, "Returning: [$count] items for feed: [$fid]");
    return ($items);
}


//Build a json array of feed items that will be the river for this user
function build_river_json2($uid = NULL, $max = NULL, $force = FALSE, $mobile = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/opml.php";

    loggit(1, "DEBUG: build_river_json2($uid, $max, $force, $mobile)");

    //Get the users prefs
    $prefs = get_user_prefs($uid);
    if (!$prefs) {
        loggit(2, "Couldn't get prefs for user: [$uid]");
        return (FALSE);
    }

    //For a good collapsed river, you really need at least 24 hours of data to work with
    //If the user has their riverhours set below 24, we just use 24 the non-collapsed
    //river is unaffected by this
    if ($prefs['riverhours'] < 24) {
        $start = time() - (24 * 3600);
    } else {
        $start = time() - ($prefs['riverhours'] * 3600);
    }
    $dmax = $prefs['maxriversize'];
    $mmax = $prefs['maxriversizemobile'];

    //The river array
    $river = array();
    $driver = array();
    $mriver = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Assemble query
    $sqltxt = "SELECT $table_nfitem.id,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.origin,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
                    $table_nfitem.author,
                    $table_nfitemprop.sticky,
                    $table_nfcatalog.sticky,
                    $table_nfitemprop.hidden,
                    $table_nfcatalog.hidden,
                    $table_nfitemprop.`fulltext`,
                    $table_nfcatalog.`fulltext`
             FROM $table_nfitem
             LEFT OUTER JOIN $table_nfitemprop ON $table_nfitemprop.itemid = $table_nfitem.id AND $table_nfitemprop.userid=?
             INNER JOIN $table_nfcatalog ON $table_nfcatalog.feedid = $table_nfitem.feedid
             WHERE $table_nfcatalog.userid=?
             AND $table_nfitem.timeadded > ?
             AND $table_nfitem.`old` = 0";
    $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";
    //loggit(3, $sqltxt);

    //Make sure to set the LIMIT to the higher of the two max values, so we cover both
    if ($max == NULL) {
        $max = $dmax;
        if ($mmax > $dmax) {
            $max = $mmax;
        }
    }
    //$sqltxt .= " LIMIT $max";

    //Execute the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssd", $uid, $uid, $start) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    $nr = $sql->num_rows();
    if ( $nr < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The user: [$uid] has an empty river.");
        return (FALSE);
    } else {
        loggit(3, "$nr rows returned for user: [$uid]'s river.");
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid,
        $timeadded, $enclosure, $description,
        $guid, $origin, $sourceurl, $sourcetitle,
        $author, $sticky, $fsticky, $hidden,
        $fhidden, $fulltext, $ffulltext) or loggit(2, "MySql error: " . $dbh->error);


    // ----- Begin building the river. -----
    $origins = array();
    $forigins = array();
    $fcount = -1;
    $icount = 0;
    $ticount = 0;
    $drcount = 0;
    $mrcount = 0;
    $firstid = "";
    $lastfeedid = "";
    $pubdate = time();
    while ($sql->fetch()) {
        //If this feed is hidden skip
        if( $fhidden == 1 ) continue;

        $feed = get_feed_info($feedid);

        //Save the time stamp of the first item to use as a pubdate
        if ($firstid == "" && $sticky != 1 && $hidden != 1) {
            $pubdate = $timeadded;
            $firstid = $id;
        }

        //Keep track of which feed we're in along the way
        if ($lastfeedid != $feedid) {
            //If the last feed was blank then remove it from the array
            if ($fcount >= 0 && empty($river[$fcount]['item'])) {
                array_splice($river, $fcount);
            } else {
                $fcount++;
            }
            $icount = 0;
            $lastfeedid = $feedid;

            //Insert a new array that will contain the feed
            $newfeed = array(
                'feedId' => $feedid,
                'feedIndex' => $fcount,
                'feedUrl' => $feed['url'],
                'websiteUrl' => $feed['link'],
                'feedTitle' => $feed['title'],
                'feedDescription' => '',
                'feedSticky' => $fsticky,
                'feedHidden' => $fhidden,
                'feedFullText' => $ffulltext,
                'itemIndex' => $ticount,
                'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
            );

            //Check if this feed is linked to an outline this user subscribes to
            $oid = get_feed_outline_by_user($feedid, $uid);
            if ($oid != FALSE) {
                $ou = get_outline_info($oid);
                $newfeed['linkedOutlineId'] = $oid;
                if (!empty($ou['type'])) {
                    $newfeed['linkedOutlineType'] = $ou['type'];
                }
                if (!empty($ou['title'])) {
                    $newfeed['linkedOutlineTitle'] = $ou['title'];
                }
                if (!empty($ou['url'])) {
                    $newfeed['linkedOutlineUrl'] = $ou['url'];
                }
                if (!empty($ou['ownername'])) {
                    $newfeed['ownerName'] = $ou['ownername'];
                }
                if (!empty($ou['avatarurl'])) {
                    $newfeed['avatarUrl'] = $ou['avatarurl'];
                }
            }

            //Does this feed have an avatar url?
            if (!empty($feed['avatarurl'])) {
                $newfeed['avatarUrl'] = $feed['avatarurl'];
            }

            //Start a sub-array in this feed array to hold items
            $newfeed['item'] = array();
            $river[$fcount] = $newfeed;
        }
        // ----- End Feed section -----


        // ----- Start Item section -----
        //Construct item body
        if ($prefs['fulltextriver'] == 0) {
            if ($ffulltext == 1) {
                $itembody = $description;
            } else
                if (strlen($description) > $cg_default_max_river_item_body_length) {
                    $itembody = truncate_text($description, $cg_default_max_river_item_body_length) . "...";
                } else {
                    $itembody = $description;
                }
        } else {
            $itembody = $description;
        }

        //Fill in the details of this item
        $newitem = array(
            'index' => $ticount,
            'body' => $itembody,
            'permaLink' => $url,
            'guid' => $guid,
            'pubDate' => date("D, d M Y H:i:s O", $timeadded),
            'title' => $title,
            'link' => $url,
            'id' => $id
        );

        //loggit(3, "TITLE: $title");

        //Is there an author attribution?
        if (!empty($author)) {
            $newitem['author'] = $author;
        }

        //Does this item specify a source attribution?
        if (!empty($sourceurl)) {
            $newitem['sourceurl'] = $sourceurl;
        }
        if (!empty($sourcetitle)) {
            $newitem['sourcetitle'] = $sourcetitle;
        }

        //Set the sticky bit
        if ($sticky == 1) {
            $newitem['sticky'] = 1;
        }

        //Set the hidden bit
        if ($hidden == 1) {
            $newitem['hidden'] = 1;
        }

        //Set the full text bit
        if ($ffulltext == 1) {
            $newitem['fullText'] = 1;
        }

        //Is there an origin?
        if (!empty($origin)) {
            $newitem['origin'] = $origin;
        } else {
            $newitem['origin'] = $feed['url'] . "|" . $guid;
        }

        //Are there any enclosures?
        $enclosures = unserialize($enclosure);
        if ($enclosures != FALSE) {
            if (!empty($enclosures)) {
                if (!empty($enclosures[0]['url'])) {
                    $newitem['enclosure'] = $enclosures;
                }
            }
        }

        //We base where to stick the item on the origin.  If the origin of this new item
        //is the same as the origin of an existing item we've already put into the river
        //then we insert this new item as a sub-item of that existing one.
        //If we haven't seen this origin before, then we just put it in as a new standard
        //river item.
        $floc = isset($forigins[$newitem['origin']]['feedindex']) ? $forigins[$newitem['origin']]['feedindex'] : FALSE;

        //echo "DEBUG: [$fcount] [".$newitem['origin']."]\n";

        if ($floc !== FALSE) {
            $iloc = $forigins[(string)$newitem['origin']]['itemindex'];
            $newitem['index'] = @count($river[$floc]['item'][$iloc]['subitem']);
            $newitem['avatarUrl'] = @$newfeed['avatarUrl'];
            $newitem['feedTitle'] = @$newfeed['feedTitle'];
            $river[$floc]['item'][$iloc]['subitem'][] = $newitem;
        } else {
            $river[$fcount]['item'][$icount] = $newitem;
            $forigins[(string)$newitem['origin']]['feedindex'] = $fcount;
            $forigins[(string)$newitem['origin']]['itemindex'] = $icount;
            $icount++;
        }
        // ----- End Item Section -----

        $ticount++;
        if ($ticount == $dmax) {
            $dfcut = $fcount;
        }
        if ($ticount == $mmax) {
            $mfcut = $fcount;
        }

        //We're building two rivers here.  One for desktop and one for mobile
        //if( $ticount <= $dmax ) {  $driver = $river; $drcount++;  }
        //if( $ticount <= $mmax ) {  $mriver = $river; $mrcount++;  }

        //Break out if we hit max
        //if( $ticount >= $max ) { break; }
    }

    if (isset($dfcut)) {
        $driver = array_slice($river, 0, $dfcut);
    } else {
        $driver = $river;
    }
    if (isset($mfcut)) {
        $mriver = array_slice($river, 0, $mfcut);
    } else {
        $mriver = $river;
    }

    $sql->close();

    //Debugging
    //echo "-- forigins --------------------------\n";
    //echo print_r($forigins, TRUE)."\n";
    //echo "-- origins ---------------------------\n";
    //echo print_r($origins, TRUE)."\n";
    //echo "--------------------------------------\n";

    //Encapsulate the river
    $doutput['updatedFeeds']['updatedFeed'] = $driver;
    $moutput['updatedFeeds']['updatedFeed'] = $mriver;

    //Add metadata
    $doutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );
    $moutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );

    //Json encode the river
    $djsonriver = "onGetRiverStream(" . json_encode($doutput) . ")";
    $mjsonriver = "onGetRiverStream(" . json_encode($moutput) . ")";

    //Let's return the river asked for
    $jsonriver = $djsonriver;
    if ($mobile == TRUE) {
        $jsonriver = $mjsonriver;
    }

    //Let's be smart about this and not re-publish a river that hasn't changed
    $pubriver = get_river_info($uid);
    $newhash = md5(serialize($driver));

    //loggit(3, "River hash: OLD: [".$pubriver['conthash']."]");
    //loggit(3, "River hash: NEW: [$newhash]");
    //loggit(3, "River XML: ".print_r($driver, TRUE));
    //loggit(3, "River JSON: ".$djsonriver);

    if (!empty($pubriver) && ($pubriver['firstid'] == $firstid && $force == FALSE) && ($pubriver['conthash'] == $newhash)) {
        //loggit(3, "User: [$uid]'s river has not changed. No need to publish.");
        return ($jsonriver);
    }

    //Put this built river in the database
    update_river($uid, $doutput, $moutput, $newhash);

    //If we can get some sane S3 credentials, and the user wants a public river then let's go
    if ($prefs['publicriver'] == 1) {
        if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
            //First we get all the key info
            $s3info = get_s3_info($uid);

            //Subpath?  Must begin with a slash
            $subpath = "";

            //Put the desktop file
            $filename = $default_river_json_file_name;
            $s3res = putInS3(gzencode($djsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$uid].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote desktop river to S3 at url: [$s3url].");
            }
            //Put the mobile file
            $filename = $default_river_json_mobile_file_name;
            $s3res = putInS3(gzencode($mjsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote mobile river to S3 at url: [$s3url].");
            }
        }
    } else {
        loggit(1, "Skipping S3 upload of river json since user wants it private.");
    }

    loggit(1, "Returning: [$drcount] items in user: [$uid]'s desktop river.");
    loggit(1, "Returning: [$mrcount] items in user: [$uid]'s mobile river.");
    return ($jsonriver);
}


//Build a json array of feed items that will be the river for this user
function build_river_json3($uid = NULL, $max = NULL, $force = FALSE, $mobile = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/opml.php";

    loggit(1, "DEBUG: build_river_json3($uid, $max, $force, $mobile)");

    //Get the users prefs
    $prefs = get_user_prefs($uid);
    if (!$prefs) {
        loggit(2, "Couldn't get prefs for user: [$uid]");
        return (FALSE);
    }

    //For a good collapsed river, you really need at least 24 hours of data to work with
    //If the user has their riverhours set below 24, we just use 24 the non-collapsed
    //river is unaffected by this
    if ($prefs['riverhours'] < 24) {
        $start = time() - (24 * 3600);
    } else {
        $start = time() - ($prefs['riverhours'] * 3600);
    }
    $dmax = $prefs['maxriversize'];
    $mmax = $prefs['maxriversizemobile'];

    //The river array
    $river = array();
    $driver = array();
    $mriver = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Assemble query
    $sqltxt = "SELECT
                    $table_nfitemprop.itemid,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.origin,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
                    $table_nfitem.author,
                    $table_nfitemprop.sticky,
                    $table_nfitemprop.hidden,
                    $table_nfitemprop.`fulltext`
               FROM
                    $table_nfitemprop
               INNER JOIN
                    $table_nfitem ON $table_nfitem.id = $table_nfitemprop.itemid
               WHERE
                    $table_nfitemprop.userid=?
               AND
                    $table_nfitem.timeadded > ?
               AND
                    $table_nfitem.`old` = 0
    ";
    $sqltxt .= " ORDER BY $table_nfitemprop.itemid DESC";
    //loggit(3, $sqltxt);

    //Make sure to set the LIMIT to the higher of the two max values, so we cover both
    if ($max == NULL) {
        $max = $dmax;
        if ($mmax > $dmax) {
            $max = $mmax;
        }
    }
    //$sqltxt .= " LIMIT $max";

    //Execute the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sd", $uid, $start) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The user: [$uid] has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid,
        $timeadded, $enclosure, $description,
        $guid, $origin, $sourceurl, $sourcetitle,
        $author, $sticky, $hidden, $fulltext) or loggit(2, "MySql error: " . $dbh->error);


    // ----- Begin building the river. -----
    $origins = array();
    $forigins = array();
    $fcount = -1;
    $icount = 0;
    $ticount = 0;
    $drcount = 0;
    $mrcount = 0;
    $firstid = "";
    $lastfeedid = "";
    $pubdate = time();
    while ($sql->fetch()) {
        $feed = get_feed_info($feedid);

        //Save the time stamp of the first item to use as a pubdate
        if ($firstid == "" && $sticky != 1 && $hidden != 1) {
            $pubdate = $timeadded;
            $firstid = $id;
        }

        //Keep track of which feed we're in along the way
        if ($lastfeedid != $feedid) {
            //If the last feed was blank then remove it from the array
            if ($fcount >= 0 && empty($river[$fcount]['item'])) {
                array_splice($river, $fcount);
            } else {
                $fcount++;
            }
            $icount = 0;
            $lastfeedid = $feedid;

            //Insert a new array that will contain the feed
            $newfeed = array(
                'feedId' => $feedid,
                'feedIndex' => $fcount,
                'feedUrl' => $feed['url'],
                'websiteUrl' => $feed['link'],
                'feedTitle' => $feed['title'],
                'feedDescription' => '',
                'feedSticky' => 0,
                'feedHidden' => 0,
                'feedFullText' => 0,
                'itemIndex' => $ticount,
                'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
            );

            //Check if this feed is linked to an outline this user subscribes to
            $oid = get_feed_outline_by_user($feedid, $uid);
            if ($oid != FALSE) {
                $ou = get_outline_info($oid);
                $newfeed['linkedOutlineId'] = $oid;
                if (!empty($ou['type'])) {
                    $newfeed['linkedOutlineType'] = $ou['type'];
                }
                if (!empty($ou['title'])) {
                    $newfeed['linkedOutlineTitle'] = $ou['title'];
                }
                if (!empty($ou['url'])) {
                    $newfeed['linkedOutlineUrl'] = $ou['url'];
                }
                if (!empty($ou['ownername'])) {
                    $newfeed['ownerName'] = $ou['ownername'];
                }
                if (!empty($ou['avatarurl'])) {
                    $newfeed['avatarUrl'] = $ou['avatarurl'];
                }
            }

            //Does this feed have an avatar url?
            if (!empty($feed['avatarurl'])) {
                $newfeed['avatarUrl'] = $feed['avatarurl'];
            }

            //Start a sub-array in this feed array to hold items
            $newfeed['item'] = array();
            $river[$fcount] = $newfeed;
        }
        // ----- End Feed section -----


        // ----- Start Item section -----
        //Construct item body
        if ($prefs['fulltextriver'] == 0) {
            if ($fulltext == 1) {
                $itembody = $description;
            } else
                if (strlen($description) > 300) {
                    $itembody = truncate_text($description, 300) . "...";
                } else {
                    $itembody = $description;
                }
        } else {
            $itembody = $description;
        }

        //Fill in the details of this item
        $newitem = array(
            'index' => $ticount,
            'body' => $itembody,
            'permaLink' => $url,
            'guid' => $guid,
            'pubDate' => date("D, d M Y H:i:s O", $timeadded),
            'title' => $title,
            'link' => $url,
            'id' => $id
        );

        //Is there an author attribution?
        if (!empty($author)) {
            $newitem['author'] = $author;
        }

        //Does this item specify a source attribution?
        if (!empty($sourceurl)) {
            $newitem['sourceurl'] = $sourceurl;
        }
        if (!empty($sourcetitle)) {
            $newitem['sourcetitle'] = $sourcetitle;
        }

        //Set the sticky bit
        if ($sticky == 1) {
            $newitem['sticky'] = 1;
        }

        //Set the hidden bit
        if ($hidden == 1) {
            $newitem['hidden'] = 1;
        }

        //Set the full text bit
        if ($fulltext == 1) {
            $newitem['fullText'] = 1;
        }

        //Is there an origin?
        if (!empty($origin)) {
            $newitem['origin'] = $origin;
        } else {
            $newitem['origin'] = $feed['url'] . "|" . $guid;
        }

        //Are there any enclosures?
        $enclosures = unserialize($enclosure);
        if ($enclosures != FALSE) {
            if (!empty($enclosures)) {
                if (!empty($enclosures[0]['url'])) {
                    $newitem['enclosure'] = $enclosures;
                }
            }
        }

        //We base where to stick the item on the origin.  If the origin of this new item
        //is the same as the origin of an existing item we've already put into the river
        //then we insert this new item as a sub-item of that existing one.
        //If we haven't seen this origin before, then we just put it in as a new standard
        //river item.
        $floc = isset($forigins[$newitem['origin']]['feedindex']) ? $forigins[$newitem['origin']]['feedindex'] : FALSE;

        //echo "DEBUG: [$fcount] [".$newitem['origin']."]\n";

        if ($floc !== FALSE) {
            $iloc = $forigins[(string)$newitem['origin']]['itemindex'];
            $newitem['index'] = @count($river[$floc]['item'][$iloc]['subitem']);
            $newitem['avatarUrl'] = @$newfeed['avatarUrl'];
            $newitem['feedTitle'] = @$newfeed['feedTitle'];
            $river[$floc]['item'][$iloc]['subitem'][] = $newitem;
        } else {
            $river[$fcount]['item'][$icount] = $newitem;
            $forigins[(string)$newitem['origin']]['feedindex'] = $fcount;
            $forigins[(string)$newitem['origin']]['itemindex'] = $icount;
            $icount++;
        }
        // ----- End Item Section -----

        $ticount++;
        if ($ticount == $dmax) {
            $dfcut = $fcount;
        }
        if ($ticount == $mmax) {
            $mfcut = $fcount;
        }

        //We're building two rivers here.  One for desktop and one for mobile
        //if( $ticount <= $dmax ) {  $driver = $river; $drcount++;  }
        //if( $ticount <= $mmax ) {  $mriver = $river; $mrcount++;  }

        //Break out if we hit max
        //if( $ticount >= $max ) { break; }
    }

    if (isset($dfcut)) {
        $driver = array_slice($river, 0, $dfcut);
    } else {
        $driver = $river;
    }
    if (isset($mfcut)) {
        $mriver = array_slice($river, 0, $mfcut);
    } else {
        $mriver = $river;
    }

    $sql->close();

    //Debugging
    //echo "-- forigins --------------------------\n";
    //echo print_r($forigins, TRUE)."\n";
    //echo "-- origins ---------------------------\n";
    //echo print_r($origins, TRUE)."\n";
    //echo "--------------------------------------\n";

    //Encapsulate the river
    $doutput['updatedFeeds']['updatedFeed'] = $driver;
    $moutput['updatedFeeds']['updatedFeed'] = $mriver;

    //Add metadata
    $doutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );
    $moutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );

    //Json encode the river
    $djsonriver = "onGetRiverStream(" . json_encode($doutput) . ")";
    $mjsonriver = "onGetRiverStream(" . json_encode($moutput) . ")";

    //Let's return the river asked for
    $jsonriver = $djsonriver;
    if ($mobile == TRUE) {
        $jsonriver = $mjsonriver;
    }

    //Let's be smart about this and not re-publish a river that hasn't changed
    $pubriver = get_river_info($uid);
    $newhash = md5(serialize($driver));

    //loggit(3, "River hash: OLD: [".$pubriver['conthash']."]");
    //loggit(3, "River hash: NEW: [$newhash]");

    if (!empty($pubriver) && ($pubriver['firstid'] == $firstid && $force == FALSE) && ($pubriver['conthash'] == $newhash)) {
        //loggit(3, "User: [$uid]'s river has not changed. No need to publish.");
        return ($jsonriver);
    }

    //Put this built river in the database
    update_river($uid, $doutput, $moutput, $newhash);

    //If we can get some sane S3 credentials, and the user wants a public river then let's go
    if ($prefs['publicriver'] == 1) {
        if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
            //First we get all the key info
            $s3info = get_s3_info($uid);

            //Subpath?  Must begin with a slash
            $subpath = "";

            //Put the desktop file
            $filename = $default_river_json_file_name;
            $s3res = putInS3(gzencode($djsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote desktop river to S3 at url: [$s3url].");
            }
            //Put the mobile file
            $filename = $default_river_json_mobile_file_name;
            $s3res = putInS3(gzencode($mjsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote mobile river to S3 at url: [$s3url].");
            }
        }
    } else {
        loggit(1, "Skipping S3 upload of river json since user wants it private.");
    }

    loggit(1, "Returning: [$drcount] items in user: [$uid]'s desktop river.");
    loggit(1, "Returning: [$mrcount] items in user: [$uid]'s mobile river.");
    return ($jsonriver);
}


//Build a json array of feed items that will be the river for this user
function build_river_json4($uid = NULL, $max = NULL, $force = FALSE, $mobile = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/opml.php";

    //Get the users prefs
    $prefs = get_user_prefs($uid);
    if (!$prefs) {
        loggit(2, "Couldn't get prefs for user: [$uid]");
        return (FALSE);
    }
    $start = time() - ($prefs['riverhours'] * 3600);
    $dmax = $prefs['maxriversize'];
    $mmax = $prefs['maxriversizemobile'];

    //The river array
    $river = array();
    $driver = array();
    $mriver = array();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Assemble query
    $sqltxt = "SELECT
                    $table_nfitemprop.itemid,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.origin,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
                    $table_nfitem.author,
                    $table_nfitemprop.sticky,
                    $table_nfitemprop.hidden,
                    $table_nfitemprop.`fulltext`
               FROM
                    $table_nfitemprop
               INNER JOIN
                    $table_nfitem ON $table_nfitem.id = $table_nfitemprop.itemid
               WHERE
                    $table_nfitemprop.userid=?
               AND
                    $table_nfitem.timeadded > ?
               AND
                    $table_nfitem.`old` = 0";
    //loggit(3, $sqltxt);

    //Make sure to set the LIMIT to the higher of the two max values, so we cover both
    if ($max == NULL) {
        $max = $dmax;
        if ($mmax > $dmax) {
            $max = $mmax;
        }
    }
    $sqltxt .= " LIMIT $max";

    //Execute the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sd", $uid, $start) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The user: [$uid] has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid,
        $timeadded, $enclosure, $description,
        $guid, $origin, $sourceurl, $sourcetitle,
        $author, $sticky, $hidden,
        $fulltext) or loggit(2, "MySql error: " . $dbh->error);

    $fcount = -1;
    $icount = 0;
    $ticount = 0;
    $drcount = 0;
    $mrcount = 0;
    $firstid = "";
    $lastfeedid = "";
    $pubdate = time();
    while ($sql->fetch()) {
        $feed = get_feed_info($feedid);

        //Save the time stamp of the first item to use as a pubdate
        if ($firstid == "" && $sticky != 1 && $hidden != 1) {
            $pubdate = $timeadded;
            $firstid = $id;
        }

        //Keep track of which feed we're in along the way
        if ($lastfeedid != $feedid) {
            $fcount++;
            $icount = 0;
            $lastfeedid = $feedid;

            //Insert a new array that will contain the feed
            $river[$fcount] = array(
                'feedId' => $feedid,
                'feedUrl' => $feed['url'],
                'websiteUrl' => $feed['link'],
                'feedTitle' => $feed['title'],
                'feedDescription' => '',
                'feedSticky' => $sticky,
                'feedHidden' => $hidden,
                'feedFullText' => $fulltext,
                'itemIndex' => $ticount,
                'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
            );

            //Check if this feed is linked to an outline this user subscribes to
            $oid = get_feed_outline_by_user($feedid, $uid);
            if ($oid != FALSE) {
                $ou = get_outline_info($oid);
                $river[$fcount]['linkedOutlineId'] = $oid;
                if (!empty($ou['type'])) {
                    $river[$fcount]['linkedOutlineType'] = $ou['type'];
                }
                if (!empty($ou['title'])) {
                    $river[$fcount]['linkedOutlineTitle'] = $ou['title'];
                }
                if (!empty($ou['url'])) {
                    $river[$fcount]['linkedOutlineUrl'] = $ou['url'];
                }
                if (!empty($ou['ownername'])) {
                    $river[$fcount]['ownerName'] = $ou['ownername'];
                }
                if (!empty($ou['avatarurl'])) {
                    $river[$fcount]['avatarUrl'] = $ou['avatarurl'];
                }
            }

            //Does this feed have an avatar url?
            if (!empty($feed['avatarurl'])) {
                $river[$fcount]['avatarUrl'] = $feed['avatarurl'];
            }

            //Start a sub-array in this feed array to hold items
            $river[$fcount]['item'] = array();

        }

        //Construct item body
        if ($prefs['fulltextriver'] == 0) {
            if ($fulltext == 1) {
                $itembody = $description;
            } else
                if (strlen($description) > 300) {
                    $itembody = truncate_text($description, 300) . "...";
                } else {
                    $itembody = $description;
                }
        } else {
            $itembody = $description;
        }

        //Fill in the details of this item
        $river[$fcount]['item'][$icount] = array(
            'index' => $ticount,
            'body' => $itembody,
            'permaLink' => $url,
            'guid' => $guid,
            'pubDate' => date("D, d M Y H:i:s O", $timeadded),
            'title' => $title,
            'link' => $url,
            'id' => $id
        );

        //Is there an author attribution?
        if (!empty($author)) {
            $river[$fcount]['item'][$icount]['author'] = $author;
        }

        //Does this item specify a source attribution?
        if (!empty($sourceurl)) {
            $river[$fcount]['item'][$icount]['sourceurl'] = $sourceurl;
        }
        if (!empty($sourcetitle)) {
            $river[$fcount]['item'][$icount]['sourcetitle'] = $sourcetitle;
        }

        //Is there an origin?
        if (!empty($origin)) {
            $river[$fcount]['item'][$icount]['origin'] = $origin;
        } else {
            $river[$fcount]['item'][$icount]['origin'] = $feed['url'] . "|" . $guid;
        }

        //Set the sticky bit
        if ($sticky == 1) {
            $river[$fcount]['item'][$icount]['sticky'] = 1;
        }

        //Set the hidden bit
        if ($hidden == 1) {
            $river[$fcount]['item'][$icount]['hidden'] = 1;
        }

        //Set the full text bit
        if ($fulltext == 1) {
            $river[$fcount]['item'][$icount]['fullText'] = 1;
        }

        //Are there any enclosures?
        $enclosures = unserialize($enclosure);
        if ($enclosures != FALSE) {
            if (!empty($enclosures)) {
                if (!empty($enclosures[0]['url'])) {
                    $river[$fcount]['item'][$icount]['enclosure'] = $enclosures;
                }
            }
        }

        //We're building two rivers here.  One for desktop and one for mobile
        if ($ticount <= $dmax) {
            $driver = $river;
            $drcount++;
        }
        if ($ticount <= $mmax) {
            $mriver = $river;
            $mrcount++;
        }

        $icount++;
        $ticount++;
    }

    $sql->close();

    //Encapsulate the river
    $doutput['updatedFeeds']['updatedFeed'] = $driver;
    $moutput['updatedFeeds']['updatedFeed'] = $mriver;

    //Add metadata
    $doutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );
    $moutput['metadata'] = array(
        "docs" => "http://scripting.com/stories/2010/12/06/innovationRiverOfNewsInJso.html",
        "whenGMT" => date("D, d M Y H:i:s O", $pubdate),
        "whenLocal" => date("D, d M Y H:i:s O", $pubdate),
        "version" => "3",
        "secs" => "1",
        "firstId" => $firstid,
        "lastBuildDate" => time()
    );

    //Json encode the river
    $djsonriver = "onGetRiverStream(" . json_encode($doutput) . ")";
    $mjsonriver = "onGetRiverStream(" . json_encode($moutput) . ")";

    //Let's return the river asked for
    $jsonriver = $djsonriver;
    if ($mobile == TRUE) {
        $jsonriver = $mjsonriver;
    }

    //Let's be smart about this and not re-publish a river that hasn't changed
    $pubriver = get_river_info($uid);
    $newhash = md5(serialize($driver));

    //loggit(3, "River hash: OLD: [".$pubriver['conthash']."]");
    //loggit(3, "River hash: NEW: [$newhash]");

    if (!empty($pubriver) && ($pubriver['firstid'] == $firstid && $force == FALSE) && ($pubriver['conthash'] == $newhash)) {
        loggit(1, "User: [$uid]'s river has not changed. No need to publish.");
        return ($jsonriver);
    }

    //Put this built river in the database
    update_river($uid, $doutput, $moutput, $newhash);

    //If we can get some sane S3 credentials and the user wants a public river then let's go
    if ($prefs['publicriver'] == 1) {
        if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
            //First we get all the key info
            $s3info = get_s3_info($uid);

            //Subpath?  Must begin with a slash
            $subpath = "";

            //Put the desktop file
            $filename = $default_river_json_file_name;
            $s3res = putInS3(gzencode($djsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote desktop river to S3 at url: [$s3url].");
            }
            //Put the mobile file
            $filename = $default_river_json_mobile_file_name;
            $s3res = putInS3(gzencode($mjsonriver), $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], array("Content-Type" => "application/javascript", "Content-Encoding" => "gzip"));
            if (!$s3res) {
                loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
                //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
            } else {
                $s3url = get_s3_url($uid, $subpath, $filename);
                loggit(1, "Wrote mobile river to S3 at url: [$s3url].");
            }
        }
    } else {
        loggit(1, "Skipping S3 upload of river json since user wants it private.");
    }

    loggit(1, "Returning: [$drcount] items in user: [$uid]'s desktop river.");
    loggit(1, "Returning: [$mrcount] items in user: [$uid]'s mobile river.");
    return ($jsonriver);
}


//Collapses a river structure based on origin value
function collapse_river($river)
{
    //Check parameters
    if ($river == NULL) {
        loggit(2, "The river array given is corrupt or blank: [$river]");
        return (FALSE);
    }

    //We will incrementally build a new river
    $nrindex = 0;
    $nritemindex = 0;
    $feeds = $river['updatedFeeds']['updatedFeed'];
    $newriver = "";

    //Go through each item
    foreach ($feeds as $feed) {
        foreach ($feed['item'] as $item) {
            $index = $item['index'];
            if (isset($item['origin'])) {

                $result = array_search_ext($newriver, $item['origin']);
                if ($result === FALSE) {
                    //...
                } else {
                    echo print_r($result, TRUE) . "\n";
                }

            }

        }
    }


    return ($newriver);
}


//Retrieve the feed items that have media in them
function get_feed_items_with_enclosures($uid = NULL, $tstart = NULL, $max = NULL, $start = NULL, $typefilter = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Set up max limit
    if (empty($max)) {
        $max = $default_max_list;
    }

    //Set up start time
    if (empty($tstart)) {
        $tstart = time() - 86400;
    }

    //Run the query
    $subsql = "SELECT * FROM $table_nfitem WHERE media = 1 AND `old` = 0 AND timeadded > ? ORDER BY timeadded DESC";
    $sqltxt = "SELECT id,
                    url,
                    description,
                    timestamp,
                    timeadded,
                    enclosure,
                    title,
                    sourceurl,
                    sourcetitle,
                    origin,
                    author
             FROM ($subsql) AS tsub
             LEFT JOIN $table_nfcatalog ON $table_nfcatalog.feedid = tsub.feedid
             WHERE $table_nfcatalog.userid=?
	     LIMIT ?,?";


    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("dsdd", $tstart, $uid, $start, $max) or loggit(2, $sql->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for: [$fid].");
        return (array());
    }

    $sql->bind_result($aid,
        $aurl,
        $adescription,
        $atimestamp,
        $atimeadded,
        $aenclosure,
        $atitle,
        $asourceurl,
        $asourcetitle,
        $aorigin,
        $aauthor
    ) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $enc = unserialize($aenclosure);
        foreach( $enc as $enclosure ) {
            if($typefilter == NULL || stripos($enclosure['url'], $typefilter) !== FALSE) {
                $items[$count] = array(
                    'id' => $aid,
                    'url' => $aurl,
                    'title' => $atitle,
                    'description' => $adescription,
                    'timestamp' => $atimestamp,
                    'timeadded' => $atimeadded,
                    'enclosure' => $enclosure,
                    'sourceurl' => $asourceurl,
                    'sourcetitle' => $asourcetitle,
                    'origin' => $aorigin,
                    'author' => $aauthor
                );
                $count++;
            }
        }
    }

    $sql->close();

    //loggit(3, print_r($items, TRUE));

    loggit(1, "Returning: [$count] items.");
    return ($items);
}


//Retrieve the feed items that have media in them
function get_feed_items_with_enclosures2($uid = NULL, $tstart = NULL, $max = NULL, $start = NULL, $type = NULL, $noduplicates = FALSE)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Set up max limit
    if (empty($max)) {
        $max = $default_max_list;
    }

    //Set up start time
    if (empty($tstart)) {
        $tstart = time() - 86400;
    }

    //Run the query
    $sqltxt = "SELECT enc.id,
                      enc.iid,
                      enc.url,
                      enc.mimetype,
                      enc.length,
                      enc.time,
                      enc.type,
                      enc.marker,
                      nfi.title,
                      nfi.sourceurl,
                      nfi.sourcetitle,
                      nfi.origin,
                      nfi.author
             FROM $table_nfenclosures AS enc
             LEFT JOIN $table_nfitem AS nfi ON nfi.id = enc.iid
             INNER JOIN $table_nfcatalog AS nfc ON nfi.feedid = nfc.feedid AND nfc.userid = ?
    ";

    //Was a type requested
    if (!empty($type)) {
        $sqltxt .= "WHERE enc.type = ?
        ";
    }

    $sqltxt .= "ORDER BY enc.time DESC LIMIT ?,?";



    loggit(3, "DEBUG_SQL:[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    //Was a type requested
    if (!empty($type)) {
        $sql->bind_param("sddd", $uid, $type, $start, $max) or loggit(2, $sql->error);
    } else {
        $sql->bind_param("sdd", $uid, $start, $max) or loggit(2, $sql->error);
    }
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for: [$fid].");
        return (array());
    }

    $sql->bind_result(
        $aid,
        $aiid,
        $aurl,
        $amimetype,
        $alength,
        $atime,
        $atype,
        $amarker,
        $atitle,
        $asourceurl,
        $asourcetitle,
        $aorigin,
        $aauthor
    ) or loggit(2, "MySql error: " . $dbh->error);

    $urls = array();
    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        if($noduplicates && in_array($aurl, $urls)) continue;

        $items[$count] = array(
            'id' => $aiid,
            'url' => $aurl,
            'mimetype' => $amimetype,
            'length' => $alength,
            'title' => $atitle,
            'type' => $atype,
            'timestamp' => $atime,
            'timeadded' => $atime,
            'sourceurl' => $asourceurl,
            'sourcetitle' => $asourcetitle,
            'origin' => $aorigin,
            'author' => $aauthor
        );
        $count++;

        if($noduplicates) $urls[] = $aurl;
    }

    $sql->close();

    //loggit(3, print_r($items, TRUE));

    loggit(1, "Returning: [$count] items.");
    return ($items);
}


//Retrieve the feed items that are sticky for a users river
function get_sticky_feed_items($uid = NULL, $max = 100)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get prefs
    $prefs = get_user_prefs($uid);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $sqltxt = "SELECT nfitems.author,
					newsfeeds.avatarurl,
					nfitems.description,
					'nfitemprops.fulltext',
					'nfitemprops.hidden',
					newsfeeds.id,
					newsfeeds.title,
					newsfeeds.url,
					nfitems.guid,
					nfitems.id,
					nfitems.url,
					nfitems.origin,
					nfitems.enclosure,
					nfitems.timestamp,
					nfitems.timeadded,
					nfitems.sourcetitle,
					nfitems.sourceurl,
					nfitems.title,
					newsfeeds.link,
					newsfeeds.pubdate,
					nfcatalog.sticky,
					'nfcatalog.hidden',
					'nfcatalog.fulltext'
             FROM (SELECT itemid FROM nfitemprops WHERE userid = ? AND sticky = 1) AS tsub
             LEFT JOIN nfitems ON nfitems.id = tsub.itemid
			 LEFT JOIN newsfeeds ON nfitems.feedid = newsfeeds.id
			 LEFT JOIN nfcatalog ON nfitems.feedid = nfcatalog.feedid AND nfcatalog.userid = ?
             ORDER BY timeadded ASC LIMIT ?";

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssd", $uid, $uid, $max) or loggit(2, $sql->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for: [$uid].");
        return (array());
    }

    $sql->bind_result($lauthor,
        $lavatarurl,
        $ldescription,
        $lfulltext,
        $lhidden,
        $lfeedid,
        $lfeedtitle,
        $lfeedurl,
        $lguid,
        $litemid,
        $lurl,
        $lorigin,
        $lenclosure,
        $ltimestamp,
        $ltimeadded,
        $lsourcetitle,
        $lsourceurl,
        $ltitle,
        $lfeedlink,
        $lfeedpubdate,
        $lfsticky,
        $lfhidden,
        $lffulltext
    ) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        //Construct item body
        if ($prefs['fulltextriver'] == 0) {
            if ($lffulltext == 1) {
                $itembody = $ldescription;
            } else
                if (strlen($ldescription) > $cg_default_max_river_item_body_length) {
                    $itembody = truncate_text($ldescription, $cg_default_max_river_item_body_length) . "...";
                } else {
                    $itembody = $ldescription;
                }
        } else {
            $itembody = $ldescription;
        }

        //Construct the item array
        $items[$count] = array('author' => $lauthor,
            'avatarUrl' => $lavatarurl,
            'body' => $itembody,
            'guid' => $lguid,
            'id' => $litemid,
            'index' => $count,
            'link' => $lurl,
            'origin' => $lorigin,
            'enclosure' => unserialize($lenclosure),
            'permaLink' => $lurl,
            'pubDate' => date("D, d M Y H:i:s O", $ltimeadded),
            'sourcetitle' => $lsourcetitle,
            'sourceurl' => $lsourceurl,
            'sticky' => 1,
            'hidden' => 0,
            'fullText' => $lfulltext,
            'title' => $ltitle,
            'feed' => array(
                'feedDescription' => '',
                'feedFullText' => $lffulltext,
                'feedHidden' => $lfhidden,
                'feedId' => $lfeedid,
                'feedSticky' => $lfsticky,
                'feedTitle' => $lfeedtitle,
                'feedUrl' => $lfeedurl,
                'websiteUrl' => $lfeedlink,
                'whenLastUpdate' => $lfeedpubdate
            )
        );

        //Check if this feed is linked to an outline this user subscribes to
        $oid = get_feed_outline_by_user($lfeedid, $uid);
        if ($oid != FALSE) {
            $ou = get_outline_info($oid);
            $items[$count]['feed']['linkedOutlineId'] = $oid;
            if (!empty($ou['type'])) {
                $items[$count]['feed']['linkedOutlineType'] = $ou['type'];
            }
            if (!empty($ou['title'])) {
                $items[$count]['feed']['linkedOutlineTitle'] = $ou['title'];
            }
            if (!empty($ou['url'])) {
                $items[$count]['feed']['linkedOutlineUrl'] = $ou['url'];
            }
            if (!empty($ou['ownername'])) {
                $items[$count]['feed']['ownerName'] = $ou['ownername'];
            }
            if (!empty($ou['avatarurl'])) {
                $items[$count]['feed']['avatarUrl'] = $ou['avatarurl'];
            }
        }

        $count++;
    }

    $sql->close();

    //loggit(3, print_r($items, TRUE));

    loggit(1, "Returning: [$count] items.");
    return ($items);
}


//Retrieve the feed items for a users river
function get_river_feed_items($uid = NULL, $max = 100)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get prefs
    $prefs = get_user_prefs($uid);

    $timeadded = time() - 7200;

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Run the query
    $sqltxt = "SELECT $table_nfitem.id,
                    $table_nfitem.title,
                    $table_nfitem.url,
                    $table_nfitem.timestamp,
                    $table_nfitem.feedid,
                    $table_nfitem.timeadded,
                    $table_nfitem.enclosure,
                    $table_nfitem.description,
                    $table_nfitem.guid,
                    $table_nfitem.origin,
                    $table_nfitem.sourceurl,
                    $table_nfitem.sourcetitle,
                    $table_nfitem.author,
                    $table_nfitemprop.sticky,
                    $table_nfcatalog.sticky,
                    $table_nfitemprop.hidden,
                    $table_nfcatalog.hidden,
                    $table_nfitemprop.`fulltext`,
                    $table_nfcatalog.`fulltext`,
                    $table_newsfeed.url,
                    $table_newsfeed.title,
                    $table_newsfeed.link,
                    $table_newsfeed.avatarurl,
                    $table_newsfeed.pubdate
             FROM $table_nfitem
             LEFT JOIN $table_newsfeed ON $table_newsfeed.id = $table_nfitem.feedid
             LEFT OUTER JOIN $table_nfitemprop ON $table_nfitemprop.itemid = $table_nfitem.id AND $table_nfitemprop.userid=?
             INNER JOIN $table_nfcatalog ON $table_nfcatalog.feedid = $table_nfitem.feedid
             WHERE $table_nfcatalog.userid=?
             AND $table_nfitem.timeadded > ?
             AND $table_nfitem.`old` = 0";
    $sqltxt .= " ORDER BY $table_nfitem.timeadded ASC LIMIT ?";

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssdd", $uid, $uid, $timeadded, $max) or loggit(2, $sql->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for: [$uid].");
        return (array());
    }

    $sql->bind_result($litemid, $ltitle, $lurl, $ltimestamp, $lfeedid, $ltimeadded, $lenclosure, $ldescription, $lguid, $lorigin, $lsourceurl,
        $lsourcetitle, $lauthor, $lsticky, $lfsticky, $lhidden, $lfhidden, $lfulltext, $lffulltext, $lfeedurl, $lfeedtitle, $lfeedlink, $lavatarurl,
        $lfeedpubdate) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        //Construct item body
        if ($prefs['fulltextriver'] == 0) {
            if ($lffulltext == 1) {
                $itembody = $ldescription;
            } else
                if (strlen($ldescription) > 300) {
                    $itembody = truncate_text($ldescription, 300) . "...";
                } else {
                    $itembody = $ldescription;
                }
        } else {
            $itembody = $ldescription;
        }

        //Check if we have a feed defined timestamp greater than 1/1/1990 and
        //use it if so.  This is a quick and dirty sanity check.  Otherwise use
        //timeadded since we know that is always sane
        $litemtime = 0;
        if( $ltimestamp > 631152000 ) {
            $litemtime = $ltimestamp;
        } else {
            $litemtime = $ltimeadded;
        }

        //Construct the item array
        $items[$count] = array('author' => $lauthor,
            'avatarUrl' => $lavatarurl,
            'body' => $itembody,
            'guid' => $lguid,
            'id' => $litemid,
            'index' => $count,
            'link' => $lurl,
            'origin' => $lorigin,
            'enclosure' => unserialize($lenclosure),
            'permaLink' => $lurl,
            'pubDate' => date("D, d M Y H:i:s O", $litemtime),
            'sourcetitle' => $lsourcetitle,
            'sourceurl' => $lsourceurl,
            'sticky' => 1,
            'hidden' => 0,
            'fullText' => $lfulltext,
            'title' => $ltitle,
            'feed' => array(
                'feedDescription' => '',
                'feedFullText' => $lffulltext,
                'feedHidden' => $lfhidden,
                'feedId' => $lfeedid,
                'feedSticky' => $lfsticky,
                'feedTitle' => $lfeedtitle,
                'feedUrl' => $lfeedurl,
                'websiteUrl' => $lfeedlink,
                'whenLastUpdate' => $lfeedpubdate
            )
        );

        //Check if this feed is linked to an outline this user subscribes to
        $oid = get_feed_outline_by_user($lfeedid, $uid);
        if ($oid != FALSE) {
            $ou = get_outline_info($oid);
            $items[$count]['feed']['linkedOutlineId'] = $oid;
            if (!empty($ou['type'])) {
                $items[$count]['feed']['linkedOutlineType'] = $ou['type'];
            }
            if (!empty($ou['title'])) {
                $items[$count]['feed']['linkedOutlineTitle'] = $ou['title'];
            }
            if (!empty($ou['url'])) {
                $items[$count]['feed']['linkedOutlineUrl'] = $ou['url'];
            }
            if (!empty($ou['ownername'])) {
                $items[$count]['feed']['ownerName'] = $ou['ownername'];
            }
            if (!empty($ou['avatarurl'])) {
                $items[$count]['feed']['avatarUrl'] = $ou['avatarurl'];
            }
        }

        $count++;
    }

    $sql->close();

    //loggit(3, print_r($items, TRUE));

    loggit(1, "Returning: [$count] items.");
    return ($items);
}


//Add an enclosure for a feed item
function add_feed_item_enclosure($iid = NULL, $enclosure = array()) {
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if(empty($enclosure)) {
        loggit(2, "Enclosure array is blank or corrupt.");
        return (FALSE);
    }

    //Set an integer for the given type
    $t = get_mimetype_parent($enclosure['type']);
    $enclosure['t'] = $t;
    switch ($t) {
        case 'image':
            $type = 1;
            break;
        case 'audio':
            $type = 2;
            break;
        case 'video':
            $type = 3;
            break;
        default:
            $type = 0;
            break;
    }

    //See if this enclosure was an extraction from scraping the html or if it was a
    //native enclosure
    $source = 0;
    if(isset($enclosure['source']) && $enclosure['source']) {
        loggit(3, "DEBUG: Enclosure: [".$enclosure['url']."] was found by scraping.");
        $source = 1;
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    $stmt = "INSERT INTO $table_nfenclosures
                        (iid,
                         url,
                         mimetype,
                         length,
                         time,
                         type,
                         source)
             VALUES     (?,?,?,?,NOW(),?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("dssddd", $iid, $enclosure['url'], $enclosure['type'], $enclosure['length'], $type, $source) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(3, $dbh->error);
    $sql->close();

    loggit(1, "NEW ENCLOSURE: ".print_r($enclosure, TRUE));
    return(TRUE);
}


//Re-calculate the counts for the v2 search table
function calculate_map_word_counts() {
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //First kill the existing records
    $stmt = "TRUNCATE TABLE $table_nfitem_map_count";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);

    //Do the word count insert
    $stmt = "INSERT INTO $table_nfitem_map_count
                SELECT wordid,count(nfitemid) as totals
                FROM $table_nfitem_map_catalog AS catalog
                GROUP BY wordid
                ORDER BY totals DESC";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);

    //Close the connection
    $sql->close();

    //Log and leave
    loggit(3, "Re-built search map word counts.");
    return(TRUE);
}


//Re-calculate the counts for the v2 search table for the last 23 hours
function calculate_map_word_today_counts() {
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //First kill the existing records
    $stmt = "TRUNCATE TABLE $table_nfitem_map_count_today";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);

    //Do the word count insert
    $stmt = "INSERT INTO nfitem_map_count_today
                SELECT wordid,count(nfitemid) as totals
                FROM nfitem_map_catalog AS catalog
                WHERE catalog.added >= (NOW() - INTERVAL 23 HOUR)
                GROUP BY wordid
                ORDER BY totals DESC";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);

    //Close the connection
    $sql->close();

    //Log and leave
    loggit(3, "Re-built search map word counts.");
    return(TRUE);
}


//Get the counts for the v2 search table for the last 23 hours
function get_map_word_today_counts($max = 100) {
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //First kill the existing records
    $stmt = "TRUNCATE TABLE $table_nfitem_map_count_today";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);

    //Do the word count insert
    $stmt = "SELECT nfm.word
              FROM nfitem_map_count_today AS nfc
              INNER JOIN nfitem_map AS nfm ON nfm.id = nfc.wordid
              ORDER BY nfc.totals DESC";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any items returned
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No words returned for the last 23 hours.");
        return (array());
    }

    $sql->bind_result(
        $fword
    ) or loggit(2, "MySql error: " . $dbh->error);

    $words = array();
    $count = 0;
    while ($sql->fetch()) {
        //Construct the word array
        $words[$count] = $fword;
        $count++;
    }

    //Close the connection
    $sql->close();

    //Log and leave
    loggit(3, "Returning: [$count] words in the last 23 hours.");
    return(TRUE);
}


//Determine if a headline is click-bait and assign a score
function calculate_click_bait_score($headline = "") {
    //Check parameters
    if (empty($headline)) {
        loggit(2, "The headline to test is blank or corrupt: [$headline]");
        return (FALSE);
    }

    $cbscore = 0;

    //Check linguistics
    if (preg_match("/\bthis\b/i", $headline)) {
        $cbscore += 5;
    }
    if (preg_match("/\bthese\b/i", $headline)) {
        $cbscore += 5;
    }
    if (preg_match("/\byou\b/i", $headline)) {
        $cbscore += 5;
    }
    if (preg_match("/\byour\b/i", $headline)) {
        $cbscore += 5;
    }
    if (preg_match("/([^-]\b[1-9][0-9]*\b)/i", $headline)) {
        $cbscore += 5;
    }

    //Log and leave
    loggit(3, "Click-bait score for [$headline] is: [$cbscore].");
    return($cbscore);
}


//Create an m3u file based on all of the audio enclosures in this user's river
function create_m3u_from_river_enclosures($uid = NULL, $max = 50) {

    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //The m3u file content
    $m3utxt = "#EXTM3U\n\n";

    //Get all of the audio enclosures
    $encitems = get_feed_items_with_enclosures2($uid, NULL, $max, NULL, 2, TRUE);

    //Build out the m3u items
    foreach($encitems as $encitem) {

        //The header for each line
        $m3utxt .= "#EXTINF:";

        //Next is the length.  If it's less than 3 bytes (randomly chosen) assume the length isn't valid
        //and use -1 instead, per the spec
        if($encitem['length'] > 3 && is_numeric($encitem['length'])) {
            $m3utxt .= $encitem['length'].", ";
        } else {
            $m3utxt .= "-1, ";
        }

        //Need a title next.  Use the title of the article for now until a better way comes to mind
        $m3utxt .= $encitem['title']."\n";

        //Finally the url of the enclosure followed by a blank line
        $m3utxt .= $encitem['url']."\n\n";

    }

    return($m3utxt);
}


//Build an opml outline containing the requested feed items
function build_opml_nfitem_feed($uid = NULL, $max = NULL, $archive = FALSE, $feeditems = NULL, $nos3 = FALSE, $s3filename = NULL, $returns3url = FALSE, $giventitle = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";

    //Get some essentials
    $username = get_user_name_from_uid($uid);
    $prefs = get_user_prefs($uid);

    //Lets set a sane limit for feed size
    if ($max == NULL) {
        if (!empty($prefs['maxlist'])) {
            $max = $prefs['maxlist'];
        } else {
            loggit(1, "No max given. Setting to default of: [$default_max_opml_items].");
            $max = $default_max_opml_items;
        }
    }

    //Allow passing in a list of articles as a param
    if ($feeditems == NULL || !is_array($feeditems)) {
        loggit(2, "The feed item list passed in was blank or corrupt.");
        return (FALSE);
    }

    //Get the dates straight
    $dateCreated = date("D, d M Y H:i:s O");
    $dateModified = date("D, d M Y H:i:s O");

    //Set the title
    $outlinetitle = "Feed item list.";
    if(!empty($giventitle)) {
        $outlinetitle = $giventitle;
    }

    //The feed string
    $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
    $opml .= "<!-- OPML generated by " . $system_name . " v" . $version . " on " . date("D, d M Y H:i:s O") . " -->\n";
    $opml .= '<opml version="2.0">' . "\n";

    $opml .= "
      <head>
        <title>" . xmlentities($giventitle) . "</title>
        <dateCreated>$dateCreated</dateCreated>
        <dateModified>$dateModified</dateModified>
        <ownerName>" . xmlentities(get_user_name_from_uid($uid)) . "</ownerName>
        <ownerId>" . $uid . "</ownerId>
        <expansionState></expansionState>
        <expansionState></expansionState>
        <vertScrollState>1</vertScrollState>
        <windowTop>146</windowTop>
        <windowLeft>107</windowLeft>
        <windowBottom>468</windowBottom>
        <windowRight>560</windowRight>
      </head>\n";

    $opml .= "
      <body>";

    foreach ($feeditems as $feeditem) {
        $opml .= "
              <outline text=\"" . xmlentities(trim(str_replace(array("\r", "\n", "\t", '&#13;'), '', $feeditem['title']))) . "\">
                      <outline text=\"Link to Article\" type=\"link\" url=\"" . htmlspecialchars($feeditem['url']) . "\" />";

        if (!empty($feeditem['staticurl'])) {
            $opml .= "        <outline text=\"Archived Version\" type=\"link\" url=\"" . htmlspecialchars($feeditem['staticurl']) . "\" />" . "\n";
        }

        if (!empty($feeditem['sourceurl']) || !empty($feeditem['sourcetitle'])) {
            $opml .= '        <outline text="Source: ' . htmlspecialchars(trim($feeditem['sourcetitle'])) . '" type="link" url="' . htmlspecialchars(trim($feeditem['sourceurl'])) . '" />' . "\n";
        }


        $opml .= "      <outline text=\"" . date("D, d M Y H:i", $feeditem['timeadded']) . "\" />
                      <outline text=\"\" />";
        foreach (explode("</p>", trim(str_replace(array("\r", "\n", "\t", '&#13;'), '', $feeditem['description']))) as $line) {
            //loggit(3, "DEBUGLINE: ".$line);
            $line = trim(strip_tags($line));
            if (!empty($line)) {
                $opml .= "
                      <outline text=\"" . xmlentities($line) . "\" />";
            }
        }
        $opml .= "
              </outline>\n";
    }

    $opml .= "      </body>
  ";

    $opml .= "</opml>";


    //If this user has S3 storage enabled, then do it
    $s3res = FALSE;
    if ((s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Get the feed file name
        if(!empty($s3filename)) {
            $filename = $s3filename.".".time().".opml";
        } else {
            $filename = "feeditemlisting".time().".opml";
        }
        $arcpath = '';

        //Was this a request for a monthly archive?
        if ($archive != FALSE) {
            $arcpath = "/arc/" . date('Y') . "/" . date('m') . "/" . date('d');
            //loggit(3, "Archive path: [".$arcpath."]");
        }

        //Put the file
        $s3res = putInS3($opml, $filename, $s3info['bucket'] . $arcpath, $s3info['key'], $s3info['secret'], "text/xml");
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
            //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
        } else {
            $s3url = get_s3_url($uid, $arcpath, $filename);
            loggit(3, "Wrote feed to S3 at url: [$s3url].");
        }
    }


    loggit(3, "Built newsfeed item opml feed for user: [$username | $uid].");
    if($returns3url && $s3res) {
        return($s3url);
    }
    return ($opml);
}


//Convert a news feed to opml structure
function convert_jsonfeed_to_rss($content = NULL, $max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Check params
    if (empty($content) || !is_jsonfeed($content)) {
        loggit(2, "The jsonfeed content is blank or corrupt: [$content]");
        return (FALSE);
    }

    $jf = json_decode($content, TRUE);

    //Get the latest item publish date
    $latestDate = 0;
    foreach( $jf['items'] as $item ) {
        if( strtotime($item['date_published']) > $latestDate ) $latestDate = strtotime($item['date_published']);
    }
    $lastBuildDate = date(DATE_RSS, $latestDate);

    //Create the xml feed
    $xmlFeed = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
    $xmlFeed->addChild("channel");

    //Required
    $xmlFeed->channel->addChild("title", xmlentities($jf['title']));
    $xmlFeed->channel->addChild("pubDate", $lastBuildDate);
    $xmlFeed->channel->addChild("lastBuildDate", $lastBuildDate);

    //Optional
    if(isset($jf['description'])) $xmlFeed->channel->description = xmlentities($jf['description']);
    if(isset($jf['home_page_url'])) $xmlFeed->channel->link = xmlentities($jf['home_page_url']);

    //Items
    $count = 0;
    foreach( $jf['items'] as $item ) {
        //loggit(3, "DEBUG: ".print_r($item, TRUE));

        $newItem = $xmlFeed->channel->addChild('item');

        if(isset($item['id'])) $newItem->addChild('guid', xmlentities($item['id']));
        if(isset($item['title'])) $newItem->addChild('title', xmlentities($item['title']));
        if(isset($item['content_text'])) $newItem->addChild('description', xmlentities($item['content_text']));
        if(isset($item['content_html'])) $newItem->addChild('description', xmlentities($item['content_html']));
        if(isset($item['date_published'])) $newItem->addChild('pubDate', $item['date_published']);
        if(isset($item['url'])) $newItem->addChild('link', xmlentities($item['url']));

        //Enclosures?
        if(isset($item['attachments'])) {
            foreach($item['attachments'] as $attachment) {
                $enclosure = $newItem->addChild('enclosure');
                $enclosure['url'] = xmlentities($attachment['url']);
                $enclosure['type'] = xmlentities($attachment['mime_type']);
                $enclosure['length'] = $attachment['size_in_bytes'];
            }
        }

        $count++;
    }

    //Log and leave
    loggit(1, "Converted: [$count] items from JSONfeed to RSS.");
    return($xmlFeed->asXML());
}


//Get poor quality feeds
function get_poor_feeds()
{

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    $thirtydaysago = time() - (86400 * 30);

    //Build the query
    $sqltxt = "SELECT id, url FROM $table_newsfeed WHERE (contenttype NOT LIKE '%xml%' OR content LIKE '<!DOCTYPE%') AND errors > 100 AND lastupdate < ? AND lastupdate > 0 AND dead = 0 ORDER BY lastupdate ASC ";

    //Run the query
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("d", $thirtydaysago) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feed items returned
    if ($sql->num_rows() < 1) {
        $sql->close() or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "No poor feeds found.");
        return (array());
    }

    $sql->bind_result($fid, $furl) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array(
            'id'  => $fid,
            'url' => $furl
        );
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] poor feeds.");
    return ($feeds);
}
