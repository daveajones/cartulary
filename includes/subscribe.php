<?php
//########################################################################################
// API for managing linkage between users and content
//########################################################################################


//Check if a feed links to an outline for a given user
function get_feed_outline_by_user($fid = NULL, $uid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT outlineid FROM $table_nfcatalog WHERE (userid=? AND feedid=?) AND outlineid != '' AND outlineid IS NOT NULL") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        //loggit(1,"The feed: [$fid] does not link to an outline for user: [$uid].");
        return (FALSE);
    }
    $sql->bind_result($oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //loggit(1,"The feed: [$fid] links to outline: [$oid] for user: [$uid].");
    return ($oid);
}


//See how many items are in the database for a given feed
function get_feed_subscriber_count($fid = NULL)
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
    $sql = $dbh->prepare("SELECT userid FROM $table_nfcatalog WHERE feedid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    $subcount = $sql->num_rows();
    $sql->close();

    //loggit(3,"The feed: [$fid] has: [$subcount] subscribers.");
    return ($subcount);
}


//Get a list of users who subscribe to this feed
function get_feed_subscribers($fid = NULL)
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
    $sql = $dbh->prepare("SELECT userid FROM $table_nfcatalog WHERE feedid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    $subcount = $sql->num_rows();
    if ($subcount < 1) {
        return (array());
    }

    //Put the feed ids in an array to send back
    $sql->bind_result($userid) or loggit(2, "MySql error: " . $dbh->error);
    $users = array();
    $count = 0;
    while ($sql->fetch()) {
        $users[$count] = $userid;
        $count++;
    }

    $sql->close();

    loggit(1, "The feed: [$fid] has: [$count] subscribers.");
    return ($users);
}


//Retrieve an array of info about the feed
function get_feed_info_for_user($id = NULL, $uid = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The feed id given is corrupt or blank: [$id]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
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
                  $table_newsfeed.rsscloudregurl,
                  $table_newsfeed.rsscloudlastreg,
                  $table_newsfeed.updated,
                  $table_newsfeed.lastitemid,
                  $table_nfcatalog.outlineid,
		  $table_newsfeed.pubdate,
                  $table_newsfeed.errors,
		  $table_newsfeed.id,
		  $table_nfcatalog.sticky,
		  $table_nfcatalog.hidden
           FROM $table_newsfeed,$table_nfcatalog
           WHERE $table_newsfeed.id=?
	   AND $table_newsfeed.id = $table_nfcatalog.feedid
           AND $table_nfcatalog.userid = ?";

    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $id, $uid) or loggit(2, "MySql error: " . $dbh->error);
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
        $feed['lastmod'], $feed['createdon'], $feed['link'], $feed['rsscloudregurl'],
        $feed['rsscloudlastreg'], $feed['updated'], $feed['lastitemid'], $feed['oid'],
        $feed['pubdate'], $feed['errors'], $feed['id'], $feed['sticky'], $feed['hidden']) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //loggit(1,"Returning feed info for feed: [$id]");
    return ($feed);
}


//Retrieve an array of info about the feed item
function get_feed_item_properties($id = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The feed item id given is corrupt or blank: [$id]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "SELECT sticky,hidden FROM $table_nfitemprop WHERE itemid=? AND userid=?";

    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $id, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        //loggit(2,"Failed to retrieve feed item properties for item: [$id]");
        return (FALSE);
    }
    $item = array();
    $sql->bind_result($item['sticky'], $item['hidden']) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //loggit(1,"Returning feed item properties for item: [$id]");
    return ($item);
}


