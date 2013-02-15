<?
//########################################################################################
// API for managing feeds in the database


//Test if the given content is a valid feed
function feed_is_valid($content = NULL)
{
  //Check parameters
  if($content == NULL) {
    loggit(2,"The content to test is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Load the content into a simplexml object
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Look for rss nodes
  foreach($x->channel as $entry) {
    loggit(1, "Found a channel node. This content looks like RSS.");
    return(TRUE);
  }

  //Look for atom nodes
  foreach($x->entry as $entry) {
    loggit(1, "Found and entry node. This content looks like ATOM.");
    return(TRUE);
  }

  //None of the tests passed so return FALSE
  loggit(1,"The content tested was not a valid feed.");
  return(FALSE);
}


//Get the title of a feed from it's content
function get_feed_title($content = NULL)
{
  //Check parameters
  if($content == NULL) {
    loggit(2,"The content of the feed is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Load the content into a simplexml object
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Look for a title node in the rss
  foreach($x->channel->title as $entry) {
    loggit(1, "Found a title node: [$entry].");
    return((string)$entry);
  }

  //Look for atom nodes
  foreach($x->title as $entry) {
    loggit(1, "Found a title node: [$entry].");
    return((string)$entry);
  }

  //None of the tests passed so return FALSE
  loggit(1,"Could not find a title for this feed.");
  return(FALSE);
}


//Does the feed contain a microblog:avatar?
function get_feed_avatar($x = NULL)
{
  //Check parameters
  if( empty($x) ) {
    loggit(2,"The xml object of the feed is blank or corrupt: [$x]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Grab namespaces
  $namespaces = $x->getDocNamespaces();

  //If there's no namespace
  if( !isset($namespaces['microblog']) ) {
    //None of the tests passed so return FALSE
    loggit(1,"No microblog namespace defined for this feed.");
    return(FALSE);
  }

  //Search for an avatar
  $ns_microblog = $x->channel->children($namespaces['microblog']);
  //loggit(3, "MICROBLOG NS: ".print_r($ns_microblog, true));
  if( isset($ns_microblog->avatar) ) {
    $url = $ns_microblog->avatar;
    //loggit(3, "MICROBLOG: Avatar url is: [$url].");
    //Log and leave
    loggit(1, "The avatar of this feed is at: [$url].");
    return($url);
  }

  //We can get avatars for twitter too
  if( stripos($x->channel->link, '/twitter.com') !== FALSE) {
    //Extract the username from the link element
    $twusername = substr($x->channel->link, stripos($x->channel->link, 'twitter.com/') + 12);
    $twprofile = get_twitter_profile($twusername);
    $url = $twprofile['profile_image_url'];
    //loggit(3, "The avatar of this twitter feed is at: [$url].");
    return($url);
  }

  //None of the tests passed so return FALSE
  loggit(1,"Could not find an avatar for this feed.");
  return(FALSE);
}


//_______________________________________________________________________________________
//Check if a feed is already in the newsfeed repository
function feed_exists($url = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The feed url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT id FROM $table_newsfeed WHERE url=?") or print(mysql_error());
  $sql->bind_param("s", $url) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The feed at url: [$url] does not exist in the repository.");
    return(FALSE);
  }
  $sql->bind_result($feedid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"The feed: [$feedid] at url: [$url] is already in the repository.");
  return($feedid);
}


//_______________________________________________________________________________________
//Check if a given feed id is already in use in the feed table
function feed_id_exists($id = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The id is blank or corrupt: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the feed table
  $sql=$dbh->prepare("SELECT id FROM $table_newsfeed WHERE id=?") or print(mysql_error());
  $sql->bind_param("s", $id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The id: [$id] does not exist in the feed table.");
    return(FALSE);
  }
  $sql->bind_result($feedid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"The feed: [$id] is already in use in the feed table.");
  return(TRUE);
}


//_______________________________________________________________________________________
//Check if a feed links to an outlin
function get_feed_outline($fid = NULL, $uid = NULL)
{
  //Check parameters
  if(empty($fid)) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if(empty($uid)) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT outlineid FROM $table_nfcatalog WHERE userid=? AND feedid=?") or print(mysql_error());
  $sql->bind_param("ss", $uid, $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The feed: [$fid] does not contain an outline link for user: [$uid].");
    return(FALSE);
  }
  $sql->bind_result($oid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"The feed: [$fid] links to outline: [$oid] for user: [$uid].");
  return($oid);
}


//_______________________________________________________________________________________
//Check if a feed links to an outline for a given user
function get_feed_outline_by_user($fid = NULL, $uid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT outlineid FROM $table_nfcatalog WHERE (userid=? AND feedid=?) AND outlineid != '' AND outlineid IS NOT NULL") or print(mysql_error());
  $sql->bind_param("ss", $uid, $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    //loggit(1,"The feed: [$fid] does not link to an outline for user: [$uid].");
    return(FALSE);
  }
  $sql->bind_result($oid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"The feed: [$fid] links to outline: [$oid] for user: [$uid].");
  return($oid);
}


//_______________________________________________________________________________________
//See how many items are in the database for a given feed
function get_feed_subscriber_count($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT userid FROM $table_nfcatalog WHERE feedid=?") or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $subcount = $sql->num_rows();
  $sql->close() or print(mysql_error());

  //loggit(3,"The feed: [$fid] has: [$subcount] subscribers.");
  return($subcount);
}

//_______________________________________________________________________________________
//Get a list of users who subscribe to this feed
function get_feed_subscribers($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT userid FROM $table_nfcatalog WHERE feedid=?") or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $subcount = $sql->num_rows();
  if($subcount < 1) {
    return(FALSE);
  }

  //Put the feed ids in an array to send back
  $sql->bind_result($userid) or print(mysql_error());
  $users = array();
  $count = 0;
  while($sql->fetch()){
    $users[$count] = $userid;
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"The feed: [$fid] has: [$count] subscribers.");
  return($users);
}


//_______________________________________________________________________________________
//Get a list of feeds owned by an outline
function get_feeds_by_outline_id($id = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$includes/opml.php";

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //See if this is a list or an outline
  $oi = get_outline_info($id);

  //Look for the url in the feed table
  if( $oi['type'] == 'list' ) {
    $sql=$dbh->prepare("SELECT DISTINCT feedid FROM $table_listfeeds WHERE listid=?") or print(mysql_error());
    $sql->bind_param("s", $id) or print(mysql_error());
  } else {
    $sql=$dbh->prepare("SELECT DISTINCT feedid FROM $table_nfcatalog WHERE outlineid=?") or print(mysql_error());
    $sql->bind_param("s", $id) or print(mysql_error());
  }
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $subcount = $sql->num_rows();

  //Put the feed ids in an array to send back
  $sql->bind_result($feedid) or print(mysql_error());
  $feeds = array();
  $count = 0;
  while($sql->fetch()){
    $feeds[$count] = $feedid;
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$subcount] feeds linked to outline: [$id].");
  return($feeds);
}


//_______________________________________________________________________________________
//Get a list of pub feeds for this user
function get_pub_feeds($uid = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT userid,url,linkedon,title FROM $table_sopml_feed WHERE userid=?") or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $subcount = $sql->num_rows();
  if($subcount < 1) {
    loggit(1, "No pub feeds found for user: [$uid].");
    return(FALSE);
  }

  //Put stuff in an array and send back
  $sql->bind_result($userid,$url,$linkedon,$title) or print(mysql_error());
  $feeds = array();
  $count = 0;
  while($sql->fetch()){
    $feeds[$count] = array( 'userid' => $userid, 'url' => $url, 'linkedon' => $linkedon, 'title' => $title );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$subcount] pub feeds for user: [$uid].");
  return($feeds);
}


//_______________________________________________________________________________________
//See how many items are in the database for a given feed
function get_item_count($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT id FROM $table_nfitem WHERE feedid=?") or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $itemcount = $sql->num_rows();
  $sql->close() or print(mysql_error());

  loggit(1,"The feed: [$fid] has: [$itemcount] items.");
  return($itemcount);
}


//_______________________________________________________________________________________
//Retrieve a static river from the rivers table for the given user
function get_river($uid = NULL, $mobile = FALSE)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  if( $mobile == TRUE) {
    $sql=$dbh->prepare("SELECT mriver FROM $table_river WHERE userid=?") or print(mysql_error());
  } else {
    $sql=$dbh->prepare("SELECT river FROM $table_river WHERE userid=?") or print(mysql_error());
  }
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve static river for user: [$uid]");
    return(FALSE);
  }
  $sql->bind_result($sriver) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Unserialize it
  $river = unserialize($sriver);

  loggit(1,"Returning static river array for user: [$uid]");
  return( $river );
}


//Get the river formatted in JSONP
function get_river_as_json($uid = NULL, $mobile = FALSE)
{
  //Check parameters
  if( empty($uid) ) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  return( json_encode(get_river($uid, $mobile)) );
}


//_______________________________________________________________________________________
//Retrieve an array of info about the feed
function get_feed_info($id = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The feed id given is corrupt or blank: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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
                  $table_newsfeed.oid,
		  $table_newsfeed.pubdate,
                  $table_newsfeed.errors,
                  $table_newsfeed.avatarurl,
		  $table_newsfeed.id
           FROM $table_newsfeed
           WHERE $table_newsfeed.id=?";

  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve feed info for feed id: [$id]");
    return(FALSE);
  }
  $feed = array();
  $sql->bind_result($feed['url'], $feed['title'], $feed['content'], $feed['lastcheck'], $feed['lastupdate'],
                    $feed['lastmod'], $feed['createdon'], $feed['link'], $feed['rsscloudregurl'],
		    $feed['rsscloudlastreg'], $feed['updated'], $feed['lastitemid'], $feed['oid'], 
                    $feed['pubdate'], $feed['errors'], $feed['avatarurl'], $feed['id'] ) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"Returning feed info for feed: [$id]");
  return($feed);
}

//_______________________________________________________________________________________
//Retrieve an array of info about the feed
function get_feed_info_for_user($id = NULL, $uid = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The feed id given is corrupt or blank: [$id]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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

  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $id, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve feed info for feed id: [$id]");
    return(FALSE);
  }
  $feed = array();
  $sql->bind_result($feed['url'], $feed['title'], $feed['content'], $feed['lastcheck'], $feed['lastupdate'],
                    $feed['lastmod'], $feed['createdon'], $feed['link'], $feed['rsscloudregurl'],
		    $feed['rsscloudlastreg'], $feed['updated'], $feed['lastitemid'], $feed['oid'],
                    $feed['pubdate'], $feed['errors'], $feed['id'], $feed['sticky'], $feed['hidden'] ) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"Returning feed info for feed: [$id]");
  return($feed);
}


//_______________________________________________________________________________________
//Retrieve an array of info about the feed item
function get_feed_item_properties($id = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($id) ) {
    loggit(2,"The feed item id given is corrupt or blank: [$id]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $stmt = "SELECT sticky,hidden FROM $table_nfitemprop WHERE itemid=? AND userid=?";

  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $id, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    //loggit(2,"Failed to retrieve feed item properties for item: [$id]");
    return(FALSE);
  }
  $item = array();
  $sql->bind_result( $item['sticky'],$item['hidden'] ) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"Returning feed item properties for item: [$id]");
  return($item);
}


