<?
//########################################################################################
// API for managing opml content


//Test if the given content is an opml outline
function is_outline($content = NULL)
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

  //Look for opml nodes
  $items = array();
  $count = 0;
  foreach($x->body->outline as $entry) {
    loggit(1, "Found an outline node. This content IS an opml outline.");
    return(TRUE);
  }

  //None of the tests passed so return FALSE
  loggit(1,"The content tested was not an opml outline.");
  return(FALSE);
}


//Test if the given content is an opml reading list
function is_reading_list($content = NULL)
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

  //Look for opml nodes with an xmlUrl attribute
  $nodes = $x->xpath('*/outline[@xmlUrl]');
  if (!empty($nodes)) {
    loggit(1, "This content looks like a reading list.");
    return(TRUE);
  }

  //None of the tests passed so return FALSE
  loggit(1,"The content tested was not a reading list.");
  return(FALSE);
}


//Test if the given content is a social outline graph
function is_social_outline($content = NULL)
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

  //Look for sopml specific elements
  $namespaces = $x->getDocNamespaces();
  loggit(1, print_r($namespaces, true));
  if (array_key_exists('sopml', $namespaces)) {
    loggit(1, "This content looks like a social outline.");
    return(TRUE);
  }

  //None of the tests passed so return FALSE
  loggit(1,"The content tested was not a social outline.");
  return(FALSE);
}


//_______________________________________________________________________________________
//Check if an outline is already in the repository
function outline_exists($url = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The outline url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT id FROM $table_sopml_outlines WHERE url=?") or print(mysql_error());
  $sql->bind_param("s", $url) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The outline at url: [$url] does not exist in the repository.");
    return(FALSE);
  }
  $sql->bind_result($oid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"The outline: [$oid] at url: [$url] does exist in the repository.");
  return($oid);
}


//_______________________________________________________________________________________
//Check if an outline is already in the repository
function outline_exists_by_id($id = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT id FROM $table_sopml_outlines WHERE id=?") or print(mysql_error());
  $sql->bind_param("s", $id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The outline: [$id] does not exist in the repository.");
    return(FALSE);
  }
  $sql->bind_result($oid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"The outline: [$oid] does exist in the repository.");
  return(TRUE);
}


//_______________________________________________________________________________________
//See how many subscribers an outline has
function get_outline_subscriber_count($oid = NULL)
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

  //Look for the id in the catalog
  $sql=$dbh->prepare("SELECT oid FROM $table_sopml_catalog WHERE oid=?") or print(mysql_error());
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $subcount = $sql->num_rows();
  $sql->close() or print(mysql_error());

  loggit(1,"The outline: [$oid] has: [$subcount] subscribers.");
  return($subcount);
}


//_______________________________________________________________________________________
//Return an array of user ids that subscribe to this outline
function get_outline_subscribers($oid = NULL)
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

  //Look for the id in the catalog
  $sql=$dbh->prepare("SELECT uid FROM $table_sopml_catalog WHERE oid=?") or print(mysql_error());
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there are any subscribers to this feed
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Outline: [$oid] has no subscribers.");
    return(FALSE);
  }

  $sql->bind_result($uid) or print(mysql_error());

  $users = array();
  $count = 0;
  while($sql->fetch()) {
    $users[$count] = $uid;
    $count++;
  }
  $subcount = count($users);

  $sql->close() or print(mysql_error());

  loggit(1,"Returning an array of: [$subcount] user id's for outline: [$oid].");
  return($users);
}


//_______________________________________________________________________________________
//Return an array of outline id's that link to a given feed
/*
function get_linked_outlines_for_feed_by_user($fid = NULL, $uid = NULL)
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

  //Do the query
  $sql=$dbh->prepare("SELECT outlineid FROM $table_nfcatalog WHERE userid=? AND feedid=?") or print(mysql_error());
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there are any subscribers to this feed
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Outline: [$oid] has no subscribers.");
    return(FALSE);
  }

  $sql->bind_result($uid) or print(mysql_error());

  $users = array();
  $count = 0;
  while($sql->fetch()) {
    $users[$count] = $uid;
    $count++;
  }
  $subcount = count($users);

  $sql->close() or print(mysql_error());

  loggit(1,"Returning an array of: [$subcount] user id's for outline: [$oid].");
  return($users);
}
*/