//Set an array of info about the feed item
function set_feed_item_properties($id = NULL, $uid = NULL, $props = array())
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The feed item id given is corrupt or blank: [$id]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }
    if (empty($props) || !is_array($props)) {
        loggit(2, "The property array given is corrupt or blank: [$props]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Sticky bit?
    if (isset($props['sticky']) && $props['sticky'] == TRUE) {
        mark_feed_item_as_sticky($id, $uid);
    } else {
        unmark_feed_item_as_sticky($id, $uid);
    }
    //Hidden bit?
    if (isset($props['hidden']) && $props['hidden'] == TRUE) {
        mark_feed_item_as_hidden($id, $uid);
    } else {
        unmark_feed_item_as_hidden($id, $uid);
    }
    //Fulltext bit?
    if (isset($props['fulltext']) && $props['fulltext'] == TRUE) {
        mark_feed_item_as_fulltext($id, $uid);
    } else {
        unmark_feed_item_as_fulltext($id, $uid);
    }

    //loggit(1,"Returning feed item properties for item: [$id]");
    return (TRUE);
}


//Sets a feed's sticky bit to on in the newsfeed catalog
function mark_feed_as_sticky($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET sticky=1 WHERE feedid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as sticky for user: [$uid].");
    return (TRUE);
}


//Sets a feed's sticky bit to off in the newsfeed catalog
function unmark_feed_as_sticky($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET sticky=0 WHERE feedid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as sticky for user: [$uid].");
    return (TRUE);
}


//Sets a feed item's sticky bit to true
function mark_feed_item_as_sticky($iid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "INSERT INTO $table_nfitemprop (itemid, userid, sticky) VALUES (?,?,1) ON DUPLICATE KEY UPDATE sticky=1";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $iid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged item: [$iid] as sticky for user: [$uid].");
    return (TRUE);
}


//Sets a feed item's sticky bit to false
function unmark_feed_item_as_sticky($iid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitemprop SET sticky=0 WHERE itemid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $iid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Un-flagged item: [$iid] as sticky for user: [$uid].");
    return (TRUE);
}


//Sets a feed's hidden bit to on in the newsfeed catalog
function mark_feed_as_hidden($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET hidden=1 WHERE feedid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as hidden for user: [$uid].");
    return (TRUE);
}


//Sets a feed's hidden bit to off in the newsfeed catalog
function unmark_feed_as_hidden($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET hidden=0 WHERE feedid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as hidden for user: [$uid].");
    return (TRUE);
}


//Sets a feed item's hidden bit to true
function mark_feed_item_as_hidden($iid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "INSERT INTO $table_nfitemprop (itemid, userid, hidden) VALUES (?,?,1) ON DUPLICATE KEY UPDATE hidden=1";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $iid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged item: [$iid] as hidden for user: [$uid].");
    return (TRUE);
}


//Sets a feed item's hidden bit to false
function unmark_feed_item_as_hidden($iid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitemprop SET hidden=0 WHERE itemid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $iid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Un-flagged item: [$iid] as hidden for user: [$uid].");
    return (TRUE);
}


//Sets a feed's fulltext bit to on in the newsfeed catalog
function mark_feed_as_fulltext($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET `fulltext`=1 WHERE feedid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as fulltext for user: [$uid].");
    return (TRUE);
}


//Sets a feed's fulltext bit to off in the newsfeed catalog
function unmark_feed_as_fulltext($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET `fulltext`=0 WHERE feedid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Flagged feed: [$fid] as fulltext for user: [$uid].");
    return (TRUE);
}


//Sets a feed item's fulltext bit to true
function mark_feed_item_as_fulltext($iid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "INSERT INTO $table_nfitemprop (itemid, userid, `fulltext`) VALUES (?,?,1) ON DUPLICATE KEY UPDATE `fulltext`=1";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $iid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "FULLTEXT: Flagged item: [$iid] as fulltext for user: [$uid].");
    return (TRUE);
}


//Sets a feed item's fulltext bit to false
function unmark_feed_item_as_fulltext($iid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($iid)) {
        loggit(2, "The feed item id is blank or corrupt: [$iid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfitemprop SET `fulltext`=0 WHERE itemid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $iid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1, "Un-flagged item: [$iid] as fulltext for user: [$uid].");
    return (TRUE);
}


//Check if a feed is sticky for a given user
function feed_is_sticky($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT sticky FROM $table_nfcatalog WHERE feedid=? AND userid=? AND sticky=1") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        //loggit(3,"The feed: [$fid] is NOT sticky for user: [$uid].");
        return (FALSE);
    }
    $sql->close();

    //loggit(3,"The feed: [$fid] is sticky for user: [$uid].");
    return (TRUE);
}