//_______________________________________________________________________________________
//Set an array of info about the feed item
function set_feed_item_properties($id = NULL, $uid = NULL, $props = array())
{
  //Check parameters
  if( empty($id) ) {
    loggit(2,"The feed item id given is corrupt or blank: [$id]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }
  if( empty($props) || !is_array($props) ) {
    loggit(2,"The property array given is corrupt or blank: [$props]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Sticky bit?
  if( isset($props['sticky']) && $props['sticky'] == TRUE ) {
    mark_feed_item_as_sticky($id, $uid);
  } else {
    unmark_feed_item_as_sticky($id, $uid);
  }
  //Hidden bit?
  if( isset($props['hidden']) && $props['hidden'] == TRUE ) {
    mark_feed_item_as_hidden($id, $uid);
  } else {
    unmark_feed_item_as_hidden($id, $uid);
  }

  //loggit(1,"Returning feed item properties for item: [$id]");
  return(TRUE);
}


//_______________________________________________________________________________________
//Retrieve an array of info about the river
function get_river_info($uid = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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

  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if we got a result
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve river info for user id: [$uid]");
    return(FALSE);
  }
  $river = array();
  $sql->bind_result( $river['id'], $river['userid'], $river['lastbuild'], $river['river'], $river['conthash'], $river['firstid'], $river['updated'] ) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(3,"Returning river info for user: [$uid]: ".print_r($river, TRUE));
  return($river);
}


//_______________________________________________________________________________________
//Add a feed to the database
function add_feed($url = NULL, $uid = NULL, $get = FALSE, $oid = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The article url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Timestamp
  $createdon = time();

  //Each feed needs a unique id
  $id = random_gen(64);
  while( feed_id_exists($id) ) {
    $id = random_gen(64);
  }

  //Does this feed exist already?
  $fid = feed_exists($url);
  if( $fid == FALSE ) {
    $existed = FALSE;
    //Now that we have a good id, put the article into the database
    $stmt = "INSERT INTO $table_newsfeed (id,url,createdon) VALUES (?,?,?)";
    $sql=$dbh->prepare($stmt) or print(mysql_error());
    $sql->bind_param("sss", $id,$url,$createdon) or print(mysql_error());
    $sql->execute() or print(mysql_error());
    $sql->close() or print(mysql_error());
  } else {
    $existed = TRUE;
    $id = $fid;
  }

  //Now link the feed id to this user's id if that was requested
  if($uid != NULL) {
    link_feed_to_user($id, $uid);
  }

  //Link this feed to an outline if that was requested
  if($oid != NULL) {
    link_feed_to_outline($id, $oid, $uid);
  }

  //Was feed item retrieval requested?
  if($get == TRUE && $existed == FALSE) {
    get_feed_items($id, $default_new_subscription_item_count);
  }

  //Log and return
  if($existed == TRUE) {
  	loggit(1,"Feed: [$id] with url [$url] already existed in the database.");
  } else {
  	loggit(1,"Put a new feed in the repository: [$id] with url [$url].");
  }
  return($id);
}

//_______________________________________________________________________________________
//Change the title of a feed
function update_feed_title($fid = NULL, $title = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($title == NULL) {
    loggit(2,"The feed title is blank or corrupt: [$title]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Deal with blank feed titles
  if( empty($title) ) {
    $title = "Untitled Feed";
  } else {
    $title = trim( htmlspecialchars( $title ) );
  }

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET title=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $title,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s title to: [$title].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Change the avatar url of a feed
function update_feed_avatar($fid = NULL, $url = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Deal with blank feed titles
  if( empty($url) ) {
    $url = "";
  } else {
    $url = trim( htmlspecialchars( $url ) );
  }

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET avatarurl=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $url,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s avatar url to: [$url].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Initialize an empty set of stats for this feed
function init_feed_stats($fid = NULL)
{

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Make sure uid isn't empty
  if($fid == NULL) {
    loggit(2,"The given feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $stmt = "INSERT IGNORE INTO $table_feedstats (id) VALUES (?)";
  $sql = $dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Get the new stats record
  if($updcount > 0) {
    $stats = get_feed_stats($fid);
  } else {
    $stats = FALSE;
  }

  //Log and return
  //loggit(3,"Initialized a default set of stats for feed: [$fid]");
  return($stats);
}



//_______________________________________________________________________________________
//Change the stats for the feed
function set_feed_stats($fid = NULL, $stats = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if( empty($stats) ) {
    loggit(2,"The stats array is blank or corrupt: [$stats]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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
  $sql=$dbh->prepare($stmt) or print(mysql_error());
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
  ) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Set stats for feed: [$fid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Return an array of all the stats for this feed
function get_feed_stats($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"Feed id given is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli_Extended($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Run the query
  $sql=$dbh->prepare("SELECT * FROM $table_feedstats WHERE id=?") or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() != 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"This feed has no stats: [$fid]");
    return(init_feed_stats($fid));
  }
  $stats = $sql->fetch_assoc();
  $sql->close() or print(mysql_error());

  loggit(1,"Returning stats array for feed: [$fid]");
  return($stats);
}



//_______________________________________________________________________________________
//Change the reported pub date of a feed
function update_feed_pubdate($fid = NULL, $pubdate = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($pubdate == NULL) {
    loggit(2,"The pubdate is blank or corrupt: [$pubdate]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET pubdate=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $pubdate,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s pubdate to: [$pubdate].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Change the rsscloud registration url for a feed
function update_feed_rsscloud_regurl($fid = NULL, $url = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($url == NULL) {
    loggit(2,"The rsscloud reg url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET rsscloudregurl=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $url,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s rssCloud registration url to: [$url].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the rsscloud lastreg timestamp for a feed
function update_feed_rsscloud_lastreg($fid = NULL, $time = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($time == NULL) {
    loggit(2,"The time is blank or corrupt: [$time]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET rsscloudlastreg=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $time,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s rssCloud lastreg time to: [$time].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the rsscloud lastreg timestamp for a feed
function update_feed_rsscloud_reglastresp($fid = NULL, $resp = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($resp == NULL) {
    loggit(2,"The response string is blank or corrupt: [$resp]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET rsscloudreglastresp=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $resp,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s rssCloud last response text to: [$resp].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Change the lastupdate column of a feed
function update_feed_lastupdate($fid = NULL, $time = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($time == NULL) {
    loggit(2,"The time is blank or corrupt: [$time]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET lastupdate=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $time,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s lastupdate time to: [$time].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Increment the error count on this feed
function increment_feed_error_count($fid = NULL)
{
  //Check parameters
  if(empty($fid)) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET errors=errors+1 WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Incremented error count for feed:[$fid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Reset feed error count to zero
function reset_feed_error_count($fid = NULL)
{
  //Check parameters
  if(empty($fid)) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET errors=0 WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Reset error count for feed:[$fid] to zero.");
  return(TRUE);
}


//_______________________________________________________________________________________
//Change the lastcheck column of a feed
function update_feed_lastcheck($fid = NULL, $time = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($time == NULL) {
    loggit(2,"The time is blank or corrupt: [$time]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET lastcheck=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $time,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s lastcheck time to: [$time].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Flip the flag on items marked purge to mark as old instead
function flip_purge_to_old($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfitem SET `old`=1 WHERE feedid=? AND `purge`=1";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flipped: [$updcount] items in feed:[$fid] from purge to old.");
  return($updcount);
}

//_______________________________________________________________________________________
//Sets a feed's sticky bit to on in the newsfeed catalog
function mark_feed_as_sticky($fid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET sticky=1 WHERE feedid=? AND userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged feed: [$fid] as sticky for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Sets a feed's sticky bit to off in the newsfeed catalog
function unmark_feed_as_sticky($fid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET sticky=0 WHERE feedid=? AND userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged feed: [$fid] as sticky for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Sets a feed item's sticky bit to true
function mark_feed_item_as_sticky($iid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($iid) ) {
    loggit(2,"The feed item id is blank or corrupt: [$iid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "INSERT INTO $table_nfitemprop (itemid, userid, sticky) VALUES (?,?,1) ON DUPLICATE KEY UPDATE sticky=1";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $iid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged item: [$iid] as sticky for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Sets a feed item's sticky bit to false
function unmark_feed_item_as_sticky($iid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($iid) ) {
    loggit(2,"The feed item id is blank or corrupt: [$iid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfitemprop SET sticky=0 WHERE itemid=? AND userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $iid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Un-flagged item: [$iid] as sticky for user: [$uid].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Sets a feed's hidden bit to on in the newsfeed catalog
function mark_feed_as_hidden($fid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET hidden=1 WHERE feedid=? AND userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged feed: [$fid] as hidden for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Sets a feed's hidden bit to off in the newsfeed catalog
function unmark_feed_as_hidden($fid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET hidden=0 WHERE feedid=? AND userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged feed: [$fid] as hidden for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Sets a feed item's hidden bit to true
function mark_feed_item_as_hidden($iid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($iid) ) {
    loggit(2,"The feed item id is blank or corrupt: [$iid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "INSERT INTO $table_nfitemprop (itemid, userid, hidden) VALUES (?,?,1) ON DUPLICATE KEY UPDATE hidden=1";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $iid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged item: [$iid] as hidden for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Sets a feed item's hidden bit to false
function unmark_feed_item_as_hidden($iid = NULL, $uid = NULL)
{
  //Check parameters
  if( empty($iid) ) {
    loggit(2,"The feed item id is blank or corrupt: [$iid]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfitemprop SET hidden=0 WHERE itemid=? AND userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $iid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Un-flagged item: [$iid] as hidden for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Flip flag on a river to signify it needs rebuilding
function mark_river_as_updated($uid = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_river SET updated=1 WHERE userid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1, "Flagged river for user: [$uid] as needing to be rebuilt.");
  return($updcount);
}


//_______________________________________________________________________________________
//Flip the flag on a newsfeed to make it known that it needs updating
function mark_feed_as_updated($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET updated=1 WHERE id=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Flagged feed: [$fid] as needing to be scanned.");
  return($updcount);
}



//_______________________________________________________________________________________
//Flip the flag on a newsfeed to make it known that it does NOT need updating
function unmark_feed_as_updated($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET updated=0 WHERE id=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  //loggit(3,"Cleared update flag on feed: [$fid].");
  return($updcount);
}


//_______________________________________________________________________________________
//Mark a feed's items as purge
function mark_all_feed_items_to_purge($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfitem SET `purge`=1 WHERE feedid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Marked: [$updcount] items in feed:[$fid] to purge.");
  return($updcount);
}


//_______________________________________________________________________________________
//Unmark a feed's items as purge
function unmark_all_feed_items_to_purge($fid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Update all the items
  $stmt = "UPDATE $table_nfitem SET `purge`=0 WHERE feedid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s",$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Un-marked: [$updcount] items in feed:[$fid] to purge.");
  return($updcount);
}

//_______________________________________________________________________________________
//Mark an outline's feeds to purge for a certain user
function mark_all_outline_feeds_to_purge($oid = NULL)
{
  //Check parameters
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET `purge`=1 WHERE outlineid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Marked: [$updcount] feeds to purge for outline: [$oid].");
  return($updcount);
}


//_______________________________________________________________________________________
//Un-mark an outline's feeds to purge for a certain user
function unmark_all_outline_feeds_to_purge($oid = NULL)
{
  //Check parameters
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET `purge`=0 WHERE outlineid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Un-marked: [$updcount] feeds to purge for outline: [$oid].");
  return($updcount);
}

//_______________________________________________________________________________________
//Mark an outline feed to purge
function mark_outline_feed_to_purge($oid = NULL, $fid = NULL)
{
  //Check parameters
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET `purge`=1 WHERE outlineid=? AND feedid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $oid, $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  if($updcount < 1) {
    loggit(2,"Failed to mark feed: [$fid] to purge for outline: [$oid].");
    return(FALSE);
  } else {
    loggit(1,"Marked feed: [$fid] to purge for outline: [$oid].");
    return(TRUE);
  }
}


//_______________________________________________________________________________________
//Un-mark an outline feed to purge
function unmark_outline_feed_to_purge($oid = NULL, $fid = NULL)
{
  //Check parameters
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfcatalog SET `purge`=0 WHERE outlineid=? AND feedid=?";
  $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
  $sql->bind_param("ss", $oid, $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  if($updcount < 1) {
    loggit(2,"Failed to unmark feed: [$fid] to purge for outline: [$oid].");
    return(FALSE);
  } else {
    loggit(1,"Unmarked feed: [$fid] to purge for outline: [$oid].");
    return(TRUE);
  }
}


//_______________________________________________________________________________________
//Mark a feed item as purge
function mark_feed_item_to_purge($fid = NULL, $guid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The item feed it is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($guid == NULL) {
    loggit(2,"The item guid is blank or corrupt: [$guid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfitem SET `purge`=1 WHERE feedid=? AND guid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss",$fid,$guid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  if($updcount < 1) {
    //loggit(2,"Failed to mark feed item: [$guid] to purge for feed: [$fid].");
    return(FALSE);
  } else {
    //loggit(1,"Marked feed item: [$guid] to purge for feed: [$fid].");
    return(TRUE);
  }
}

//_______________________________________________________________________________________
//Unmark a feed item as purge
function unmark_feed_item_to_purge($fid = NULL, $guid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The item feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($guid == NULL) {
    loggit(2,"The item guid is blank or corrupt: [$guid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_nfitem SET `purge`=0 WHERE feedid=? AND guid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss",$fid,$guid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $updcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  if($updcount < 1) {
    //loggit(2,"Failed to unmark feed item: [$guid] to purge for feed: [$fid].");
    return(FALSE);
  } else {
    loggit(1,"Unmarked feed item: [$guid] to purge for feed: [$fid].");
    return(TRUE);
  }
}


//_______________________________________________________________________________________
//Change the link of a feed
function update_feed_link($fid = NULL, $link = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($link == NULL) {
    loggit(2,"The feed link is blank or corrupt: [$link] for feed: [$fid].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET link=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $link,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Changed feed:[$fid]'s link to: [$link].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the content of a feed
function update_feed_content($fid = NULL, $content = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($content == NULL) {
    loggit(2,"The feed content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET content=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $content,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated content for feed: [$fid].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the last modified time of a feed
function update_feed_lastmod($fid = NULL, $lastmod = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($lastmod == NULL) {
    loggit(2,"The feed last-modified time is blank or corrupt: [$lastmod]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_newsfeed SET lastmod=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $lastmod,$fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated last-modified time to: [$lastmod] for feed: [$fid].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Check if a feed is sticky for a given user
function feed_is_sticky($fid = NULL, $uid = NULL)
{
  //Check parameters
  if(empty($fid)) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if(empty($uid)) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT sticky FROM $table_nfcatalog WHERE feedid=? AND userid=? AND sticky=1") or print(mysql_error());
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    //loggit(3,"The feed: [$fid] is NOT sticky for user: [$uid].");
    return(FALSE);
  }
  $sql->close() or print(mysql_error());

  //loggit(3,"The feed: [$fid] is sticky for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Check if a feed is hidden for a given user
function feed_is_hidden($fid = NULL, $uid = NULL)
{
  //Check parameters
  if(empty($fid)) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if(empty($uid)) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT hidden FROM $table_nfcatalog WHERE feedid=? AND userid=? AND hidden=1") or print(mysql_error());
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    //loggit(3,"The feed: [$fid] is NOT hidden for user: [$uid].");
    return(FALSE);
  }
  $sql->close() or print(mysql_error());

  //loggit(3,"The feed: [$fid] is hidden for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Check if a feed is linked to a user
function feed_is_linked($fid = NULL, $uid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT * FROM $table_nfcatalog WHERE feedid=? AND userid=?") or print(mysql_error());
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The feed: [$fid] is not linked to user: [$uid].");
    return(FALSE);
  }
  $sql->close() or print(mysql_error());

  loggit(1,"The feed: [$fid] is already linked to user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Check if a feed is linked to a user
function feed_is_linked_by_url($url = NULL, $uid = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The feed url is blank or corrupt: [$url]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the feed id from the url
  $fid = feed_exists($url);
  if($fid == FALSE) {
    loggit(1, "Feed: [$url] not in database.");
    return(FALSE);
  }

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT * FROM $table_nfcatalog WHERE feedid=? AND userid=?") or print(mysql_error());
  $sql->bind_param("ss", $fid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The feed: [$fid] is not linked to user: [$uid].");
    return(FALSE);
  }
  $sql->close() or print(mysql_error());

  loggit(1,"The feed: [$fid] is already linked to user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Link a feed to a user
function link_feed_to_user($fid = NULL, $uid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  $tstamp = time();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Link the article to the user
  $stmt = "INSERT INTO $table_nfcatalog (userid,feedid,linkedon) VALUES (?,?,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("sss", $uid,$fid,$tstamp) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Linked feed: [$fid] with user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Add a pub feed to an sopml outline
function add_pub_feed($url = NULL, $uid = NULL, $title = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The url is blank or corrupt: [$url]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if($title == NULL) {
    loggit(2,"The feed title is blank or corrupt: [$title]. Setting to untitled.");
    $title = "Untitled feed.";
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  $tstamp = time();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Link the article to the user
  $stmt = "INSERT INTO $table_sopml_feed (userid,url,linkedon,title) VALUES (?,?,?,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssss", $uid,$url,$tstamp,$title) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Linked feed: [$url | $title] with user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Delete a published feed from an sopml outline
function del_pub_feed($uid = NULL, $url = NULL)
{
  //Check params
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if( empty($url) ) {
    loggit(2,"The url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_sopml_feed WHERE userid=? AND url=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $uid, $url) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  if($delcount < 1) {
    loggit(2,"Failed to delete feed: [$url] from sopml feeds table for user: [$uid].");
    return(FALSE);
  }

  //Log and leave
  loggit(3,"Deleted feed: [$url] from sopml feed table for user: [$uid].");
  return(TRUE);
}



//_______________________________________________________________________________________
//Link a feed to an outline
function link_feed_to_outline($fid = NULL, $oid = NULL, $uid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  $tstamp = time();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Link the feed to the user
  $stmt = "INSERT INTO $table_nfcatalog (userid, feedid, outlineid, linkedon) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE outlineid=?, linkedon=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssssss", $uid,$fid,$oid,$tstamp,$oid,$tstamp) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Linked feed: [$fid] with outline: [$oid] for user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Link feed to a list
function link_feed_to_list($fid = NULL, $oid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  $tstamp = time();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Link the feed to the user
  $stmt = "INSERT INTO $table_listfeeds (listid, feedid, linkedon) VALUES (?,?,?) ON DUPLICATE KEY UPDATE linkedon=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssss", $oid,$fid,$tstamp,$tstamp) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Linked feed: [$fid] with list: [$oid].");
  return(TRUE);
}



//_______________________________________________________________________________________
//Retrieve a list of feeds subscribed to by the given user
function get_feeds($uid = NULL, $max = NULL, $ididx = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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
                    $table_nfcatalog.hidden
	     FROM $table_newsfeed,
                  $table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_newsfeed.id)";
  //$sqltxt .= " ORDER BY $table_nfcatalog.sticky DESC,$table_newsfeed.title ASC";
  $sqltxt .= " ORDER BY $table_newsfeed.title ASC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"This user has not subscribed to any feeds: [$uid]");
    return(FALSE);
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
                    $fhidden) or print(mysql_error());

  $feeds = array();
  $count = 0;
  while($sql->fetch()){
    if($ididx == TRUE) {
      $idx = $fid;
    } else {
      $idx = $count;
    }

    $feeds[$idx] = array( 'id' => $fid,
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
                          'hidden' => $fhidden );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] feeds for user: [$uid]");
  return($feeds);
}


//_______________________________________________________________________________________
//Retrieve a list of feeds subscribed to by the given user that match a query text
function search_feeds($uid = NULL, $query = NULL, $max = NULL, $ididx = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }
  if($query == NULL) {
    loggit(2,"The query given is corrupt or blank: [$query]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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
                    $table_nfcatalog.hidden
	     FROM $table_newsfeed,
                  $table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_newsfeed.id)
             AND ($table_newsfeed.title LIKE CONCAT('%', ?, '%')
             OR $table_newsfeed.url LIKE CONCAT('%', ?, '%')
             OR $table_newsfeed.link LIKE CONCAT('%', ?, '%'))";
  $sqltxt .= " ORDER BY $table_newsfeed.title ASC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("ssss", $uid, $query, $query, $query) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"This user has not subscribed to any feeds: [$uid]");
    return(FALSE);
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
                    $fhidden) or print(mysql_error());

  $feeds = array();
  $count = 0;
  while($sql->fetch()){
    if($ididx == TRUE) {
      $idx = $fid;
    } else {
      $idx = $count;
    }

    $feeds[$idx] = array( 'id' => $fid,
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
                          'hidden' => $fhidden );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] feeds for user: [$uid] matching query: [$query].");
  return($feeds);
}



//_______________________________________________________________________________________
//Unlink a feed from a user
function unlink_feed_from_user($uid = NULL, $fid = NULL)
{
  //Check params
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_nfcatalog WHERE userid=? AND feedid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $uid, $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Unlinked: [$delcount] feeds: [$fid] from user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Unlink a feed from an outline
function unlink_feed_from_outline($fid = NULL, $oid = NULL)
{
  //Check params
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($oid == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the table and kill it
  $stmt = "UPDATE $table_nfcatalog SET outlineid=NULL WHERE feedid=? AND outlineid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $fid, $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Unlinked feed: [$fid] from outline: [$oid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Unlink a feed from a list
function unlink_feed_from_list($fid = NULL, $lid = NULL)
{
  //Check params
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($lid == NULL) {
    loggit(2,"The list id is blank or corrupt: [$lid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the table and kill it
  $stmt = "DELETE FROM $table_listfeeds WHERE feedid=? AND listid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $fid, $lid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  //loggit(3,"Unlinked feed: [$fid] from list: [$lid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Remove the specified number of items from a feed
function trim_feed($fid = NULL, $max = NULL)
{
  //Check params
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($max == NULL) {
    loggit(2,"The max count is blank or corrupt: [$aid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_nfitem WHERE feedid=? ORDER BY timeadded DESC LIMIT ?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $fid, $max) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Deleted: [$delcount] items from feed: [$fid].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Remove feed items over a certain alloted amount based on date
function trim_feed_items($fid = NULL, $max = NULL)
{
  //Check params
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($max == NULL) {
    loggit(2,"The max item count is blank or corrupt: [$max]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_nfitem WHERE id NOT IN (SELECT id FROM (SELECT id FROM $table_nfitem ORDER BY timestamp DESC LIMIT ?) x) AND feedid=?;";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $max, $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Deleted: [$delcount] items from feed: [$fid].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Purge items from the items table that have the purge flag set
function purge_feed_items($fid = NULL)
{
  //Check params
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_nfitem WHERE feedid=? AND `purge`=1;";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Deleted: [$delcount] items from feed: [$fid].");
  return($delcount);
}


//_______________________________________________________________________________________
//Delete old feed items from the items table that have the old flag
function delete_old_feed_items($fid = NULL)
{
  //Check params
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  $thirtydaysago = time() - 7776000;

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_nfitem WHERE feedid=? AND `old`=1 AND timeadded < ?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $fid, $thirtydaysago) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  if($delcount > 0) {
    loggit(1,"Deleted: [$delcount] old items from feed: [$fid].");
  }
  return($delcount);
}


//_______________________________________________________________________________________
//Delete all the items for a particular feed
function delete_feed_items($fid = NULL)
{
  //Check params
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE nfi.*,nfp.* FROM $table_nfitem nfi INNER JOIN $table_nfitemprop nfp ON nfi.id = nfp.itemid WHERE nfi.feedid=? ";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  if($delcount > 0) {
    loggit(3,"Deleted: [$delcount] items for orphaned feed: [$fid].");
  }
  return($delcount);
}


//_______________________________________________________________________________________
//Delete a feed
function delete_feed($fid = NULL)
{
  //Check params
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_newsfeed WHERE id=? ";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  if($delcount < 1) {
    loggit(2,"Failed to delete feed: [$fid] from feed table.");
    return(FALSE);
  }

  //Log and leave
  loggit(3,"Deleted feed: [$fid] from feed table.");
  return(TRUE);
}


//_______________________________________________________________________________________
//Remove all feeds in the catalog table marked as purge for this outline
function purge_outline_feeds($oid = NULL)
{
  //Check params
  if(empty($oid)) {
    loggit(2,"The outline id is blank or corrupt: [$oid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_nfcatalog WHERE `purge`=1 AND outlineid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Deleted: [$delcount] catalog links for outline: [$oid].");
  return($delcount);
}


//_______________________________________________________________________________________
//Get and parse out the content of an RSS feed
function get_feed_items($fid = NULL, $max = NULL)
{
  //Check params
  if( empty($fid) ) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if( empty($max) ) {
    //loggit(2,"No max value declared, so set it high: [9,999].");
    $max = 9999;
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Un-flag this feed
  unmark_feed_as_updated($fid);

  //Get the content of the feed
  $feed = get_feed_info($fid);
  $stats = get_feed_stats($fid);
  $stats['subscribers'] = get_feed_subscriber_count($fid);
  $stats['checkcount']++;
  $fstart = time();
  $url = $feed['url'];

  //If a feed has over 100 errors, we fall back to only scanning it once a day
  if( $feed['errors'] > 100 ) {
    if( (time() - $feed['lastcheck']) < 86400 ) {
      loggit(2, "Feed: [$url] is over the error limit.  Skipping for 24 hours.");
      $stats['checktime'] += (time() - $fstart);
      set_feed_stats($fid, $stats);
      return(-1);
    } else {
      loggit(3, "DEBUG: Doing once per day check on error-prone feed: [$url].");
    }
  }

  //Do we need to re-register?
  if( !empty($feed['rsscloudregurl']) && ((time() - $feed['rsscloudlastreg']) > 86400 || $feed['rsscloudlastreg'] == '') ) {
    $rcregurl = parse_url($feed['rsscloudregurl']);
    //debug
    loggit(1, "RSSCLOUD: Feed: [$url] was last registered ".(time() - $feed['rsscloudlastreg'])." seconds ago. Time to renew.");
    update_feed_rsscloud_lastreg($fid, time());

    //Send the registration post if rssCloud is enabled AND the feed has been updated within the last 2 weeks
    //We don't want to keep cloud registrations in place for feeds that rarely update
    if( $enable_rsscloud == 1 && (time() - $feed['lastupdate']) < 1209600 ) {
      loggit(1, "RSSCLOUD: Registering at: [".$rcregurl['host']."] for feed [$fid].");
      $resp = httpRequest($rcregurl['host'], $rcregurl['port'], "POST", $rcregurl['path'], array(  "notifyProcedure" => '', "port" => '80', "path" => '/cgi/in/ping',
                          	                                   "protocol" => 'http-post', "url1" => $url, "domain" => $rss_cloud_url )
      );
      $resp = trim(htmlentities(strip_tags($resp)));
      loggit(1, "RSSCLOUD: Registration response: [$resp].");
      update_feed_rsscloud_reglastresp($fid, $resp);
    }
  }

  //Let's do some intelligent header checking so we don't waste time and bandwidth
  update_feed_lastcheck($fid, time());
  $lastmodtime = check_head_lastmod($url);
  if( ($lastmodtime == $feed['lastmod']) && ($lastmodtime != FALSE) ) {
    loggit(1, "Feed: [($url) $fid] hasn't been updated. Skipping.");
    $stats['checktime'] += (time() - $fstart);
    set_feed_stats($fid, $stats);
    return(-3);
  }

  //Feed has been changed so grab the new content
  if( !empty($lastmodtime) ) {
    update_feed_lastmod($fid, $lastmodtime);
  }
  $goodurl = get_final_url($url);
  if($goodurl != $url) {
    loggit(3, "DEBUG: Re-direct: Feed: [$url] re-directs to: [$goodurl].");
  }
  $tstart = time();
  $content = fetchUrl($goodurl);
  //loggit(3, "FEED SCAN: GET request took [".(time() - $tstart)."] seconds.");
  if($content == FALSE) {
    loggit(2, "Error attempting to get url: [$url]. See log for details.");
    increment_feed_error_count($fid);
    $stats['checktime'] += (time() - $fstart);
    set_feed_stats($fid, $stats);
    return(-1);
  }
  update_feed_content($fid, $content);

  //Parse it
  $tstart = time();
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
  //loggit(3, "FEED SCAN: SimpleXML parse load took [".(time() - $tstart)."] seconds.");

  //Was there a fatal error during parsing?
  if(!$x) {
    loggit(1, "Failed to parse XML for feed: [$fid].  Let's run it through Tidy() and try again.");
    $tidy = new tidy();
    $xr = $tidy->repairString($content, array('output-xml' => true, 'input-xml' => true));
    $x = simplexml_load_string($xr, 'SimpleXMLElement', LIBXML_NOCDATA);
    if(!$x) {
      loggit(1, "Error parsing feed XML for feed: [$fid].  Incrementing error count and skipping feed: [$fid].");
      $stats['checktime'] += (time() - $fstart);
      set_feed_stats($fid, $stats);
      increment_feed_error_count($fid);
      return(-1);
    }
  }

  //Look for some kind of publish date
  if( !empty($x->channel->pubDate) ) {
    $pubdate = $x->channel->pubDate;
  } else if ( !empty($x->channel->lastBuildDate) ) {
    $pubdate = $x->channel->lastBuildDate;
  } else if ( !empty($x->updated) ) {
    $pubdate = $x->updated;
  } else {
    $pubdate = time();
  }
  if( $feed['pubdate'] == $pubdate ) {
    //The feed says that it hasn't been updated
    loggit(1, "The pubdate in the feed has not changed.");
    $stats['checktime'] += (time() - $fstart);
    set_feed_stats($fid, $stats);
    return(-3);
  }
  update_feed_pubdate($fid, $pubdate);

  //Freshen feed title
  if( isset($x->channel->title) ) {
    $ftitle = $x->channel->title;
  } else {
    $ftitle = $x->title;
  }
  update_feed_title($fid, $ftitle);

  //Freshen feed link
  if( isset($x->channel->link) ) {
    $flink = $x->channel->link;
  } else {
    $flink = (string)$x->link->attributes()->href;
  }
  update_feed_link($fid, $flink);

  //Freshen feed avatar
  update_feed_avatar($fid, get_feed_avatar($x));

  //Does this feed support rssCloud?
  if( isset($x->channel->cloud) ) {
    //loggit(3, "RSSCLOUD REG: Feed [$fid] has a cloud element.");
    $domain = (string)$x->channel->cloud->attributes()->domain;
    $port = (string)$x->channel->cloud->attributes()->port;
    $path = (string)$x->channel->cloud->attributes()->path;
    $regurl = $domain.":".$port.$path;
    update_feed_rsscloud_regurl($fid, $regurl);
  }

  //Pass any namespaces to the item add routine
  $namespaces = $x->getDocNamespaces(TRUE);

  //Mark all of this feed's items as purge
  mark_all_feed_items_to_purge($fid);

  //Put all of the items in an array
  $tstart = time();
  $items = array();
  $count = 0;
  $newcount = 0;
  if(empty($x->channel->item)) {
    //This is an atom feed
    foreach($x->entry as $entry) {
      $items[$count] = $entry;
      if(!feed_item_exists($fid, $entry->id)) {  //testing
        update_feed_lastupdate($fid, time());
        add_feed_item($fid, $entry, "atom", $namespaces);
        $newcount++;
        if($newcount > $max) {
	  mark_feed_item_to_purge($fid, $entry->id);
        }
      } else {
        unmark_feed_item_to_purge($fid, $entry->id);
      }
      $count++;
    }
  } else {
    //This is an rss feed
    foreach($x->channel->item as $entry) {
      $items[$count] = $entry;
      if( !feed_item_exists( $fid, get_unique_id_for_feed_item($entry) ) ) {  //testing
        update_feed_lastupdate($fid, time());
        add_feed_item($fid, $entry, "rss", $namespaces);
        $newcount++;
        if($newcount > $max) {
	  mark_feed_item_to_purge($fid, $entry->guid);
        }
      } else {
        unmark_feed_item_to_purge($fid, $entry->guid);
      }
      $count++;
    }
  }
  //loggit(3, "FEED SCAN: Feed item storage took [".(time() - $tstart)."] seconds.");

  //Flip the purge flags to old
  flip_purge_to_old($fid);

  //Delete old items
  delete_old_feed_items($fid);

  //Evidently the scan was successful so reset the error counter
  reset_feed_error_count($fid);

  //Calculate new stats for this feed
  $stats['checktime'] += (time() - $fstart);
  $stats['avgchecktime'] = ($stats['checktime'] / $stats['checkcount']);
  $stats['newitems'] += $newcount;
  $stats['avgnewitems'] = ($stats['newitems'] / $stats['checkcount']);
  $stats['avgnewinterval'] = ($stats['checkcount'] / $stats['newitems']);
  $stats['lastnewtime'] = $fstart;
  set_feed_stats($fid, $stats);

  //Is the feed empty?
  if ($count == 0) {
    loggit(1, "Scan: There were no items in this feed: [$url].");
    return(-2);
  }


  //Log and leave
  loggit(1,"Scan: [$newcount] out of: [$count] items from feed: [$url] were new.");
  return($newcount);
}


//_______________________________________________________________________________________
//Retrieve a list of all the feeds in the database
function get_all_feeds($max = NULL)
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the
  $sqltxt = "SELECT id,title,url,createdon FROM $table_newsfeed ORDER BY $table_newsfeed.errors ASC, $table_newsfeed.lastcheck ASC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"There are no feeds in the system.");
    return(FALSE);
  }

  $sql->bind_result($fid,$ftitle,$furl,$fcreatedon) or print(mysql_error());

  $feeds = array();
  $count = 0;
  while($sql->fetch()){
    $feeds[$count] = array( 'id' => $fid, 'title' => $ftitle, 'url' => $furl, 'createdon' => $fcreatedon );
    $count++;
  }

  $sql->close() or print(mysql_error());

  //loggit(1,"Returning: [$count] feeds in the system.");
  return($feeds);
}


//_______________________________________________________________________________________
//Retrieve a list of all the feeds in the database with their stats
function get_all_feeds_with_stats($max = NULL)
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli_Extended($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the
  $sqltxt = "SELECT *
             FROM $table_newsfeed
	     LEFT JOIN $table_feedstats ON $table_newsfeed.id = $table_feedstats.id
             ORDER BY $table_feedstats.subscribers DESC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"There are no feeds in the system.");
    return(FALSE);
  }

  $feeds = array();
  $count = 0;
  while($feedrow = $sql->fetch_assoc()){
    $feeds[$count] = $feedrow;
    $count++;
  }

  $sql->close() or print(mysql_error());

  //loggit(1,"Returning: [$count] feeds in the system.");
  return($feeds);
}


//_______________________________________________________________________________________
//Retrieve a list of all the feeds flagged as needing to be re-scanned
function get_updated_feeds($max = NULL)
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the
  $sqltxt = "SELECT id,title,url,createdon FROM $table_newsfeed WHERE updated=1";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds returned
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    //loggit(2,"There are no feeds that need scanning.");
    return(FALSE);
  }

  $sql->bind_result($fid,$ftitle,$furl,$fcreatedon) or print(mysql_error());

  $feeds = array();
  $count = 0;
  while($sql->fetch()){
    $feeds[$count] = array( 'id' => $fid, 'title' => $ftitle, 'url' => $furl, 'createdon' => $fcreatedon );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] updated feeds that need scanning.");
  return($feeds);
}


//_______________________________________________________________________________________
//Add a feed item to the item storage table
function add_feed_item($fid = NULL, $item = NULL, $format = NULL, $namespaces = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($item == NULL) {
    loggit(2,"The item is blank or corrupt: [$item]");
    return(FALSE);
  }

  //Debug
  //loggit(3, print_r($item, true));

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Timestamp
  $timeadded = time();

  //Each item needs a unique id
  $id = random_gen(128);
  $old = FALSE;

  //Find a publish date for the item
  if($format == "atom") {
	  $pubdate = strtotime($item->updated);
  } else {
	  $pubdate = strtotime($item->pubDate);
  }

  //Deal with old items that come back into the feed.  Yes, some feeds do this.
  if( !empty($pubdate) ) {
    //If this feed item reports a pubdate of greater than 7 days ago, then set
    //timeadded to same value as pubdate.  This keeps old items from re-appearing as new.
    if( $pubdate < (time() - 604800) ) {
      loggit(1, "OLD ITEM: Setting timeadded: [$timeadded] to pubdate: [$pubdate] on item id: [$id].");
      $timeadded = $pubdate;
      $old = TRUE;
    }
  }

  //Now that we have a good id, put the feed item into the database
  $stmt = "INSERT INTO $table_nfitem (id,feedid,title,url,description,guid,timestamp,timeadded,enclosure,`purge`,sourceurl,sourcetitle,author) VALUES (?,?,?,?,?,?,?,?,?,0,?,?,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  if($format == "atom") {
    //-----ATOM--------------------------------------------------------------------------------------------------------------------------------------------------
    $mcount = count($item->link);

    $description = $item->summary;
    if( isset($item->content) ) {
      $description = (string)$item->content;
      //loggit(3, "DEBUG - ATOM content: [".print_r($item->content, TRUE)."].");
    }

    //Find links and enclosures
    $linkurl = "#";
    $enclosures = array();
    for($lcount = 0 ; $lcount < $mcount ; $lcount++) {
      //Alternate is the main link
      if($item->link[$lcount]['rel'] == "alternate") {
        $linkurl = $item->link[$lcount]['href'];
      }
      //Enclosures are links also
      if($item->link[$lcount]['rel'] == "enclosure") {
        $enclosures[] = array( 'url' => (string)$item->link[$lcount]->attributes()->href,
			       'length' => (string)$item->link[$lcount]->attributes()->length,
                               'type' => (string)$item->link[$lcount]->attributes()->type
        );
        //loggit(3, "Found an ATOM enclosure: [".print_r($enclosures, true)."].");
        //loggit(3, "Item struct: [".print_r($item, true)."].");
      }
    }

    //Fix up twitter links
    if(strpos($linkurl, 'twitter.com') !== FALSE) {
	//Search for an embedded link in the description
	$title = $item->title;
	if(strpos($title, 'http:') !== FALSE) {
		preg_match('/\bhttp\:\/\/([A-Za-z0-9\.\/\+\&\@\~\-\%\?\=\_\#\!]*)/i', $title, $twurl);
		$linkurl = $twurl[0];
		//loggit(3, "I think this: [$linkurl] might be the twitter link.");
		//loggit(3, print_r($twurl, TRUE));
	}
    }

    //Clean the incoming description
    $cleaned = clean_feed_item_content($description, 0, TRUE, TRUE);
    $description = $cleaned['text'];

    //Attach extracted media tags as enclosures with correct type
    foreach( $cleaned['media'] as $mediatag ) {
      $esize = "";
      if( $mediatag['type'] == 'image' || $mediatag['type'] == 'audio' || $mediatag['type'] == 'video' ) {
        $esize = check_head_size($mediatag['src']);
        //loggit(3, "DEBUG ENCLOSURE SIZE: [$esize] for url: [".$mediatag['src']."]");
      }
      if( empty($esize) || $esize > 2500) {
        $enclosures[] = array( 'url' => $mediatag['src'], 'length' => $esize, 'type' => $mediatag['type'] );
      } else {
        //loggit(3, "  DISCARDING TINY ENCLOSURE: [$esize] for url: [".$mediatag['src']."]");
      }
    }

    //Serialize enclosures
    $enclosure = serialize($enclosures);

    //De-relativize links
    $httploc = strpos($linkurl, 'http');
    if( $httploc === FALSE || $httploc > 1 ) {
        $linkurl = '';
    }

    //debug
    //loggit(3, "NEW ITEM - CLEANED: ".print_r($cleaned, TRUE) );
    //loggit(3, "NEW ITEM - TEXT: ".print_r($description, TRUE) );
    //loggit(3, "NEW ITEM - ENCLOSURES: ".print_r($enclosures, TRUE) );

    $sourceurl="";
    $sourcetitle="";
    $author="";

    $title = strip_tags((string)$item->title);

    $sql->bind_param("ssssssssssss", $id,$fid,$title,$linkurl,$description,$item->id,$pubdate,$timeadded,$enclosure,$sourceurl,$sourcetitle,$author) or print(mysql_error());
  } else {
    //-----RSS----------------------------------------------------------------------------------------------------------------------------------------------------
    $linkurl = $item->link;
    $description = $item->description;

    //We also need to find any enclosures
    $mcount = count($item->enclosure);
    $enclosures = array();
    for($i = 0; $i < $mcount; $i++ ) {
        $enclosures[$i] = array( 'url' => (string)$item->enclosure[$i]->attributes()->url,
			         'length' => (string)$item->enclosure[$i]->attributes()->length,
                                 'type' => (string)$item->enclosure[$i]->attributes()->type
        );
        //loggit(3, "Found an RSS enclosure: [".print_r($item->enclosure, TRUE)."].");
    }

    //Does this item have a media namespace?
    if( isset($namespaces['media']) ) {
      //loggit(3, "Media namespace found.");

      $kids = $item->children($namespaces['media'])->thumbnail;
      $kcount = count($kids);
      $ecount = count($enclosures);
      for($i = 0; $i < $kcount; $i++ ) {
        if( isset($kids[$i]->attributes()->url) ) {
          $murl = (string)$kids[$i]->attributes()->url;
          if( !in_array_r($murl, $enclosures) ) {
            $enclosures[$ecount] = array( 'url' => (string)$kids[$i]->attributes()->url, 'length' => '', 'type' => '' );
            //loggit(3, "Thumbnail(s) found: ".print_r($kids[$i]->attributes(), TRUE));
            $ecount++;
          }
        }
      }

      $kids = $item->children($namespaces['media'])->content;
      $kcount = count($kids);
      $ecount = count($enclosures);
      for($i = 0; $i < $kcount; $i++ ) {
        if( isset($kids[$i]->attributes()->url) ) {
          $murl = (string)$kids[$i]->attributes()->url;
          if( !in_array_r($murl, $enclosures) ) {
            $enclosures[$ecount] = array( 'url' => (string)$kids[$i]->attributes()->url, 'length' => '', 'type' => '' );
            //loggit(3, "Content found: ".print_r($kids[$i]->attributes(), TRUE));
            $ecount++;
          }
        }
      }
    }

    //Does this item have a content namespace?
    if( isset($namespaces['content']) ) {
      $content = $item->children($namespaces['content']);
      if( isset($content->encoded) ) {
        $description = (string)trim($content->encoded);
        loggit(1, "Content:encoded found: ".print_r($content, TRUE));
      }
    }

    //Link extraction for those "special" feeds
    if(strpos($linkurl, 'twitter.com') !== FALSE) {
	//Search for an embedded link in the description
	$title = $item->title;
	if(strpos($title, 'http:') !== FALSE) {
		preg_match('/\bhttp\:\/\/([A-Za-z0-9\.\/\+\&\@\~\-\%\?\=\_\#\!]*)/i', $title, $twurl);
		$linkurl = $twurl[0];
		//loggit(3, "I think this: [$linkurl] might be the twitter link.");
		//loggit(3, print_r($twurl, TRUE));
	}
    }

    //Does this item have a source tag?
    $sourceurl="";
    $sourcetitle="";
    if( $item->source && $item->source->attributes() ) {
	loggit(3, "SOURCE: ".print_r($item->source, TRUE));
	//loggit(3, "Item source url: ".$item->source->attributes()->url);
	$sourceurl = (string)$item->source->attributes()->url;
	//loggit(3, "Item source title: ".$item->source);
	$sourcetitle = (string)$item->source;
    }

    //Is there an author?
    $author="";
    if( $item->author ) {
    	$author = (string)$item->author;
        //loggit(3, "AUTHOR FOUND: ".$author);
    }

    //We need a guid, so if the item doesn't have a guid, then build a uniqe id by hashing the whole item
    $uniq = get_unique_id_for_feed_item($item);

    //De-relativize links
    $httploc = strpos($linkurl, 'http');
    if( $httploc === FALSE || $httploc > 1 ) {
        $linkurl = '';
    }

    //Clean the incoming description
    $cleaned = clean_feed_item_content($description, 0, TRUE, TRUE);
    $description = $cleaned['text'];

    //Attach extracted media tags as enclosures with correct type
    foreach( $cleaned['media'] as $mediatag ) {
      $esize = "";
      if( $mediatag['type'] == 'image' || $mediatag['type'] == 'audio' || $mediatag['type'] == 'video' ) {
        $esize = check_head_size($mediatag['src']);
        //loggit(3, "DEBUG ENCLOSURE SIZE: [$esize] for url: [".$mediatag['src']."]");
      }
      if( empty($esize) || $esize > 3000) {
        $enclosures[] = array( 'url' => $mediatag['src'], 'length' => $esize, 'type' => $mediatag['type'] );
      } else {
        //loggit(3, "  DISCARDING TINY ENCLOSURE: [$esize] for url: [".$mediatag['src']."]");
      }
    }

    //Serialize the enclosure array
    $enclosure = serialize($enclosures);

    //debug
    //loggit(3, "NEW ITEM - CLEANED: ".print_r($cleaned, TRUE) );
    //loggit(3, "NEW ITEM - TEXT: ".print_r($description, TRUE) );
    //loggit(3, "NEW ITEM - ENCLOSURES: ".print_r($enclosures, TRUE) );

    $title = strip_tags((string)$item->title);

    $sql->bind_param("ssssssssssss", $id,$fid,$title,$linkurl,$description,$uniq,$pubdate,$timeadded,$enclosure,$sourceurl,$sourcetitle,$author) or print(mysql_error());
  }
  $sql->execute() or loggit(3, $dbh->error);
  $sql->close() or print(mysql_error());

  //Set the items properties per user
  $fusers = get_feed_subscribers($fid);
  if( $fusers != FALSE ) {
    foreach( $fusers as $fuser ) {
      if( feed_is_sticky($fid, $fuser) && $old != TRUE ) {
        set_feed_item_properties($id, $fuser, array( 'sticky' => TRUE ));
      }
      if( feed_is_hidden($fid, $fuser) ) {
        set_feed_item_properties($id, $fuser, array( 'hidden' => TRUE ));
      }
      mark_river_as_updated($fuser);
    }
  }

  //Log and return
  //loggit(1,"Put a new feed item: [$id] in for feed: [$fid].");
  return($id);
}

//_______________________________________________________________________________________
//Check if a feed item is already in the newsfeed repository
function feed_item_exists($fid = NULL, $guid = NULL)
{
  //Check parameters
  if($fid == NULL) {
    loggit(2,"The feed id is blank or corrupt: [$fid]");
    return(FALSE);
  }
  if($guid == NULL) {
    loggit(2,"The feed item guid is blank or corrupt: [$guid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $stmt = "SELECT id FROM $table_nfitem WHERE feedid=? AND guid=?";
  //loggit(3, $stmt);
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $fid, $guid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  $rowcount = $sql->num_rows();
  if($rowcount < 1) {
    $sql->close()
      or print(mysql_error());
    //loggit(3,"The feed item with guid: [$guid] does not exist for feed: [$fid]. Row count: [$rowcount].");
    return(FALSE);
  }
  $sql->bind_result($itemid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(3,"The feed item: [$itemid] with guid: [$guid] already exists for feed: [$fid].");
  return($itemid);
}


//_______________________________________________________________________________________
//Check if a river needs to be rebuilt
function river_updated($uid = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT userid FROM $table_river WHERE userid=? AND updated=1") or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The river for user: [$uid] does not need rebuilding.");
    return(FALSE);
  }
  $sql->bind_result($userid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"The river for user: [$uid] needs rebuilding.");
  return(TRUE);
}


//_______________________________________________________________________________________
//Stick a built river array into the rivers table
function update_river($uid = NULL, $river = NULL, $mriver = NULL, $hash = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if($river == NULL) {
    loggit(2,"The river array is blank or corrupt: [$river]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the first item id from the array
  $firstid = $river['metadata']['firstId'];

  //Get the lastbuild time
  $lastbuild = $river['metadata']['lastBuildDate'];

  //Turn the river into a storable string object
  $striver = serialize($river);

  //If there is a mobile river, serialize it too
  $mtriver = "";
  if( !empty($mriver) ) {
    $mtriver = serialize($mriver);
  }

  //Hash the river for change detection
  //$conthash = md5($striver);

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good uid and river, put the river in the database
  $stmt = "REPLACE INTO $table_river (userid,lastbuild,river,conthash,firstid,updated,mriver) VALUES (?,?,?,?,?,0,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssssss", $uid,$lastbuild,$striver,$hash,$firstid,$mtriver) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  //loggit(3,"Updated river array for user: [$uid]. Checksum: [$conthash].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Build an array of feed items that will be the river for this user
function build_river($uid = NULL, $max = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //The river array
  $river = array();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any items returned
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The user: [$uid] has an empty river.");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$guid,$timestamp,$timeadded,$feedid,$description) or print(mysql_error());

  $count = 0;
  while($sql->fetch()){
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

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] items in user: [$uid]'s river.");
  return($river);
}

//_______________________________________________________________________________________
//Build a json array of feed items that will be the river for this user
function build_river_json($uid = NULL, $max = NULL, $force = FALSE, $mobile = FALSE)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";
  require_once "$confroot/$includes/opml.php";

  //Get the users prefs
  $prefs = get_user_prefs($uid);
  if( !$prefs ) {
    loggit(2,"Couldn't get prefs for user: [$uid]");
    return(FALSE);
  }
  $start = time() - ($prefs['riverhours'] * 3600);
  $dmax = $prefs['maxriversize'];
  $mmax = $prefs['maxriversizemobile'];

  //The river array
  $river = array();
  $driver = array();
  $mriver = array();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get the items
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
             WHERE $table_nfcatalog.userid=?
             AND ( $table_nfitem.timeadded > ? OR $table_nfitemprop.sticky = 1 )
             AND $table_nfitem.`old` = 0";
  $sqltxt .= " ORDER BY $table_nfitemprop.sticky DESC, $table_nfitem.timeadded DESC";
  //loggit(3, $sqltxt);

  //Make sure to set the LIMIT to the higher of the two max values, so we cover both
  if($max == NULL) {
    $max = $dmax;
    if( $mmax > $dmax ) {
      $max = $mmax;
    }
  }
  $sqltxt .= " LIMIT $max";

  //loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("ssd", $uid, $uid, $start) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any items returned
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The user: [$uid] has an empty river.");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$timestamp,$feedid,$timeadded,$enclosure,$description,$sourceurl,$sourcetitle,$author,$sticky,$fsticky,$hidden,$fhidden) or print(mysql_error());

  $fcount = -1;
  $icount = 0;
  $ticount = 0;
  $drcount = 0;
  $mrcount = 0;
  $firstid = "";
  $lastfeedid = "";
  $pubdate = time();
  while($sql->fetch()){
    $feed = get_feed_info($feedid);

    //Save the time stamp of the first item to use as a pubdate
    if( $firstid == "" && $sticky != 1 && $hidden != 1 ) {
      $pubdate = $timeadded;
      $firstid = $id;
    }

    //Keep track of which feed we're in along the way
    if($lastfeedid != $feedid) {
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
		'itemIndex' => $ticount,
		'whenLastUpdate' => date("D, d M Y H:i:s O", $feed['lastupdate'])
      );

      //Check if this feed is linked to an outline this user subscribes to
      $oid = get_feed_outline_by_user($feedid, $uid);
      if($oid != FALSE) {
 	$ou = get_outline_info($oid);
	$river[$fcount]['linkedOutlineId'] = $oid;
	if( !empty($ou['type']) ) { $river[$fcount]['linkedOutlineType'] = $ou['type']; }
	if( !empty($ou['title']) ) {  $river[$fcount]['linkedOutlineTitle'] = $ou['title'];  }
	if( !empty($ou['ownername']) ) {  $river[$fcount]['ownerName'] = $ou['ownername'];  }
	if( !empty($ou['avatarurl']) ) {  $river[$fcount]['avatarUrl'] = $ou['avatarurl'];  }
      }

      //Does this feed have an avatar url?
      if( !empty($feed['avatarurl']) ) {  $river[$fcount]['avatarUrl'] = $feed['avatarurl']; }

      //Start a sub-array in this feed array to hold items
      $river[$fcount]['item'] = array();

    }

    if($prefs['fulltextriver'] == 0) {
      if( strlen($description) > 300 ) {
        $itembody = truncate_text($description, 300)."...";
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
		'pubDate' => date("D, d M Y H:i:s O", $timeadded),
	        'title' => $title,
	        'link' => $url,
	        'id' => $id
    );

    //Is there an author attribution?
    if(!empty($author)) {
	$river[$fcount]['item'][$icount]['author'] = $author;
    }

    //Does this item specify a source attribution?
    if(!empty($sourceurl)) {
	$river[$fcount]['item'][$icount]['sourceurl'] = $sourceurl;
    }
    if(!empty($sourcetitle)) {
	$river[$fcount]['item'][$icount]['sourcetitle'] = $sourcetitle;
    }

    //Set the sticky bit
    if($sticky == 1) {
        $river[$fcount]['item'][$icount]['sticky'] = 1;
    }

    //Set the hidden bit
    if($hidden == 1) {
        $river[$fcount]['item'][$icount]['hidden'] = 1;
    }

    //Are there any enclosures?
    $enclosures = unserialize($enclosure);
    if($enclosures != FALSE) {
	    if( !empty($enclosures) ) {
		if(!empty($enclosures[0]['url'])){ // && !empty($enclosures[0]['type']) && !empty($enclosures[0]['length'])) {
		      $river[$fcount]['item'][$icount]['enclosure'] = $enclosures;
		}
	    }
    }

    //We're building two rivers here.  One for desktop and one for mobile
    if( $ticount <= $dmax ) {  $driver = $river; $drcount++;  }
    if( $ticount <= $mmax ) {  $mriver = $river; $mrcount++;  }

    $icount++;
    $ticount++;
  }

  $sql->close() or print(mysql_error());

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
  $djsonriver = "onGetRiverStream(".json_encode($doutput).")";
  $mjsonriver = "onGetRiverStream(".json_encode($moutput).")";

  //Let's return the river asked for
  $jsonriver = $djsonriver;
  if( $mobile == TRUE ) {
    $jsonriver = $mjsonriver;
  }

  //Let's be smart about this and not re-publish a river that hasn't changed
  $pubriver = get_river_info($uid);
  $newhash = md5(serialize($driver));

  //loggit(3, "River hash: OLD: [".$pubriver['conthash']."]");
  //loggit(3, "River hash: NEW: [$newhash]");

  if( $pubriver != FALSE && ($pubriver['firstid'] == $firstid && $force == FALSE) && ($pubriver['conthash'] == $newhash) ) {
    //loggit(3, "User: [$uid]'s river has not changed. No need to publish.");
    return($jsonriver);
  }

  //Put this built river in the database
  update_river($uid, $doutput, $moutput, $newhash);

  //If we can get some sane S3 credentials then let's go
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
      //First we get all the key info
      $s3info = get_s3_info($uid);

      //Subpath?  Must begin with a slash
      $subpath = "";

      //Put the desktop file
      $filename = $default_river_json_file_name;
      $s3res = putInS3($djsonriver, $filename, $s3info['bucket'].$subpath, $s3info['key'], $s3info['secret'], "application/json");
      if(!$s3res) {
	loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
      } else {
        $s3url = get_s3_url($uid, $subpath, $filename);
        loggit(3, "Wrote desktop river to S3 at url: [$s3url].");
      }
      //Put the mobile file
      $filename = $default_river_json_mobile_file_name;
      $s3res = putInS3($mjsonriver, $filename, $s3info['bucket'].$subpath, $s3info['key'], $s3info['secret'], "application/json");
      if(!$s3res) {
	loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
      } else {
        $s3url = get_s3_url($uid, $subpath, $filename);
        loggit(1, "Wrote mobile river to S3 at url: [$s3url].");
      }
  }

  loggit(1,"Returning: [$drcount] items in user: [$uid]'s desktop river.");
  loggit(1,"Returning: [$mrcount] items in user: [$uid]'s mobile river.");
  return($jsonriver);
}


//_______________________________________________________________________________________
//Build an opml file listing all the feeds this user follows
function build_opml_reading_list($uid = NULL, $max = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";

  $username = get_user_name_from_uid($uid);
  $prefs = get_user_prefs($uid);
  $feeds = get_feeds($uid, $max);

  //The feed string
  $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
  $opml .= "<!-- OPML generated by ".$system_name." v".$version." on ".date("D, d M Y H:i:s O")." -->\n";
  $opml .= '<opml version="2.0">'."\n";

  $opml .= "
      <head>
        <title>The feeds $username follows.</title>
        <dateCreated>".date("D, d M Y H:i:s O", $feeds[0]['createdon'])."</dateCreated>
        <dateModified>".date("D, d M Y H:i:s O", $feeds[0]['createdon'])."</dateModified>
        <ownerName>".get_user_name_from_uid($uid)."</ownerName>
        <ownerId>".$uid."</ownerId>
      </head>\n";

  $opml .= "
      <body>";

  foreach( $feeds as $feed ) {
      $opml .= "
              <outline text=\"".trim(str_replace("\n", '', htmlentities($feed['title'])))."\" description=\"\" htmlUrl=\"".htmlspecialchars($feed['link'])."\" xmlUrl=\"".htmlspecialchars($feed['url'])."\" />";
  }

  $opml .= "
      </body>";

  $opml .= "</opml>";


  //If we can get some sane S3 credentials then let's go
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
      //First we get all the key info
      $s3info = get_s3_info($uid);

      //What's the filename going to be?
      $filename = $default_readinglist_file_name;

      //Subpath?  Must begin with a slash
      $subpath = "";

      //Put the file
      $s3res = putInS3($opml, $filename, $s3info['bucket'].$subpath, $s3info['key'], $s3info['secret'], "text/plain");
      if(!$s3res) {
	loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
      } else {
        $s3url = get_s3_url($uid, $subpath, $filename);
        //loggit(3, "Wrote feed to S3 at url: [$s3url].");
      }
  }


  loggit(1, "Built opml reading list for user: [$username | $uid].");
  return($opml);
}


//_______________________________________________________________________________________
//Hash a feed item to construct a unique id for this item
function get_unique_id_for_feed_item($item = NULL)
{
  //Check parameters
  if( empty($item) ) {
    loggit(2,"The feed item is blank or corrupt: [".print_r($item, TRUE)."]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //If a guid exists then get that instead
  if( !empty($item->guid) ) {
    //loggit(3, "Returning guid: [".$item->guid."] as unique id for feed item."]");
    return($item->guid);
  }

  //Hash it
  $hashed = sha1( $item->asXML() );

  //Return
  //loggit(3, "Returning hash: [$hashed] as unique id for feed item: [".print_r($item, TRUE)."]");
  return($hashed);
}


//_______________________________________________________________________________________
//Search for river items that match the query for this user
function search_feed_items($uid = NULL, $query = NULL, $max = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }
  if($query == NULL) {
    loggit(2,"The query given is corrupt or blank: [$query]");
    return(FALSE);
  }


  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

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
             WHERE $table_nfcatalog.userid=?
	     AND ($table_nfitem.description LIKE CONCAT('%', ?, '%')
	     OR $table_nfitem.title LIKE CONCAT('%', ?, '%')
	     OR $table_nfitem.sourcetitle LIKE CONCAT('%', ?, '%')
	     OR $table_nfitem.url LIKE CONCAT('%', ?, '%')
	     OR $table_nfitem.author LIKE CONCAT('%', ?, '%'))";
  $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";
  //loggit(3, "DEBUG: [$sqltxt]");
  //loggit(3, "DEBUG: [$query]");

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  } else {
    $sqltxt .= " LIMIT 30";
  }

  //loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("sssssss", $uid, $uid, $query, $query, $query, $query, $query) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any articles for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"No feed items returned for user: [$uid] with given criteria.");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$timestamp,$feedid,$timeadded,$enclosure,$description,$sourceurl,$sourcetitle,$author,$sticky,$fsticky,$hidden,$fhidden) or print(mysql_error());

  $nfitems = array();
  $count = 0;
  while($sql->fetch()){
    if( empty($title) ) {
	$title = $description;
    }
    $nfitems[$count] = array( 'id' => $id, 'title' => $title, 'url' => $url );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] newsfeed items for user: [$uid]");
  return($nfitems);
}


//_______________________________________________________________________________________
//Build a json array of feed items for the entire server
function build_server_river_json($max = NULL, $force = FALSE, $mobile = FALSE)
{

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
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
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get the items
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
		    $table_nfitem.author
             FROM $table_nfitem
             WHERE $table_nfitem.timeadded > ?
             AND $table_nfitem.`old` = 0";
  $sqltxt .= " ORDER BY $table_nfitem.timeadded DESC";
  //loggit(3, $sqltxt);

  //Make sure to set the LIMIT to the higher of the two max values, so we cover both
  if($max == NULL) {
    $max = $dmax;
    if( $mmax > $dmax ) {
      $max = $mmax;
    }
  }
  $sqltxt .= " LIMIT $max";

  //loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("d", $start) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any items returned
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The server has an empty river.");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$timestamp,$feedid,$timeadded,$enclosure,$description,$sourceurl,$sourcetitle,$author) or print(mysql_error());

  $fcount = -1;
  $icount = 0;
  $ticount = 0;
  $drcount = 0;
  $mrcount = 0;
  $firstid = "";
  $lastfeedid = "";
  $pubdate = time();
  while($sql->fetch()){
    $feed = get_feed_info($feedid);

    //Save the time stamp of the first item to use as a pubdate
    if( $firstid == "" ) {
      $pubdate = $timeadded;
      $firstid = $id;
    }

    //Keep track of which feed we're in along the way
    if($lastfeedid != $feedid) {
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
      if( !empty($feed['avatarurl']) ) {  $river[$fcount]['avatarUrl'] = $feed['avatarurl']; }

      //Start a sub-array in this feed array to hold items
      $river[$fcount]['item'] = array();

    }

    //Body text of item
    if( strlen($description) > 512 ) {
      $itembody = truncate_text($description, 512)."...";
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
    if(!empty($author)) {
	$river[$fcount]['item'][$icount]['author'] = $author;
    }

    //Does this item specify a source attribution?
    if(!empty($sourceurl)) {
	$river[$fcount]['item'][$icount]['sourceurl'] = $sourceurl;
    }
    if(!empty($sourcetitle)) {
	$river[$fcount]['item'][$icount]['sourcetitle'] = $sourcetitle;
    }

    //Are there any enclosures?
    $enclosures = unserialize($enclosure);
    if($enclosures != FALSE) {
	    if( !empty($enclosures) ) {
		if(!empty($enclosures[0]['url'])){
		      $river[$fcount]['item'][$icount]['enclosure'] = $enclosures;
		}
	    }
    }

    //We're building two rivers here.  One for desktop and one for mobile
    if( $ticount <= $dmax ) {  $driver = $river; $drcount++;  }
    if( $ticount <= $mmax ) {  $mriver = $river; $mrcount++;  }

    $icount++;
    $ticount++;
  }

  $sql->close() or print(mysql_error());

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
  $djsonriver = "onGetRiverStream(".json_encode($doutput).")";
  $mjsonriver = "onGetRiverStream(".json_encode($moutput).")";

  //Let's return the river asked for
  $jsonriver = $djsonriver;
  if( $mobile == TRUE ) {
    $jsonriver = $mjsonriver;
  }

  //If we can get some sane S3 credentials then let's go
  if( sys_s3_is_enabled() ) {
      //First we get all the key info
      $s3info = get_sys_s3_info();

      //Subpath?  Must begin with a slash
      $subpath = "";

      //Put the json river file
      $filename = $default_river_json_file_name;
      $s3res = putInS3($djsonriver, $filename, $s3info['riverbucket'].$subpath, $s3info['key'], $s3info['secret'], "application/json");
      if(!$s3res) {
	loggit(2, "Could not create S3 file: [$filename].");
        //loggit(3, "Could not create S3 file: [$filename].");
      } else {
        $s3url = get_server_river_s3_url($subpath, $filename);
        loggit(3, "Wrote server river json to S3 at url: [$s3url].");
      }

      //We always put the json file if the bucket is enabled, but only put the html stuff if
      //the riverfile value is non-blank
      if( !empty($s3info['riverfile']) ) {
        //Construct the server river html file
        $fh = fopen("$confroot/$templates/$cg_template_html_river", "r");
        $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_template_html_river"));
        //Replace the tags
        $rftemplate = str_replace('[RIVER_TITLE]', $s3info['rivertitle'], $rftemplate);
        $rftemplate = str_replace('[RIVER_JSON_URL]', $s3url, $rftemplate);
        $rftemplate = str_replace('[SCRIPT_JQUERY]', $cg_script_js_jquery, $rftemplate);
        $rftemplate = str_replace('[SCRIPT_JQTEMPLATES]', $cg_script_js_jqtemplates, $rftemplate);
        $rftemplate = str_replace('[DATE]', date("D, d M Y H:i:s O"), $rftemplate);
        $rftemplate = str_replace('[SYS_NAME]', $system_name, $rftemplate);
        $rftemplate = str_replace('[SYS_VERSION]', $version, $rftemplate);
        //Close the template
        fclose($fh);

        //Put the html template
        $filename = $s3info['riverfile'];
        $s3res = putInS3($rftemplate, $filename, $s3info['riverbucket'].$subpath, $s3info['key'], $s3info['secret'], "text/html");
        if(!$s3res) {
          loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
          //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
        } else {
          $s3url = get_server_river_s3_url($subpath, $filename);
          loggit(1, "Wrote server river html to S3 at url: [$s3url].");
        }

      	//Put the support files
      	$filename = $cg_template_css_river;
      	$s3res = putFileInS3("$confroot/$templates/$cg_template_css_river", $filename, $s3info['riverbucket'].$subpath, $s3info['key'], $s3info['secret'], "text/css");
      	if(!$s3res) {
		loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        	//loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
      	} else {
        	$s3url = get_server_river_s3_url($subpath, $filename);
        	loggit(1, "Wrote server river html to S3 at url: [$s3url].");
      	}
      	$filename = $cg_script_js_jquery;
      	$s3res = putFileInS3("$confroot/$scripts/$cg_script_js_jquery", $filename, $s3info['riverbucket'].$subpath, $s3info['key'], $s3info['secret'], "text/javascript");
      	if(!$s3res) {
		loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        	//loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
      	} else {
        	$s3url = get_server_river_s3_url($subpath, $filename);
        	loggit(1, "Wrote jquery script to S3 at url: [$s3url].");
      	}
      	$filename = $cg_script_js_jqtemplates;
      	$s3res = putFileInS3("$confroot/$scripts/$cg_script_js_jqtemplates", $filename, $s3info['riverbucket'].$subpath, $s3info['key'], $s3info['secret'], "text/javascript");
      	if(!$s3res) {
		loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
        	//loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
      	} else {
        	$s3url = get_server_river_s3_url($subpath, $filename);
        	loggit(1, "Wrote jquery templates script to S3 at url: [$s3url].");
      	}
      }
  }

  loggit(1,"Returning: [$drcount] items in server river.");
  loggit(1,"Returning: [$mrcount] items in server river.");
  return($jsonriver);
}


//_______________________________________________________________________________________
//Retrieve all the items from a particular feed
function get_items_by_feed_id($fid = NULL, $max = NULL)
{
  //Check parameters
  if( empty($fid) ) {
    loggit(2,"The feed id given is corrupt or blank: [$fid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Run the query
  $sqltxt = "SELECT url,
                    description,
                    timestamp,
                    enclosure,
                    title,
		    sourceurl,
		    sourcetitle
	     FROM $table_nfitem
	     WHERE feedid=?
             ORDER BY timestamp ASC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  } else {
    $sqltxt .= " LIMIT $default_max_list";
  }

  loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("s", $fid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any items returned
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"No feed items returned for: [$fid].");
    return(array());
  }

  $sql->bind_result($aurl,
                    $adescription,
                    $atimestamp,
                    $aenclosure,
                    $atitle,
                    $asourceurl,
                    $asourcetitle
  ) or print(mysql_error());

  $items = array();
  $count = 0;
  while($sql->fetch()){
    $items[$count] = array( 'url' => $aurl,
                            'title' => $atitle,
                            'description' => $adescription,
                            'timestamp' => $atimestamp,
			    'enclosure' => $aenclosure,
			    'sourceurl' => $asourceurl,
			    'sourcetitle' => $asourcetitle
    );
    $count++;
  }

  $sql->close() or print(mysql_error());

  //loggit(3, print_r($items, TRUE));

  loggit(1,"Returning: [$count] items for feed: [$fid]");
  return($items);
}


//_______________________________________________________________________________________
// ***** under construction ******
//Build a server-wide rss feed
function build_server_rss_feed($max = NULL)
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";

  //Get a proper max value
  if($max == NULL) {
      $max = $default_max_rss_items;
  }

  //Get the right timezone
  date_default_timezone_set('America/Chicago');

  //If the array of posts is being passed in as an argument we don't make this call
  if( $posts == NULL || !is_array($posts) ) {
    $posts = get_blog_posts($uid, $max, NULL, $archive);
  }

  //Get a correct title
  $title = get_microblog_title($uid);

  //Get the correct link
  if( $prefs['mblinkhome'] == 0 || empty($prefs['homepagelink']) ) {
    $feedlink = get_s3_url($uid, NULL, get_microblog_html_filename($uid));
  } else {
    $feedlink = $prefs['homepagelink'];
  }

  //Get the date of the user's earliest blog post
  $firstpost = get_first_blog_post($uid);
  if( empty($firstpost) ) {
    $firstpostDate = date('Y-m-d');
  } else {
    $firstpostDate = date('Y-m-d', $firstpost['createdon']);
  }

  //Get the date of the user's latest blog post
  if( empty($posts) ) {
    $lastpostDate = date('Y-m-d');
  } else {
    $lastpostDate = date('Y-m-d', $posts[0]['createdon']);
  }



  //The feed string
  $rss = '<?xml version="1.0"?>'."\n  <rss version=\"2.0\" xmlns:microblog=\"http://microblog.reallysimple.org/\" xmlns:sopml=\"$sopmlnamespaceurlv1\">\n    <channel>";

  $rss .= "\n
      <title>".htmlspecialchars($title)."</title>
      <link>".htmlspecialchars($feedlink)."</link>
      <description>$title</description>
      <language>en-us</language>
      <pubDate>".date("D, d M Y H:i:s O")."</pubDate>
      <lastBuildDate>".date("D, d M Y H:i:s O")."</lastBuildDate>
      <generator>$system_name, v$version</generator>
      <managingEditor>".get_email_from_uid($uid)." ($username)</managingEditor>";
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
      $rss .= "
      <microblog:archive>
          <microblog:url>".htmlspecialchars(get_s3_url($uid, "arc"))."/</microblog:url>
          <microblog:filename>".get_microblog_feed_filename($uid)."</microblog:filename>
          <microblog:startDay>$firstpostDate</microblog:startDay>
          <microblog:endDay>$lastpostDate</microblog:endDay>
      </microblog:archive>";
  }
  $rss .= "
      <microblog:localTime>".date('n/j/Y; g:i:s A')."</microblog:localTime>
      <cloud domain=\"".$rss_cloud_domain."\" port=\"".$rss_cloud_port."\" path=\"".$rss_cloud_notify_path."\" registerProcedure=\"\" protocol=\"".$rss_cloud_protocol."\" />\n";

  if( !empty($prefs['avatarurl']) ) {
    $rss .= "      <microblog:avatar>".$prefs['avatarurl']."</microblog:avatar>\n";
  }

  foreach( $posts as $post ) {
	if ($post['url'] == "") {
	  $rsslink = "";
          $linkfull = "";
          $guid = "        <guid isPermaLink=\"false\">".$post['id']."</guid>";
        } else {
	  if(!empty($post['shorturl'])) {
		$rssurl = htmlspecialchars($post['shorturl']);
                $rsslink = "        <link>$rssurl</link>";
                $guid = "        <guid>$rssurl</guid>";
                $linkfull = "        <microblog:linkFull>".htmlspecialchars($post['url'])."</microblog:linkFull>";
          } else {
                $rssurl = htmlspecialchars($post['url']);
                $rsslink = "        <link>$rssurl</link>";
                $guid = "        <guid>$rssurl</guid>";
          }
	}
        if( !empty($post['enclosure']) ) {
          $enclosures = $post['enclosure'];
        } else {
          $enclosures = array();
	}
        $tweeted = '';
        if( $post['tweeted'] == 1 ) {
                $tweeted = "        <sopml:tweeted>true</sopml:tweeted>\n";
        }

       $rss .= "
      <item>\n";
        if( !empty($post['title']) ) {
          $rss .= "        <title>".htmlspecialchars(trim($post['title']))."</title>\n";
        }
        $rss .= "        <description><![CDATA[".trim($post['content'])."]]></description>
        <pubDate>".date("D, d M Y H:i:s O", $post['createdon'])."</pubDate>\n";
        $rss .= $guid."\n";
        if(!empty($rsslink)) { $rss .= $rsslink."\n"; }
        if(!empty($linkfull)) { $rss .= $linkfull."\n"; }
        if( isset($enclosures) ) {
          if( is_array($enclosures) && count($enclosures) > 0 ) {
            foreach($enclosures as $enclosure) {
              $elen = 'length="0"';
              $etyp = 'type="application/octet-stream"';
	      if( !empty($enclosure['length']) ) {
                $elen = 'length="'.$enclosure['length'].'"';
              }
	      if( !empty($enclosure['type']) ) {
                $etyp = 'type="'.$enclosure['type'].'"';
              }
              if( !empty($enclosure['url']) ) {
                $rss .= '        <enclosure url="'.htmlspecialchars(trim($enclosure['url'])).'" '.$elen.' '.$etyp.' />'."\n";
              }
            }
          }
        }
	if( !empty($post['sourceurl']) || !empty($post['sourcetitle']) ) {
          $rss .= '        <source url="'.htmlspecialchars(trim($post['sourceurl'])).'">'.htmlspecialchars(trim($post['sourcetitle'])).'</source>'."\n";
	}
      $rss .= "        <author>".get_email_from_uid($uid)."</author>\n";
      $rss .= $tweeted;
      $rss .= "      </item>\n";
  }

  $rss .= "\n    </channel>\n  </rss>";

  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the microblog feed file name
    $filename = get_microblog_feed_filename($uid);
    $arcpath = '';

    //Was this a request for a monthly archive?
    if( $archive != FALSE ) {
      $arcpath = "/arc/".date('Y')."/".date('m')."/".date('d');
      //loggit(3, "Archive path: [".$arcpath."]");
    }

    //Put the file
    $s3res = putInS3($rss, $filename, $s3info['bucket'].$arcpath, $s3info['key'], $s3info['secret'], "application/rss+xml");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $filename);
      loggit(1, "Wrote feed to S3 at url: [$s3url].");

      //Ping the rss cloud if this is not an archive AND rsscloud is enabled
      if($archive == FALSE && $enable_rsscloud == 1) {
        $resp = httpRequest($rss_cloud_domain, $rss_cloud_port, $rss_cloud_method, $rss_cloud_ping_path, array("url" => $s3url), $rss_cloud_timeout);
        loggit(1, "Pinged the rss cloud for feed: [$s3url].");
      }
    }
  }


  loggit(1, "Built blog rss feed for user: [$username | $uid].");
  return($rss);
}


//########################################################################################
?>