//_______________________________________________________________________________________
//See how many items are in the database for a given feed
function get_outline_item_count($oid = NULL)
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

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT id FROM $table_sopml_items WHERE oid=?") or print(mysql_error());
  $sql->bind_param("s", $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if any rows came back
  $itemcount = $sql->num_rows();
  $sql->close() or print(mysql_error());

  loggit(1,"The outline: [$oid] has: [$itemcount] items.");
  return($itemcount);
}


//_______________________________________________________________________________________
//Retrieve an array of info about an outline
function get_outline_info($id = NULL)
{
  //Check parameters
  if(empty($id)) {
    loggit(2,"The outline id given is corrupt or blank: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sql=$dbh->prepare("SELECT title,
                             url,
                             type,
                             content,
                             lastcheck,
                             lastupdate,
                             lastmod,
                             avatarurl,
                             ownername,
                             ownerid,
                             control,
                             filename
                      FROM $table_sopml_outlines
                      WHERE id=?")
  or print(mysql_error());
  $sql->bind_param("s", $id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve info for outline id: [$id]");
    return(FALSE);
  }
  $outline = array();
  $sql->bind_result($outline['title'],
                    $outline['url'],
                    $outline['type'],
                    $outline['content'],
                    $outline['lastcheck'],
                    $outline['lastupdate'],
                    $outline['lastmod'],
                    $outline['avatarurl'],
		    $outline['ownername'],
		    $outline['ownerid'],
		    $outline['control'],
		    $outline['filename']
  ) or print(mysql_error());

  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"Returning info for outline: [$id]");
  return($outline);
}


//_______________________________________________________________________________________
//Retrieve the content blob for this outline
function get_outline_content($id = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id given is corrupt or blank: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sql=$dbh->prepare("SELECT content FROM $table_sopml_outlines WHERE id=?") or print(mysql_error());
  $sql->bind_param("s", $id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve content for outline: [$id]");
    return(FALSE);
  }
  $sql->bind_result($content) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //loggit(1,"Returning info for outline: [$id]");
  return($content);
}


//_______________________________________________________________________________________
//Add an outline to the database
function add_outline($url = NULL, $uid = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The outline url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Timestamp
  $id = random_gen(128);

  //Check if outline already exists
  $oid = outline_exists($url);
  if($oid == FALSE) {
    //Now that we have a good id, put the outline into the database
    $stmt = "INSERT INTO $table_sopml_outlines (id,url) VALUES (?,?)";
    $sql=$dbh->prepare($stmt) or print(mysql_error());
    $sql->bind_param("ss", $id,$url) or print(mysql_error());
    $sql->execute() or print(mysql_error());
    $sql->close() or print(mysql_error());
  } else {
    $id = $oid;
  }

  //Now link the outline's id to this user's id
  if($uid != NULL) {
    link_outline_to_user($id, $uid);
  }

  //Log and return
  loggit(1,"Put a new outline in the repository: [$id] with url [$url].");
  return($id);
}


//_______________________________________________________________________________________
//Add a social outline to the database
function add_social_outline($url = NULL, $uid = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The outline url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Timestamp
  $id = random_gen(128);
  $type = "sopml";

  //Check if outline already exists
  $oid = outline_exists($url);
  if($oid == FALSE) {
    //Now that we have a good id, put the outline into the database
    $stmt = "INSERT INTO $table_sopml_outlines (id,url,type) VALUES (?,?,?)";
    $sql=$dbh->prepare($stmt) or print(mysql_error());
    $sql->bind_param("sss", $id,$url,$type) or print(mysql_error());
    $sql->execute() or print(mysql_error());
    $sql->close() or print(mysql_error());
  } else {
    $id = $oid;
  }

  //Now link the outline's id to this user's id
  if($uid != NULL) {
    link_outline_to_user($id, $uid);
  }

  //Log and return
  loggit(1,"Put a new social outline in the repository: [$id] with url [$url].");
  return($id);
}


//_______________________________________________________________________________________
//Change the content of an outline
function update_outline_content($id = NULL, $content = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Vars
  $tstamp = time();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET content=?,lastupdate=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("sss", $content,$tstamp,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated content for outline: [$id].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the last modified time of an outline
function update_outline_lastmod($id = NULL, $lastmod = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($lastmod == NULL) {
    loggit(2,"The outline last-modified time is blank or corrupt: [$lastmod]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET lastmod=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $lastmod,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated last-modified time to: [$lastmod] for outline: [$id].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the title of an outline
function update_outline_title($id = NULL, $title = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($title == NULL) {
    loggit(2,"The outline title is blank or corrupt: [$title]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Clean the title
  $title = trim($title);

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET title=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $title,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated title to: [$title] for outline: [$id].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the owner name of an outline
function update_outline_ownername($id = NULL, $name = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($name == NULL) {
    loggit(2,"The owner name is blank or corrupt: [$name]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET ownername=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $name,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated owner name to: [$name] for outline: [$id].");
  return(TRUE);
}

//_______________________________________________________________________________________
//Change the avatar url of a social outline
function update_outline_avatar($id = NULL, $url = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($url == NULL) {
    loggit(2,"The avatar url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET avatarurl=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $url,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated avatar url to: [$url] for outline: [$id].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Change the link url of a social outline
function update_outline_link($id = NULL, $url = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($url == NULL) {
    loggit(2,"The outline link url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET url=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $url,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated link url to: [$url] for outline: [$id].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Change the last check time of an outline
function update_outline_lastcheck($id = NULL, $lastcheck = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }
  if($lastcheck == NULL) {
    loggit(2,"The outline lastcheck time is blank or corrupt: [$lastcheck]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_sopml_outlines SET lastcheck=? WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $lastcheck,$id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Updated lastcheck time to: [$lastcheck] for outline: [$id].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Check if this outline is linked to a user
function outline_is_linked($oid = NULL, $uid = NULL)
{
  //Check parameters
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

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the url in the feed table
  $sql=$dbh->prepare("SELECT * FROM $table_sopml_catalog WHERE oid=? AND uid=?") or print(mysql_error());
  $sql->bind_param("ss", $oid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The outline: [$oid] is NOT linked to user: [$uid].");
    return(FALSE);
  }
  $sql->close() or print(mysql_error());

  loggit(1,"The outline: [$oid] IS linked to user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Link an outline to a user
function link_outline_to_user($oid = NULL, $uid = NULL)
{
  //Check parameters
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

  //Create a random color
  $color = random_gen(6, 'abcdef');

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Link the article to the user
  $stmt = "INSERT INTO $table_sopml_catalog (uid,oid,linkedon,color) VALUES (?,?,?,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssss", $uid,$oid,$tstamp,$color) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Linked outline: [$oid] with user: [$uid] and color: [$color].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Retrieve a list of outlines subscribed to by the given user
function get_outlines($uid = NULL, $max = NULL, $otype = NULL, $ididx = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$includes/feeds.php";

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Grab all the outlines that link to this user
  $sqltxt = "SELECT $table_sopml_outlines.id,
                    $table_sopml_outlines.title,
                    $table_sopml_outlines.url,
                    $table_sopml_outlines.type,
                    $table_sopml_catalog.linkedon,
                    $table_sopml_outlines.avatarurl,
                    $table_sopml_outlines.ownername,
                    $table_sopml_outlines.ownerid,
                    $table_sopml_outlines.control,
		    $table_sopml_catalog.color
	     FROM $table_sopml_outlines,$table_sopml_catalog
	     WHERE $table_sopml_catalog.uid=?
             AND ($table_sopml_catalog.oid=$table_sopml_outlines.id)";

  if($otype != NULL) {
    $sqltxt .= " AND $table_sopml_outlines.type = ?";
  }

  $sqltxt .= " ORDER BY $table_sopml_outlines.title ASC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  if($otype != NULL) {
    $sql->bind_param("ss", $uid, $otype) or print(mysql_error());
  } else {
    $sql->bind_param("s", $uid) or print(mysql_error());
  }
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"This user has not subscribed to any outlines: [$uid]");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$type,$linkedon,$avatarurl,$ownername,$ownerid,$control,$color) or print(mysql_error());

  $outlines = array();
  $count = 0;
  while($sql->fetch()){
    if($ididx == TRUE) {
      $idx = $id;
    } else {
      $idx = $count;
    }
    $outlines[$idx] = array(  'id' => $id,
                                'title' => $title,
				'url' => $url,
				'type' => $type,
				'linkedon' => $linkedon,
				'avatarurl' => $avatarurl,
				'ownername' => $ownername,
				'ownerid' => $ownerid,
				'control' => $control,
                                'color' => $color
    );

    $outlines[$idx]['feeds'] = get_feeds_by_outline_id($id);

    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] outlines for user: [$uid]");
  return($outlines);
}


//_______________________________________________________________________________________
//Retrieve a list of social outlines subscribed to by the given user
function get_social_outlines($uid = NULL, $max = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Globals
  $type = 'sopml';

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Grab all the outlines that link to this user
  $sqltxt = "SELECT $table_sopml_outlines.id,
                    $table_sopml_outlines.title,
                    $table_sopml_outlines.url,
                    $table_sopml_outlines.type,
                    $table_sopml_catalog.linkedon,
                    $table_sopml_outlines.avatarurl,
                    $table_sopml_outlines.ownername,
                    $table_sopml_outlines.ownerid,
                    $table_sopml_outlines.control
	     FROM $table_sopml_outlines,$table_sopml_catalog
	     WHERE $table_sopml_catalog.uid=?
             AND ($table_sopml_catalog.oid=$table_sopml_outlines.id)
             AND $table_sopml_outlines.type = ?";

  $sqltxt .= " ORDER BY $table_sopml_catalog.linkedon DESC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("ss", $uid, $type) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any feeds for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"This user has not subscribed to any outlines: [$uid]");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$type,$linkedon,$avatarurl,$ownername,$ownerid,$control) or print(mysql_error());

  $outlines = array();
  $count = 0;
  while($sql->fetch()){
    $outlines[$count] = array(  'id' => $id,
                                'title' => $title,
				'url' => $url,
				'type' => $type,
				'linkedon' => $linkedon,
				'avatarurl' => $avatarurl,
				'ownername' => $ownername,
				'ownerid' => $ownerid,
				'control' => $control,
     );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] outlines for user: [$uid]");
  return($outlines);
}


//_______________________________________________________________________________________
//Unlink an outline from a user
function unlink_outline_from_user($oid = NULL, $uid = NULL)
{
  //Check params
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

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_sopml_catalog WHERE uid=? AND oid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $uid, $oid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Unlinked: [$delcount] outline: [$oid] from user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Get and parse out the content of an outline
function get_outline_items($id = NULL, $max = NULL)
{
  //Check params
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the content of the feed
  $outline = get_outline_info($id);
  $url = $outline['url'];

  //Let's do some intelligent header checking so we don't waste time and bandwidth
  $lastmodtime = check_head_lastmod($url);
  if( ($lastmodtime == $outline['lastmod']) && ($lastmodtime != FALSE) ) {
    loggit(1, "Outline: [($url) $id] hasn't been updated. Skipping.");
    return(-3);
  }

  //Feed has been changed so grab the new content
  update_outline_lastmod($id, $lastmodtime);
  $goodurl = get_final_url($url);
  loggit(1, "Url: [$url]'s final destination is: [$goodurl].");
  $content = fetchUrl($goodurl);
  if($content == FALSE) {
    loggit(2, "Error attempting to get url: [$url]. See log for details.");
    return(-1);
  }

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Put the updated content in the database
  update_outline_content($id, $content);

  //Update the outline title
  $otitle = get_title_from_outline($content);
  if($otitle == FALSE) {
    $otitle = "Untitled Outline";
  }
  update_outline_title($id, $otitle);

  //Update the outline avatar
  $oav = get_avatar_url_from_outline($content);
  if($oav != FALSE) {
    update_outline_avatar($id, $oav);
  }

  //Update the outline canonical url
  $ocu = get_canonical_url_from_outline($content);
  if($ocu != FALSE) {
    update_outline_link($id, $ocu);
  }

  //Parse out the outline items

  //Put all of the items in an array
  $items = array();
  $count = 0;
  foreach($x->body->outline as $entry) {
      $items[$count] = $entry;
      add_outline_item($id, $entry);
      $count++;
  }

  if ($count == 0) {
    loggit(1, "Scan: There were no items in this outline: [$url].");
    return(-2);
  }

  //Log and leave
  loggit(1,"Scan: Got [$count] items from outline: [$url].");
  return($items);
}

//_______________________________________________________________________________________
//Get and parse out the content of an outline
function get_feeds_from_outline($content = NULL, $max = NULL)
{
  //Check params
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Grab only nodes that have an xmlUrl attribute
  $feeds = array();
  $nodes = $x->xpath('//outline[@xmlUrl]');
  if (empty($nodes)) {
    loggit(2, "This outline content didn't have any feeds.");
    return(-2);
  }

  //Run through each node and get the url into an array
  $count = 0;
  foreach($nodes as $entry) {
      $feeds[$count] = $entry->attributes()->xmlUrl;
      $count++;
  }

  if ($count == 0) {
    loggit(2, "There were no feeds in this outline.");
    return(-2);
  }

  //Log and leave
  loggit(1,"Got [$count] feeds from the outline.");
  return($feeds);
}

//_______________________________________________________________________________________
//Get and parse out the pub feeds from a social outline
function get_pub_feeds_from_outline($content = NULL, $max = NULL)
{
  //Check params
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Grab only nodes that have an xmlUrl attribute
  $feeds = array();
  $nodes = $x->xpath('//outline[@sopml:disposition=\'pub\' and @xmlUrl]');
  if (empty($nodes)) {
    loggit(3, "This outline doesn't publish any feeds.");
    return(-2);
  }

  //Run through each node and get the url into an array
  $count = 0;
  foreach($nodes as $entry) {
      $feedurl = $entry->attributes()->xmlUrl;
      $feeds[$count] = $feedurl;
      loggit(3, "Feed: [$feedurl] is published by this outline.");
      $count++;
  }

  if ($count == 0) {
    loggit(2, "There were no 'pub' feeds in this outline.");
    return(-2);
  }

  //Log and leave
  loggit(3,"Got [$count] 'pub' feeds from the outline.");
  return($feeds);
}


//_______________________________________________________________________________________
//Get and parse out the avatar url from a social outline
function get_avatar_url_from_outline($content = NULL)
{
  //Check params
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Get the sopml namespace first so we can work with it
  $namespaces = $x->getDocNamespaces();
  //loggit(3, print_r($namespaces, true));

  //If no sopml ns exists then bail
  if( !isset($namespaces['sopml']) ) {
    loggit(1, "This outline doesn't have an SOPML namespace.");
    return(FALSE);
  }

  //Search for an avatar
  $ns_sopml = $x->head->children($namespaces['sopml']);
  //loggit(3, print_r($ns_sopml, true));
  if( isset($ns_sopml->avatar) ) {
    $url = $ns_sopml->avatar;
    loggit(1, "SOPML: Avatar url is: [$url].");
    //Log and leave
    loggit(1, "The avatar of this outline is at: [$url].");
    return($url);
  }

  //Child namespace was empty so no avatar found
  loggit(2, "This outline content didn't have an avatar element in it's head.");
  return(FALSE);
}


//_______________________________________________________________________________________
//Get and parse out the canonical url from a social outline
function get_canonical_url_from_outline($content = NULL)
{
  //Check params
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Get the sopml namespace first so we can work with it
  $namespaces = $x->getDocNamespaces();
  //loggit(3, print_r($namespaces, true));

  //If no sopml ns exists then bail
  if( !isset($namespaces['sopml']) ) {
    loggit(2, "This outline doesn't have an SOPML namespace.");
    return(FALSE);
  }

  //Search for a link
  $ns_sopml = $x->head->children($namespaces['sopml']);
  //loggit(3, print_r($ns_sopml, true));
  if( isset($ns_sopml->url) ) {
    $url = $ns_sopml->url;
    loggit(1, "SOPML: Link url is: [$url].");
    //Log and leave
    loggit(1, "The canonical url of this outline is at: [$url].");
    return($url);
  }

  //Child namespace was empty so no avatar found
  loggit(2, "This outline content didn't have a canonical link element in it's head.");
  return(FALSE);
}


//_______________________________________________________________________________________
//Get and parse out the title of an outline
function get_title_from_outline($content = NULL, $max = NULL)
{
  //Check params
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Grab only the title node
  $title = $x->head->title;
  if (empty($title)) {
    loggit(2, "This outline content didn't have a title element in it's head.");
    return(FALSE);
  }

  //Log and leave
  loggit(1, "The title of this outline is: [$title].");
  return($title);
}

//_______________________________________________________________________________________
//Get and parse out the title of an outline
function get_ownername_from_outline($content = NULL, $max = NULL)
{
  //Check params
  if($content == NULL) {
    loggit(2,"The outline content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Parse it
  $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

  //Grab only the title node
  $name = $x->head->ownerName;
  if (empty($name)) {
    loggit(2, "This outline content didn't have an ownername element in it's head.");
    return(FALSE);
  }

  //Log and leave
  loggit(1, "The ownername of this outline is: [$name].");
  return($name);
}


//_______________________________________________________________________________________
//Retrieve a list of all the outlines in the database
function get_all_outlines($max = NULL)
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Grab all the outlines
  $sqltxt = "SELECT id,url,title,type FROM $table_sopml_outlines";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  //loggit(3, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any outlines
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"There are no outlines in the system.");
    return(FALSE);
  }

  $sql->bind_result($oid,$ourl,$otitle,$otype) or print(mysql_error());

  $outlines = array();
  $count = 0;
  while($sql->fetch()){
    $outlines[$count] = array( 'id' => $oid, 'url' => $ourl, 'title' => $otitle, 'type' => $otype );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] outlines in the system.");
  return($outlines);
}

//_______________________________________________________________________________________
//Add an outline item to the item storage table
function add_outline_item($id = NULL, $item = NULL, $format = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The outline id is blank or corrupt: [$id]");
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
  $iid = random_gen(128);
  $timeadded = time();

  //Grab the textual content of the node
  $content = (string)$item->attributes()->text;

  //Now that we have a good id, put the outline item into the database
  $stmt = "INSERT INTO $table_sopml_outlineitems (id,content,attributes,oid,timeadded,conthash) VALUES (?,?,?,?,?,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssssss", $iid,$content,$item->attributes(),$id,$timeadded,md5($content)) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  //loggit(1,"Put a new outline item: [$iid] in for outline: [$id].");
  return($iid);
}


//_______________________________________________________________________________________
//Turn an outline into html for viewing in a browser
function get_outline_as_html($uid = NULL, $max = NULL)
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
  $sqltxt = "SELECT $table_nfitem.id,$table_nfitem.title,$table_nfitem.url,$table_nfitem.timestamp,$table_nfitem.feedid,$table_nfitem.description
	     FROM $table_nfitem,$table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_nfitem.feedid)";
  $sqltxt .= " ORDER BY $table_nfitem.timestamp DESC";

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
    loggit(2,"The user: [$uid] has an empty river.");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$timestamp,$feedid,$description) or print(mysql_error());

  $count = 0;
  while($sql->fetch()){
    $feed = get_feed_info($feedid);
    $feedtitle = $feed['title'];
    $feedlink = $feed['link'];
    $river[$count] = array(
	'feedid' => $feedid,
        'feedtitle' => $feedtitle,
	'feedlink' => $feedlink,
        'id' => $id,
        'title' => $title,
        'url' => $url,
        'timestamp' => $timestamp,
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
//Build a social outline based on all the feeds this user publishes and subscribes to
function build_social_outline($uid = NULL, $archive = FALSE, $nos3 = FALSE)
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
  $feeds = get_feeds($uid);
  $pubfeeds = get_pub_feeds($uid);

  //Build urls
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
  	$sopmlurl = get_s3_url($uid, NULL, $default_social_outline_file_name);
  	$carturl = get_s3_url($uid, NULL, get_cartulary_feed_filename($uid));
  	$blogurl = get_s3_url($uid, NULL, get_microblog_feed_filename($uid));
  } else {
  	$sopmlurl = $mysopml.'?uid='.$uid;
  	$carturl = $articlespage.'-rss?uid='.$uid;
  	$blogurl = $microblogpage.'-rss?uid='.$uid;
  }

  //Build titles
  if( !empty($prefs['microblogtitle']) ) {
    $mbtitle = $prefs['microblogtitle'];
  } else {
    $mbtitle = "What $username is saying.";
  }
  if( !empty($prefs['cartularytitle']) ) {
    $catitle = $prefs['cartularytitle'];
  } else {
    $catitle = "What $username is reading.";
  }


  //The feed string
  $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
  $opml .= "<!-- OPML generated by ".$system_name." v".$version." on ".date("D, d M Y H:i:s O")." -->\n";
  $opml .= '<opml version="2.0" xmlns:sopml="'.$sopmlnamespaceurlv1.'">'."\n";

  $opml .= "
      <head>
        <title>$username's Social Outline</title>
        <dateCreated>".date("D, d M Y H:i:s O", time())."</dateCreated>
        <dateModified>".date("D, d M Y H:i:s O", time())."</dateModified>
        <ownerName>".get_user_name_from_uid($uid)."</ownerName>
        <ownerId>".$uid."</ownerId>
        <sopml:guid>[a registered global guid]</sopml:guid>
        <sopml:luid>".$uid."</sopml:luid>
        <sopml:url>".$sopmlurl."</sopml:url>
        <sopml:avatar>".$prefs['avatarurl']."</sopml:avatar>
        <sopml:timezone>".$prefs['timezone']."</sopml:timezone>
        <sopml:updateStart host=\"".$system_url."\" url=\"/update/start\" required=\"host callback key\" />
        <sopml:updateStop host=\"".$system_url."\" url=\"/update/stop\" required=\"host callback key\" />
        <sopml:updateChange host=\"".$system_url."\" url=\"/update/change\" required=\"host callback key\" />
      </head>\n";

  $opml .= "
      <body>";

  //Pub list
  $opml .= "
          <outline text=\"My Stuff\">";
  $opml .= "
              <outline text=\"$mbtitle\" description=\"$mbtitle\" type=\"rss\" xmlUrl=\"$blogurl\" sopml:disposition=\"pub\" sopml:contains=\"text\" sopml:lastupdate=\"\" />";
  if($prefs['publicdefault'] != 1) {
    $opml .= "
              <outline text=\"$catitle\" description=\"$catitle\" type=\"rss\" xmlUrl=\"$carturl\" sopml:disposition=\"pub\" sopml:contains=\"text html\" sopml:lastupdate=\"\"/>";
  }
  foreach( $pubfeeds as $pubfeed ) {
      $opml .= "
              <outline text=\"".htmlspecialchars(trim(str_replace("\n", '', htmlentities($pubfeed['title']))))."\" type=\"rss\" description=\"\" xmlUrl=\"".htmlspecialchars($pubfeed['url'])."\" sopml:disposition=\"pub\" sopml:contains=\"text html image audio video\" sopml:lastupdate=\"\" sopml:attention=\"\" />";
  }
  $opml .= "
          </outline>";

  //Sub list
  $opml .= "

          <outline text=\"Stuff I Follow\">";
  foreach( $feeds as $feed ) {
      $hidden = 'sopml:hidden="false"';
      if( $feed['hidden'] == 1 ) {
        $hidden = 'sopml:hidden="true"';
      }
      $sticky = 'sopml:sticky="false"';
      if( $feed['sticky'] == 1 ) {
        $sticky = 'sopml:sticky="true"';
      }
      $opml .= "
              <outline text=\"".htmlspecialchars(trim(str_replace("\n", '', htmlentities($feed['title']))))."\" type=\"rss\" description=\"\" xmlUrl=\"".htmlspecialchars($feed['url'])."\" sopml:disposition=\"sub\" sopml:contains=\"text html image audio video\" sopml:lastupdate=\"\" sopml:attention=\"\" $sticky $hidden />";
  }
  $opml .= "
          </outline>";

  $opml .= "
      </body>\n";

  $opml .= "</opml>";



  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the file name
    $filename = $default_social_outline_file_name;
    $arcpath = '';

    //Was this a request for a monthly archive?
    if( $archive != FALSE ) {
      $arcpath = "/arc/".date('Y')."/".date('m')."/".date('d');
      //loggit(3, "Archive path: [".$arcpath."]");
    }

    //Put the file
    $s3res = putInS3($opml, $filename, $s3info['bucket'].$arcpath, $s3info['key'], $s3info['secret'], "text/xml");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $filename);
      loggit(1, "Wrote file to S3 at url: [$s3url].");
    }
  }


  loggit(1, "Built social outline for user: [$username | $uid].");
  return($opml);
}


//_______________________________________________________________________________________
//Create a new, blank outline to use as a reading list
function build_reading_list($title = NULL, $uid = NULL, $oid = NULL, $nos3 = FALSE)
{
  //Check parameters
  if( empty($title) ) {
    loggit(2,"The outline title is blank or corrupt: [$title]");
    return(FALSE);
  }
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$includes/feeds.php";

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get username
  $username = get_user_name_from_uid($uid);
  $prefs = get_user_prefs($uid);

  //The feed string
  $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
  $opml .= "<!-- OPML generated by ".$system_name." v".$version." on ".date("D, d M Y H:i:s O")." -->\n";
  $opml .= '<opml version="2.0">'."\n";

  $opml .= "
      <head>
        <title>".xmlentities($title)."</title>
        <ownerName>".xmlentities($username)."</ownerName>
        <ownerId>".$uid."</ownerId>
      </head>\n";

  $opml .= "
      <body>";

  if( empty($oid) ) {
	$id = random_gen(128);
	loggit(3, "Created a new reading list: [$id].");
  } else {
	$id = $oid;
	$ofeeds = get_feeds_by_outline_id($id);
	foreach( $ofeeds as $ofeed ) {
	  $feed = get_feed_info($ofeed);
	  //loggit(3, print_r($feed, TRUE));
          $opml .= "
              <outline text=\"".trim(str_replace("\n", '', htmlspecialchars($feed['title'])))."\" description=\"\" htmlUrl=\"".htmlspecialchars($feed['link'])."\" xmlUrl=\"".htmlspecialchars($feed['url'])."\" />";
  	}
  }

  $opml .= "
      </body>";

  $opml .= "</opml>";


  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Create a filename from the given title, otherwise use a timestamp
    $path = "/orl";
    if( empty($oid) ) {
      $filename = time()."-".stripText($title).".opml";
    } else {
      $outline = get_outline_info($oid);
      $filename = $outline['filename'];
    }

    //Put the file
    $s3res = putInS3($opml, $filename, $s3info['bucket'].$path, $s3info['key'], $s3info['secret'], "text/xml");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $path, $filename);
      loggit(1, "Wrote file to S3 at url: [$s3url].");
    }
  }

  //We need to fail if we couldn't do s3 properly
  if( isset($s3url) ) {
    $url = $s3url;
  } else {
    loggit(2, "Couldn't get a url for the new reading list.  Check S3 settings.");
    return(FALSE);
  }

  //Now that we have a good id and a url, put the outline into the database
  if( empty($oid) ) {
    $stmt = "INSERT INTO $table_sopml_outlines (id,title,url,type,content,ownername,ownerid,control,filename) VALUES (?,?,?,'list',?,?,?,'local',?)";
    $sql=$dbh->prepare($stmt) or print(mysql_error());
    $sql->bind_param("sssssss", $id,$title,$url,$opml,$username,$uid,$filename) or print(mysql_error());
    $sql->execute() or print(mysql_error());
    $sql->close() or print(mysql_error());
  }

  //Now link the outline's id to this user's id
  if($uid != NULL) {
    link_outline_to_user($id, $uid);
  }

  //Log and return
  loggit(1,"Built outline: [$id] for user: [$uid] with title: [$title].");
  if( empty($oid) ) {
    return($id);
  } else {
    return($url);
  }
}


//########################################################################################
?>