//Check if a feed is hidden for a given user
function feed_is_hidden($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT hidden FROM $table_nfcatalog WHERE feedid=? AND userid=? AND hidden=1") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        //loggit(3,"The feed: [$fid] is NOT hidden for user: [$uid].");
        return (FALSE);
    }
    $sql->close();

    //loggit(3,"The feed: [$fid] is hidden for user: [$uid].");
    return (TRUE);
}


//Check if a feed is full text for a given user
function feed_is_fulltext($fid = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($fid)) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT `fulltext` FROM $table_nfcatalog WHERE feedid=? AND userid=? AND `fulltext`=1") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        //loggit(3,"The feed: [$fid] is NOT fulltext for user: [$uid].");
        return (FALSE);
    }
    $sql->close();

    loggit(1, "The feed: [$fid] is fulltext for user: [$uid].");
    return (TRUE);
}


//Check if a feed is linked to a user
function feed_is_linked($fid = NULL, $uid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT * FROM $table_nfcatalog WHERE feedid=? AND userid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The feed: [$fid] is not linked to user: [$uid].");
        return (FALSE);
    }
    $sql->close();

    loggit(1, "The feed: [$fid] is already linked to user: [$uid].");
    return (TRUE);
}


//Check if a feed is linked to a user
function feed_is_linked_by_url($url = NULL, $uid = NULL)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The feed url is blank or corrupt: [$url]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the feed id from the url
    $fid = feed_exists($url);
    if ($fid == FALSE) {
        loggit(1, "Feed: [$url] not in database.");
        return (FALSE);
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT * FROM $table_nfcatalog WHERE feedid=? AND userid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $fid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The feed: [$fid] is not linked to user: [$uid].");
        return (FALSE);
    }
    $sql->close();

    loggit(1, "The feed: [$fid] is already linked to user: [$uid].");
    return (TRUE);
}


//Link a feed to a user
function link_feed_to_user($fid = NULL, $uid = NULL)
{
    //Check parameters
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    $tstamp = time();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Link the article to the user
    $stmt = "INSERT INTO $table_nfcatalog (userid,feedid,linkedon) VALUES (?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $uid, $fid, $tstamp) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Linked feed: [$fid] with user: [$uid].");
    return (TRUE);
}


//Retrieve a list of feeds subscribed to by the given user
function get_feeds($uid = NULL, $max = NULL, $ididx = NULL)
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

    //Get feeds for the given user id
    $sqltxt = "SELECT $table_newsfeed.id,
                    $table_newsfeed.title,
                    $table_newsfeed.url,
                    $table_newsfeed.createdon,
                    $table_newsfeed.lastmod,
                    $table_newsfeed.lastupdate,
                    $table_newsfeed.lastcheck,
                    $table_newsfeed.link,
		    $table_newsfeed.errors,
                    $table_nfcatalog.linkedon,
                    $table_nfcatalog.outlineid,
                    $table_nfcatalog.sticky,
                    $table_nfcatalog.hidden,
		    $table_nfcatalog.`fulltext`
	     FROM $table_newsfeed,
                  $table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_newsfeed.id)";
    //$sqltxt .= " ORDER BY $table_nfcatalog.sticky DESC,$table_newsfeed.title ASC";
    $sqltxt .= " ORDER BY $table_newsfeed.title ASC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "This user has not subscribed to any feeds: [$uid]");
        return (array());
    }

    $sql->bind_result($fid,
        $ftitle,
        $furl,
        $fcreatedon,
        $flastmod,
        $flastupdate,
        $flastcheck,
        $flink,
        $ferrors,
        $flinkedon,
        $foid,
        $fsticky,
        $fhidden,
        $ffulltext) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        if ($ididx == TRUE) {
            $idx = $fid;
        } else {
            $idx = $count;
        }

        $feeds[$idx] = array('id' => $fid,
            'title' => $ftitle,
            'url' => $furl,
            'createdon' => $fcreatedon,
            'lastmod' => $flastmod,
            'lastupdate' => $flastupdate,
            'lastcheck' => $flastcheck,
            'link' => $flink,
            'errors' => $ferrors,
            'linkedon' => $flinkedon,
            'oid' => $foid,
            'sticky' => $fsticky,
            'hidden' => $fhidden,
            'fulltext' => $ffulltext);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] feeds for user: [$uid]");
    return ($feeds);
}


//Retrieve a list of feeds subscribed to by the given user that match a query text
function search_feeds($uid = NULL, $query = NULL, $max = NULL, $ididx = NULL)
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

    //Assemble sql
    $colnames = array(
        "$table_newsfeed.title",
        "$table_newsfeed.url",
        "$table_newsfeed.link"
    );
    $qsql = build_search_sql($query, $colnames);


    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get feeds for the given user id
    $sqltxt = "SELECT $table_newsfeed.id,
                    $table_newsfeed.title,
                    $table_newsfeed.url,
                    $table_newsfeed.createdon,
                    $table_newsfeed.lastmod,
                    $table_newsfeed.lastupdate,
                    $table_newsfeed.lastcheck,
                    $table_newsfeed.link,
		    $table_newsfeed.errors,
                    $table_nfcatalog.linkedon,
                    $table_nfcatalog.outlineid,
                    $table_nfcatalog.sticky,
                    $table_nfcatalog.hidden,
		    $table_nfcatalog.fulltext
	     FROM $table_newsfeed,
                  $table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_newsfeed.id)";

    //Append search criteria
    $sqltxt .= $qsql['text'];

    //Sort order
    $sqltxt .= " ORDER BY $table_newsfeed.title ASC";

    //Limit
    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);

    //Adjust bindings
    $newsetup = "s" . $qsql['bind'][0];
    $qsql['bind'][0] = & $newsetup;
    array_splice($qsql['bind'], 1, 0, array(&$uid));

    $ref = new ReflectionClass('mysqli_stmt');
    $method = $ref->getMethod("bind_param");
    $method->invokeArgs($sql, $qsql['bind']);

    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "This user has not subscribed to any feeds: [$uid]");
        return (array());
    }

    $sql->bind_result($fid,
        $ftitle,
        $furl,
        $fcreatedon,
        $flastmod,
        $flastupdate,
        $flastcheck,
        $flink,
        $ferrors,
        $flinkedon,
        $foid,
        $fsticky,
        $fhidden,
        $ffulltext) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        if ($ididx == TRUE) {
            $idx = $fid;
        } else {
            $idx = $count;
        }

        $feeds[$idx] = array('id' => $fid,
            'title' => $ftitle,
            'url' => $furl,
            'createdon' => $fcreatedon,
            'lastmod' => $flastmod,
            'lastupdate' => $flastupdate,
            'lastcheck' => $flastcheck,
            'link' => $flink,
            'errors' => $ferrors,
            'linkedon' => $flinkedon,
            'oid' => $foid,
            'sticky' => $fsticky,
            'hidden' => $fhidden,
            'fulltext' => $ffulltext);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$count] feeds for user: [$uid] matching query: [$query].");
    return ($feeds);
}


//Unlink a feed from a user
function unlink_feed_from_user($uid = NULL, $fid = NULL)
{
    //Check params
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($fid == NULL) {
        loggit(2, "The feed id is blank or corrupt: [$fid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_nfcatalog WHERE userid=? AND feedid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $fid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    loggit(1, "Unlinked: [$delcount] feeds: [$fid] from user: [$uid].");
    return (TRUE);
}


//Get a list of pub feeds for this user
function get_pub_feeds($uid = NULL)
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
    $sql = $dbh->prepare("SELECT userid,url,linkedon,title,link FROM $table_sopml_feed WHERE userid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    $subcount = $sql->num_rows();
    if ($subcount < 1) {
        loggit(1, "No pub feeds found for user: [$uid].");
        return (array());
    }

    //Put stuff in an array and send back
    $sql->bind_result($userid, $url, $linkedon, $title, $link) or loggit(2, "MySql error: " . $dbh->error);
    $feeds = array();
    $count = 0;
    while ($sql->fetch()) {
        $feeds[$count] = array('userid' => $userid, 'url' => $url, 'linkedon' => $linkedon, 'title' => $title, 'link' => $link);
        $count++;
    }

    $sql->close();

    loggit(1, "Returning: [$subcount] pub feeds for user: [$uid].");
    return ($feeds);
}


//Change a pub feed for a user
function update_pub_feed($uid = NULL, $url = NULL, $title = NULL, $link = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($url == NULL) {
        loggit(2, "The feed url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Deal with blank feed props
    if (empty($title)) {
        $title = "Untitled Feed";
    } else {
        $title = trim(htmlspecialchars($title));
    }
    //Deal with blank feed props
    if (empty($link)) {
        $link = "";
    } else {
        $link = trim($link);
    }

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_feed SET title=?,link=? WHERE userid=? AND url=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssss", $title, $link, $uid, $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Changed pub feed:[$url]'s title to: [$title] and link to: [$link].");
    return (TRUE);
}


//Add a pub feed to an sopml outline
function add_pub_feed($url = NULL, $uid = NULL, $title = NULL, $link = NULL)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($title == NULL) {
        loggit(2, "The feed title is blank or corrupt: [$title]. Setting to untitled.");
        $title = "Untitled feed.";
    }
    if ($link == NULL) {
        loggit(2, "The feed link is blank or corrupt: [$link]. Setting to empty.");
        $link = "";
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    $tstamp = time();

    //Clean the url
    $url = clean_url($url);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Link the article to the user
    $stmt = "INSERT INTO $table_sopml_feed (userid,url,linkedon,title,link) VALUES (?,?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sssss", $uid, $url, $tstamp, $title, $link) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Linked feed: [$url | $title] with user: [$uid].");
    return (TRUE);
}


//Delete a published feed from an sopml outline
function del_pub_feed($uid = NULL, $url = NULL)
{
    //Check params
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_sopml_feed WHERE userid=? AND url=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    if ($delcount < 1) {
        loggit(2, "Failed to delete feed: [$url] from sopml feeds table for user: [$uid].");
        return (FALSE);
    }

    //Log and leave
    loggit(1, "Deleted feed: [$url] from sopml feed table for user: [$uid].");
    return (TRUE);
}


//Build an opml file listing all the feeds this user follows
function build_opml_reading_list($uid = NULL, $max = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";

    $username = get_user_name_from_uid($uid);
    $prefs = get_user_prefs($uid);
    $feeds = get_feeds($uid, $max);

    //The feed string
    $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
    $opml .= "<!-- OPML generated by " . $system_name . " v" . $cg_sys_version . " on " . date("D, d M Y H:i:s O") . " -->\n";
    $opml .= '<opml version="2.0">' . "\n";

    $opml .= "
      <head>
        <title>The feeds $username follows.</title>
        <dateCreated>" . date("D, d M Y H:i:s O", $feeds[0]['createdon']) . "</dateCreated>
        <dateModified>" . date("D, d M Y H:i:s O", $feeds[0]['createdon']) . "</dateModified>
        <ownerName>" . get_user_name_from_uid($uid) . "</ownerName>
        <ownerId>" . $uid . "</ownerId>
      </head>\n";

    $opml .= "
      <body>";

    foreach ($feeds as $feed) {
        $opml .= "
              <outline text=\"" . trim(str_replace("\n", '', htmlentities($feed['title']))) . "\" description=\"\" htmlUrl=\"" . htmlspecialchars($feed['link']) . "\" xmlUrl=\"" . htmlspecialchars($feed['url']) . "\" />";
    }

    $opml .= "
      </body>";

    $opml .= "</opml>";


    //If we can get some sane S3 credentials then let's go
    if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //What's the filename going to be?
        $filename = $default_readinglist_file_name;

        //Subpath?  Must begin with a slash
        $subpath = "";

        //Put the file
        $s3res = putInS3($opml, $filename, $s3info['bucket'] . $subpath, $s3info['key'], $s3info['secret'], "text/plain");
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
            //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
        } else {
            $s3url = get_s3_url($uid, $subpath, $filename);
            //loggit(3, "Wrote feed to S3 at url: [$s3url].");
        }
    }


    loggit(1, "Built opml reading list for user: [$username | $uid].");
    return ($opml);
}