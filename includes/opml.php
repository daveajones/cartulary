<?php
//########################################################################################
// API for managing opml content
//########################################################################################


//Test if the given content is an opml outline
function is_outline($content = NULL)
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
    libxml_clear_errors();

    //Look for opml nodes with an xmlUrl attribute
    $nodes = $x->xpath('//outline');
    if (!empty($nodes)) {
        loggit(1, "This content looks like an opml outline.");
        return (TRUE);
    }

    //None of the tests passed so return FALSE
    loggit(1, "The content tested was not an opml outline.");
    return (FALSE);
}


//Test if the given content is an opml reading list
function is_reading_list($content = NULL)
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
    libxml_clear_errors();

    //Look for opml nodes with an xmlUrl attribute
    $nodes = $x->xpath('//outline[@xmlUrl]');
    if (!empty($nodes)) {
        loggit(1, "This content looks like a reading list.");
        return (TRUE);
    }

    //None of the tests passed so return FALSE
    loggit(1, "The content tested was not a reading list.");
    return (FALSE);
}


//Test if the given content is a social outline graph
function is_social_outline($content = NULL)
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
    libxml_clear_errors();

    //Look for sopml specific elements
    $namespaces = $x->getDocNamespaces();
    loggit(1, print_r($namespaces, true));
    if (array_key_exists('sopml', $namespaces)) {
        loggit(1, "This content looks like a social outline.");
        return (TRUE);
    }

    //None of the tests passed so return FALSE
    loggit(1, "The content tested was not a social outline.");
    return (FALSE);
}


//Check if an outline is already in the repository
function outline_exists($url = NULL)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The outline url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id FROM $table_sopml_outlines WHERE url=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The outline at url: [$url] does not exist in the repository.");
        return (FALSE);
    }
    $sql->bind_result($oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "The outline: [$oid] at url: [$url] does exist in the repository.");
    return ($oid);
}


//Check if an outline is already in the repository
function outline_exists_by_id($id = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id FROM $table_sopml_outlines WHERE id=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The outline: [$id] does not exist in the repository.");
        return (FALSE);
    }
    $sql->bind_result($oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "The outline: [$oid] does exist in the repository.");
    return (TRUE);
}


//Check if an outline item already exists
function outline_item_exists($oid = NULL, $item = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }
    if ($item == NULL) {
        loggit(2, "The outline item is blank or corrupt: [$item]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Text content of the item
    $content = (string)$item->attributes()->text;

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id,content FROM $table_sopml_outlineitems WHERE oid=? AND conthash=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $oid, md5($content)) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The outline item does not exist in the repository.");
        return (FALSE);
    }
    $sql->bind_result($iid, $content) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(3, "The outline item: [$iid | $content] already exists.");
    return (TRUE);
}


//See how many subscribers an outline has
function get_outline_subscriber_count($oid = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the catalog
    $sql = $dbh->prepare("SELECT oid FROM $table_sopml_catalog WHERE oid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    $subcount = $sql->num_rows();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "The outline: [$oid] has: [$subcount] subscribers.");
    return ($subcount);
}


//Return an array of user ids that subscribe to this outline
function get_outline_subscribers($oid = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the catalog
    $sql = $dbh->prepare("SELECT uid FROM $table_sopml_catalog WHERE oid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there are any subscribers to this feed
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Outline: [$oid] has no subscribers.");
        return (FALSE);
    }

    $sql->bind_result($uid) or loggit(2, "MySql error: " . $dbh->error);

    $users = array();
    $count = 0;
    while ($sql->fetch()) {
        $users[$count] = $uid;
        $count++;
    }
    $subcount = count($users);

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning an array of: [$subcount] user id's for outline: [$oid].");
    return ($users);
}


//See how many items are in the database for a given feed
function get_outline_item_count($oid = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id FROM $table_sopml_items WHERE oid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if any rows came back
    $itemcount = $sql->num_rows();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "The outline: [$oid] has: [$itemcount] items.");
    return ($itemcount);
}


//Get the items for a particular outline
function get_items_by_outline_id($oid = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the url in the feed table
    $sql = $dbh->prepare("SELECT id,content,attributes,timeadded FROM $table_sopml_items WHERE oid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    $sql->bind_result($iid, $content, $attributes, $timeadded) or loggit(2, "MySql error: " . $dbh->error);

    $items = array();
    $count = 0;
    while ($sql->fetch()) {
        $items[$count] = array('id' => $iid, 'content' => $content, 'attributes' => $attributes, 'timeadded' => $timeadded);
        $count++;
    }
    $subcount = count($items);


    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$itemcount] items for outline: [$oid].");
    return ($items);
}


//Retrieve an array of info about an outline
function get_outline_info($id = NULL)
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The outline id given is corrupt or blank: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT title,
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
    or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve info for outline id: [$id]");
        return (FALSE);
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
    ) or loggit(2, "MySql error: " . $dbh->error);

    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //loggit(3,"Returning info for outline: [$id]");
    //loggit(3,"Returning url for outline: [".$outline['url']."]");
    return ($outline);
}


//Retrieve the content blob for this outline
function get_outline_content($id = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id given is corrupt or blank: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT content FROM $table_sopml_outlines WHERE id=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve content for outline: [$id]");
        return (FALSE);
    }
    $sql->bind_result($content) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //loggit(1,"Returning info for outline: [$id]");
    return ($content);
}


//Add an outline to the database
function add_outline($url = NULL, $uid = NULL, $type = '')
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The outline url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Timestamp
    $id = random_gen(128);

    //Check if outline already exists
    $oid = outline_exists($url);
    if ($oid == FALSE) {
        //Now that we have a good id, put the outline into the database
        $stmt = "INSERT INTO $table_sopml_outlines (id,url,type) VALUES (?,?,?)";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("sss", $id, $url, $type) or loggit(2, "MySql error: " . $dbh->error);
        $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
        $sql->close() or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $id = $oid;
    }

    //Now link the outline's id to this user's id
    if ($uid != NULL) {
        link_outline_to_user($id, $uid);
    }

    //Log and return
    loggit(1, "Put a new outline in the repository: [$id] with url [$url].");
    return ($id);
}


//Add a social outline to the database
function add_social_outline($url = NULL, $uid = NULL)
{
    //Check parameters
    if ($url == NULL) {
        loggit(2, "The outline url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Timestamp
    $id = random_gen(128);
    $type = "sopml";

    //Check if outline already exists
    $oid = outline_exists($url);
    if ($oid == FALSE) {
        //Now that we have a good id, put the outline into the database
        $stmt = "INSERT INTO $table_sopml_outlines (id,url,type) VALUES (?,?,?)";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("sss", $id, $url, $type) or loggit(2, "MySql error: " . $dbh->error);
        $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
        $sql->close() or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $id = $oid;
    }

    //Now link the outline's id to this user's id
    if ($uid != NULL) {
        link_outline_to_user($id, $uid);
    }

    //Log and return
    loggit(1, "Put a new social outline in the repository: [$id] with url [$url].");
    return ($id);
}


//Change the content of an outline
function update_outline_content($id = NULL, $content = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Vars
    $tstamp = time();

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET content=?,lastupdate=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $content, $tstamp, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated content for outline: [$id].");
    return (TRUE);
}


//Change the last modified time of an outline
function update_outline_lastmod($id = NULL, $lastmod = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($lastmod == NULL) {
        loggit(2, "The outline last-modified time is blank or corrupt: [$lastmod]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET lastmod=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $lastmod, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated last-modified time to: [$lastmod] for outline: [$id].");
    return (TRUE);
}


//Change the title of an outline
function update_outline_title($id = NULL, $title = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($title == NULL) {
        loggit(2, "The outline title is blank or corrupt: [$title]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Clean the title
    $title = trim($title);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET title=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $title, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated title to: [$title] for outline: [$id].");
    return (TRUE);
}


//Change the type of an outline
function update_outline_type($id = NULL, $type = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($type == NULL) {
        loggit(2, "The outline type is blank or corrupt: [$title]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Clean the title
    $type = trim($type);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET type=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $type, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated type to: [$type] for outline: [$id].");
    return (TRUE);
}


//Change the owner name of an outline
function update_outline_ownername($id = NULL, $name = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($name == NULL) {
        loggit(2, "The owner name is blank or corrupt: [$name]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET ownername=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $name, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated owner name to: [$name] for outline: [$id].");
    return (TRUE);
}


//Change the avatar url of a social outline
function update_outline_avatar($id = NULL, $url = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($url == NULL) {
        loggit(2, "The avatar url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET avatarurl=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $url, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated avatar url to: [$url] for outline: [$id].");
    return (TRUE);
}


//Change the link url of a social outline
function update_outline_link($id = NULL, $url = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($url == NULL) {
        loggit(2, "The outline link url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET url=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $url, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated link url to: [$url] for outline: [$id].");
    return (TRUE);
}


//Change the last check time of an outline
function update_outline_lastcheck($id = NULL, $lastcheck = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }
    if ($lastcheck == NULL) {
        loggit(2, "The outline lastcheck time is blank or corrupt: [$lastcheck]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_sopml_outlines SET lastcheck=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $lastcheck, $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Updated lastcheck time to: [$lastcheck] for outline: [$id].");
    return (TRUE);
}


//Check if this outline is linked to a user
function outline_is_linked($oid = NULL, $uid = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
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
    $sql = $dbh->prepare("SELECT * FROM $table_sopml_catalog WHERE oid=? AND uid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $oid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The outline: [$oid] is NOT linked to user: [$uid].");
        return (FALSE);
    }
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "The outline: [$oid] IS linked to user: [$uid].");
    return (TRUE);
}


//Link an outline to a user
function link_outline_to_user($oid = NULL, $uid = NULL)
{
    //Check parameters
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    $tstamp = time();

    //Create a random color
    $color = random_gen(6, 'abcdef');

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Link the article to the user
    $stmt = "INSERT INTO $table_sopml_catalog (uid,oid,linkedon,color) VALUES (?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssss", $uid, $oid, $tstamp, $color) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Linked outline: [$oid] with user: [$uid] and color: [$color].");
    return (TRUE);
}


//Retrieve a list of outlines subscribed to by the given user
function get_outlines($uid = NULL, $max = NULL, $otype = NULL, $ididx = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$includes/feeds.php";

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

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

    if ($otype != NULL) {
        $sqltxt .= " AND $table_sopml_outlines.type = ?";
    }

    $sqltxt .= " ORDER BY $table_sopml_outlines.title ASC";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    if ($otype != NULL) {
        $sql->bind_param("ss", $uid, $otype) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    }
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "This user has not subscribed to any outlines: [$uid]");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $type, $linkedon, $avatarurl, $ownername, $ownerid, $control, $color) or loggit(2, "MySql error: " . $dbh->error);

    $outlines = array();
    $count = 0;
    while ($sql->fetch()) {
        if ($ididx == TRUE) {
            $idx = $id;
        } else {
            $idx = $count;
        }
        $outlines[$idx] = array('id' => $id,
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

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] outlines for user: [$uid]");
    return ($outlines);
}


//Retrieve a list of social outlines subscribed to by the given user
function get_social_outlines($uid = NULL, $max = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Globals
    $type = 'sopml';

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

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

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $type) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any feeds for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "This user has not subscribed to any outlines: [$uid]");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $type, $linkedon, $avatarurl, $ownername, $ownerid, $control) or loggit(2, "MySql error: " . $dbh->error);

    $outlines = array();
    $count = 0;
    while ($sql->fetch()) {
        $outlines[$count] = array('id' => $id,
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

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] outlines for user: [$uid]");
    return ($outlines);
}


//Unlink an outline from a user
function unlink_outline_from_user($oid = NULL, $uid = NULL)
{
    //Check params
    if ($oid == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
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

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_sopml_catalog WHERE uid=? AND oid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and leave
    loggit(1, "Unlinked: [$delcount] outline: [$oid] from user: [$uid].");
    return (TRUE);
}


//Get and parse out the content of an outline
function get_outline_items($id = NULL, $max = NULL)
{
    //Check params
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the content of the feed
    $outline = get_outline_info($id);
    $url = $outline['url'];

    //Let's do some intelligent header checking so we don't waste time and bandwidth
    $lastmodtime = check_head_lastmod($url);
    if (($lastmodtime == $outline['lastmod']) && ($lastmodtime != FALSE)) {
        loggit(1, "Outline: [($url) $id] hasn't been updated. Skipping.");
        return (-3);
    }

    //Feed has been changed so grab the new content
    update_outline_lastmod($id, $lastmodtime);
    $goodurl = get_final_url($url);
    loggit(1, "Url: [$url]'s final destination is: [$goodurl].");
    $content = fetchUrl($goodurl);
    if ($content == FALSE) {
        loggit(2, "Error attempting to get url: [$url]. See log for details.");
        return (-1);
    }

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Put the updated content in the database
    update_outline_content($id, $content);

    //Update the outline title
    $otitle = get_title_from_outline($content);
    if ($otitle == FALSE) {
        $otitle = "Untitled Outline";
    }
    update_outline_title($id, $otitle);

    //Update the outline avatar
    $oav = get_avatar_url_from_outline($content);
    if ($oav != FALSE) {
        update_outline_avatar($id, $oav);
    }

    //Update the outline canonical url
    $ocu = get_canonical_url_from_outline($content);
    if ($ocu != FALSE) {
        update_outline_link($id, $ocu);
    }

    //Update the outline owner name
    $oon = get_ownername_from_outline($content);
    if ($oon != FALSE) {
        update_outline_ownername($id, $oon);
    }

    //Parse out the outline items

    //Put all of the items in an array
    $items = array();
    $count = 0;
    $nodes = $x->xpath('//outline');
    foreach ($nodes as $entry) {
        $items[$count] = $entry;
        add_outline_item($id, $entry);
        $count++;
    }

    if ($count == 0) {
        loggit(1, "Scan: There were no items in this outline: [$url].");
        return (-2);
    }

    //Log and leave
    loggit(1, "Scan: Got [$count] items from outline: [$url].");
    return ($items);
}


//Get and parse out the content of an outline
function get_feeds_from_outline($content = NULL, $max = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Grab only nodes that have an xmlUrl attribute
    $feeds = array();
    $nodes = $x->xpath('//outline[@xmlUrl]');
    if (empty($nodes)) {
        loggit(2, "This outline content didn't have any feeds.");
        return (-2);
    }

    //Run through each node and get the url into an array
    $count = 0;
    foreach ($nodes as $entry) {
        $feeds[$count] = $entry->attributes()->xmlUrl;
        $count++;
    }

    if ($count == 0) {
        loggit(2, "There were no feeds in this outline.");
        return (-2);
    }

    //Log and leave
    loggit(1, "Got [$count] feeds from the outline.");
    return ($feeds);
}


//Get and parse out the pub feeds from a social outline
function get_pub_feeds_from_outline($content = NULL, $max = NULL, $withattr = FALSE)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Grab only nodes that have an xmlUrl attribute
    $feeds = array();
    $nodes = $x->xpath('//outline[@sopml:disposition=\'pub\' and @xmlUrl]');
    if (empty($nodes)) {
        loggit(3, "This outline doesn't publish any feeds.");
        return (-2);
    }

    //Run through each node and get the url into an array
    $count = 0;
    foreach ($nodes as $entry) {
        $feedurl = (string)$entry->attributes()->xmlUrl;
        if ($withattr == FALSE) {
            $feeds[$count] = $feedurl;
        } else {
            $feedtext = (string)$entry->attributes()->text;
            $feedhtml = (string)$entry->attributes()->htmlUrl;
            $feeds[$count] = array('url' => $feedurl, 'text' => $feedtext, 'html' => $feedhtml);
        }
        loggit(3, "Feed: [$feedurl] is published by this outline.");
        $count++;
    }

    if ($count == 0) {
        loggit(2, "There were no 'pub' feeds in this outline.");
        return (-2);
    }

    //Log and leave
    loggit(3, "Got [$count] 'pub' feeds from the outline.");
    return ($feeds);
}


//Get and parse out the avatar url from a social outline
function get_avatar_url_from_outline($content = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Get the sopml namespace first so we can work with it
    $namespaces = $x->getDocNamespaces();
    //loggit(3, print_r($namespaces, true));

    //If no sopml ns exists then bail
    if (!isset($namespaces['sopml'])) {
        loggit(1, "This outline doesn't have an SOPML namespace.");
        return (FALSE);
    }

    //Search for an avatar
    $ns_sopml = $x->head->children($namespaces['sopml']);
    //loggit(3, print_r($ns_sopml, true));
    if (isset($ns_sopml->avatar)) {
        $url = (string)$ns_sopml->avatar;
        loggit(1, "SOPML: Avatar url is: [$url].");
        //Log and leave
        loggit(1, "The avatar of this outline is at: [$url].");
        return ($url);
    }

    //Child namespace was empty so no avatar found
    loggit(1, "This outline content didn't have an avatar element.");
    return (FALSE);
}


//Get and parse out the server address from an outline
function get_server_address_from_outline($content = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Get the sopml namespace first so we can work with it
    $namespaces = $x->getDocNamespaces();

    //If no sopml ns exists then bail
    if (!isset($namespaces['sopml'])) {
        loggit(1, "This outline doesn't have an SOPML namespace.");
        return (FALSE);
    }

    //Search for a server
    $ns_sopml = $x->head->children($namespaces['sopml']);
    if (isset($ns_sopml->server)) {
        $addr = (string)$ns_sopml->server;
        loggit(3, "SOPML: Server addr is: [$addr].");
        //Log and leave
        loggit(1, "The server of this outline is at: [$addr].");
        return ($addr);
    }

    //Child namespace was empty so no server found
    loggit(1, "This outline content didn't have a server element.");
    return (FALSE);
}


//Get and parse out the canonical url from a social outline
function get_canonical_url_from_outline($content = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Get the sopml namespace first so we can work with it
    $namespaces = $x->getDocNamespaces();
    //loggit(3, print_r($namespaces, true));

    //If no sopml ns exists then bail
    if (!isset($namespaces['sopml'])) {
        loggit(2, "This outline doesn't have an SOPML namespace.");
        return (FALSE);
    }

    //Search for a link
    $ns_sopml = $x->head->children($namespaces['sopml']);
    //loggit(3, print_r($ns_sopml, true));
    if (isset($ns_sopml->url)) {
        $url = (string)$ns_sopml->url;
        loggit(1, "SOPML: Link url is: [$url].");
        //Log and leave
        loggit(1, "The canonical url of this outline is at: [$url].");
        return ($url);
    }

    //Child namespace was empty so no canonical link found
    loggit(2, "This outline content didn't have a canonical link element in it's head.");
    return (FALSE);
}


//Get and parse out the title of an outline
function get_title_from_outline($content = NULL, $max = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Grab only the title node
    $title = (string)$x->head->title;
    if (empty($title)) {
        loggit(2, "This outline content didn't have a title element in it's head.");
        return (FALSE);
    }

    //Log and leave
    loggit(1, "The title of this outline is: [$title].");
    return ($title);
}


//Get and parse out the title of an outline
function get_ownername_from_outline($content = NULL, $max = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Grab only the title node
    $name = (string)$x->head->ownerName;
    if (empty($name)) {
        loggit(2, "This outline content didn't have an ownername element in it's head.");
        return (FALSE);
    }

    //Log and leave
    loggit(1, "The ownername of this outline is: [$name].");
    return ($name);
}


//Retrieve a list of all the outlines in the database
function get_all_outlines($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Grab all the outlines
    $sqltxt = "SELECT id,url,title,type FROM $table_sopml_outlines";

    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any outlines
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "There are no outlines in the system.");
        return (array());
    }

    $sql->bind_result($oid, $ourl, $otitle, $otype) or loggit(2, "MySql error: " . $dbh->error);

    $outlines = array();
    $count = 0;
    while ($sql->fetch()) {
        $outlines[$count] = array('id' => $oid, 'url' => $ourl, 'title' => $otitle, 'type' => $otype);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] outlines in the system.");
    return ($outlines);
}


//Add an outline item to the item storage table
function add_outline_item($id = NULL, $item = NULL, $format = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The outline id is blank or corrupt: [$id]");
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
    $iid = random_gen(128);
    $timeadded = time();

    //Grab the textual content of the node
    $content = (string)$item->attributes()->text;

    //Now that we have a good id, put the outline item into the database
    $stmt = "INSERT INTO $table_sopml_outlineitems (id,content,attributes,oid,timeadded,conthash) VALUES (?,?,?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssssss", $iid, $content, $item->attributes(), $id, $timeadded, md5($content)) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    //loggit(1,"Put a new outline item: [$iid] in for outline: [$id].");
    return ($iid);
}


//Turn an outline into html for viewing in a browser
function get_outline_as_html($uid = NULL, $max = NULL)
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
    $sqltxt = "SELECT $table_nfitem.id,$table_nfitem.title,$table_nfitem.url,$table_nfitem.timestamp,$table_nfitem.feedid,$table_nfitem.description
	     FROM $table_nfitem,$table_nfcatalog
	     WHERE $table_nfcatalog.userid=?
             AND ($table_nfcatalog.feedid=$table_nfitem.feedid)";
    $sqltxt .= " ORDER BY $table_nfitem.timestamp DESC";

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
        loggit(2, "The user: [$uid] has an empty river.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $url, $timestamp, $feedid, $description) or loggit(2, "MySql error: " . $dbh->error);

    $count = 0;
    while ($sql->fetch()) {
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

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] items in user: [$uid]'s river.");
    return ($river);
}


//Build a social outline based on all the feeds this user publishes and subscribes to
function build_social_outline($uid = NULL, $archive = FALSE, $nos3 = FALSE)
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
    $feeds = get_feeds($uid);
    $pubfeeds = get_pub_feeds($uid);

    //Build urls
    if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
        $sopmlurl = get_s3_url($uid, NULL, $default_social_outline_file_name);
        $carturl = get_s3_url($uid, NULL, get_cartulary_feed_filename($uid));
        $blogurl = get_s3_url($uid, NULL, get_microblog_feed_filename($uid));
        $bloghtml = get_s3_url($uid, NULL, get_microblog_html_filename($uid));
    } else {
        $sopmlurl = $mysopml . '?uid=' . $uid;
        $carturl = $articlespage . '-rss?uid=' . $uid;
        $blogurl = $microblogpage . '-rss?uid=' . $uid;
        $bloghtml = "";
    }

    //Build titles
    if (!empty($prefs['microblogtitle'])) {
        $mbtitle = $prefs['microblogtitle'];
    } else {
        $mbtitle = "What $username is saying.";
    }
    if (!empty($prefs['cartularytitle'])) {
        $catitle = $prefs['cartularytitle'];
    } else {
        $catitle = "What $username is reading.";
    }

    //What should the server declaration be?
    if (!empty($s3_sys_server_redirect_bucket) && !empty($cg_external_ip_reflector_url)) {
        $serverdec = $s3_sys_server_redirect_bucket;
    } else {
        $serverdec = $system_fqdn;
    }

    //The feed string
    $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
    $opml .= "<!-- OPML generated by " . $system_name . " v" . $version . " on " . date("D, d M Y H:i:s O") . " -->\n";
    $opml .= '<opml version="2.0" xmlns:sopml="' . $sopmlnamespaceurlv1 . '">' . "\n";

    $opml .= "
      <head>
        <title>$username's Social Outline</title>
        <dateCreated>" . date("D, d M Y H:i:s O", time()) . "</dateCreated>
        <dateModified>" . date("D, d M Y H:i:s O", time()) . "</dateModified>
        <ownerName>" . get_user_name_from_uid($uid) . "</ownerName>
        <ownerId>" . $uid . "</ownerId>
        <sopml:server>" . $serverdec . "</sopml:server>
        <sopml:guid>" . $cg_main_serverguid . "::" . $uid . "</sopml:guid>
        <sopml:luid>" . $uid . "</sopml:luid>
        <sopml:url>" . $sopmlurl . "</sopml:url>
        <sopml:avatar>" . $prefs['avatarurl'] . "</sopml:avatar>
        <sopml:timezone>" . $prefs['timezone'] . "</sopml:timezone>";
    if ($cg_opmlcloud_enabled == 1) {
        $opml .= "
        <sopml:updates host=\"" . $cg_opmlcloud_host . "\" port=\"" . $cg_opmlcloud_port . "\" type=\"" . $cg_opmlcloud_type . "\" register=\"" . random_gen(16) . "\" />";
    }
    $opml .= "
      </head>\n";

    $opml .= "
      <body>";

    //Pub list
    $opml .= "
          <outline text=\"My Stuff\">";
    $opml .= "
              <outline text=\"$mbtitle\" description=\"$mbtitle\" type=\"rss\" xmlUrl=\"$blogurl\" htmlUrl=\"$bloghtml\" sopml:disposition=\"pub\" sopml:contains=\"mixed\" />";
    if ($prefs['publicdefault'] != 1) {
        $opml .= "
              <outline text=\"$catitle\" description=\"$catitle\" type=\"rss\" xmlUrl=\"$carturl\" sopml:disposition=\"pub\" sopml:contains=\"html\" />";
    }
    foreach ($pubfeeds as $pubfeed) {
        $opml .= "
              <outline text=\"" . htmlspecialchars(trim(str_replace("\n", '', htmlentities($pubfeed['title'])))) . "\" type=\"rss\" description=\"\" xmlUrl=\"" . htmlspecialchars($pubfeed['url']) . "\" htmlUrl=\"" . htmlspecialchars($pubfeed['link']) . "\" sopml:disposition=\"pub\" sopml:contains=\"mixed\" />";
    }
    $opml .= "
          </outline>";

    //Sub list
    $opml .= "

          <outline text=\"Stuff I Follow\">";
    foreach ($feeds as $feed) {
        $hidden = 'sopml:hidden="false"';
        if ($feed['hidden'] == 1) {
            $hidden = 'sopml:hidden="true"';
        }
        $sticky = 'sopml:sticky="false"';
        if ($feed['sticky'] == 1) {
            $sticky = 'sopml:sticky="true"';
        }
        $fulltext = 'sopml:fulltext="false"';
        if ($feed['fulltext'] == 1) {
            $fulltext = 'sopml:fulltext="true"';
        }
        $feedtitle = "Untitled Feed";
        if (!empty($feed['title'])) {
            $feedtitle = $feed['title'];
        }
        $opml .= "
              <outline text=\"" . htmlspecialchars(trim(str_replace("\n", '', xmlentities($feedtitle)))) . "\" type=\"rss\" description=\"\" xmlUrl=\"" . htmlspecialchars($feed['url']) . "\" sopml:disposition=\"sub\" sopml:contains=\"mixed\" sopml:attention=\"50\" $sticky $hidden $fulltext />";
    }
    $opml .= "
          </outline>";

    $opml .= "
      </body>\n";

    $opml .= "</opml>";


    //If this user has S3 storage enabled, then do it
    if ((s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Get the file name
        $filename = $default_social_outline_file_name;
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
            loggit(1, "Wrote file to S3 at url: [$s3url].");
        }
    }


    loggit(1, "Built social outline for user: [$username | $uid].");
    return ($opml);
}


//Create a new, blank outline to use as a reading list
function build_reading_list($title = NULL, $uid = NULL, $oid = NULL, $nos3 = FALSE)
{
    loggit(3, "DEBUG: Something called build_reading_list()");
    //Check parameters
    if (empty($title)) {
        loggit(2, "The outline title is blank or corrupt: [$title]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$includes/feeds.php";

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Get username
    $username = get_user_name_from_uid($uid);
    $prefs = get_user_prefs($uid);

    //The feed string
    $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
    $opml .= "<!-- OPML generated by " . $system_name . " v" . $version . " on " . date("D, d M Y H:i:s O") . " -->\n";
    $opml .= '<opml version="2.0">' . "\n";

    $opml .= "
      <head>
        <title>" . xmlentities($title) . "</title>
        <ownerName>" . xmlentities($username) . "</ownerName>
        <ownerId>" . $uid . "</ownerId>
      </head>\n";

    $opml .= "
      <body>";

    if (empty($oid)) {
        $id = random_gen(128);
        loggit(3, "Created a new reading list: [$id].");
    } else {
        $id = $oid;
        $ofeeds = get_feeds_by_outline_id($id);
        foreach ($ofeeds as $ofeed) {
            $feed = get_feed_info($ofeed);
            //loggit(3, print_r($feed, TRUE));
            $opml .= "
              <outline text=\"" . trim(str_replace("\n", '', htmlspecialchars($feed['title']))) . "\" description=\"\" htmlUrl=\"" . htmlspecialchars($feed['link']) . "\" xmlUrl=\"" . htmlspecialchars($feed['url']) . "\" />";
        }
    }

    $opml .= "
      </body>";

    $opml .= "</opml>";


    //If this user has S3 storage enabled, then do it
    if ((s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Create a filename from the given title, otherwise use a timestamp
        $path = "/orl";
        if (empty($oid)) {
            $filename = time() . "-" . stripText($title) . ".opml";
        } else {
            $outline = get_outline_info($oid);
            $filename = $outline['filename'];
        }

        //Put the file
        $s3res = putInS3($opml, $filename, $s3info['bucket'] . $path, $s3info['key'], $s3info['secret'], "text/xml");
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
            //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
        } else {
            $s3url = get_s3_url($uid, $path, $filename);
            loggit(1, "Wrote file to S3 at url: [$s3url].");
        }
    }

    //We need to fail if we couldn't do s3 properly
    if (isset($s3url)) {
        $url = $s3url;
    } else {
        loggit(2, "Couldn't get a url for the new reading list.  Check S3 settings.");
        return (FALSE);
    }

    //Now that we have a good id and a url, put the outline into the database
    if (empty($oid)) {
        $stmt = "INSERT INTO $table_sopml_outlines (id,title,url,type,content,ownername,ownerid,control,filename) VALUES (?,?,?,'list',?,?,?,'local',?)";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("sssssss", $id, $title, $url, $opml, $username, $uid, $filename) or loggit(2, "MySql error: " . $dbh->error);
        $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
        $sql->close() or loggit(2, "MySql error: " . $dbh->error);
    }

    //Now link the outline's id to this user's id
    if ($uid != NULL) {
        link_outline_to_user($id, $uid);
    }

    //Log and return
    loggit(1, "Built outline: [$id] for user: [$uid] with title: [$title].");
    if (empty($oid)) {
        return ($id);
    } else {
        return ($url);
    }
}


//Return a social outline url for a given user id
function get_social_outline_url($uid = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Does the user have S3 enabled?
    if (!s3_is_enabled($uid) && !sys_s3_is_enabled()) {
        return (FALSE);
    }

    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the file name
    $filename = $default_social_outline_file_name;

    //Construct the url and return it
    $s3url = get_s3_url($uid, '', $filename);
    return ($s3url);
}


//Purge outlines from the database that aren't linked to anyone
function purge_orphaned_outlines($type = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Find articles that have no linkage
    $stmt = "DELETE FROM $table_sopml_outlines WHERE NOT EXISTS ( SELECT * FROM $table_sopml_catalog WHERE $table_sopml_outlines.id = $table_sopml_catalog.oid )";

    //Type selection
    if (!empty($type)) {
        $stmt .= " AND $table_sopml_outlines.type = ?";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("s", $type) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    }

    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and leave
    loggit(3, "Deleted: [$delcount] orphaned outlines.");
    return ($delcount);
}


//Set the purge flag on all of an outline's items
function mark_all_outline_items_to_purge($oid = NULL)
{
    //Check parameters
    if (empty($oid)) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "UPDATE $table_sopml_outlineitems SET `purge`=1 WHERE oid=?";
    $sql = $dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Marked: [$updcount] items in outline:[$oid] to purge.");
    return ($updcount);
}


//Un-set the purge flag on all of an outline's items
function unmark_all_outline_items_to_purge($oid = NULL)
{
    //Check parameters
    if (empty($oid)) {
        loggit(2, "The outline id is blank or corrupt: [$oid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Update all the items
    $stmt = "UPDATE $table_sopml_outlineitems SET `purge`=0 WHERE oid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Un-marked: [$updcount] items in outline:[$oid] to purge.");
    return ($updcount);
}


//Recursive function for parsing an entire outline structure
function displayChildrenRecursive($x = NULL, $indent = 0, $line = 0)
{

    foreach ($x->children() as $child) {
        $text = (string)$child->attributes()->text;
        $attr = (string)$child->attributes();

        echo str_repeat('-', $indent) . "> [" . (string)$child->attributes()->text . "][$line][$indent]\n";
        $line = displayChildrenRecursive($child, $indent + 1, $line + 1);
    }

    return ($line);
}


//Convert an opml document to html
function convert_opml_to_html($content = NULL, $max = NULL)
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The opml content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Roll through all of the outline nodes
    $nodes = $x->xpath('//outline');
    if (empty($nodes)) {
        loggit(3, "This opml document is blank.");
        return (-2);
    }

    //Run through each node and convert it to an html element
    $count = 0;
    $html = "";
    foreach ($nodes as $entry) {
        //loggit(3, "DEBUG: ".print_r($entry, TRUE));

        $text = (string)$entry->attributes()->text;
        $name = (string)$entry->attributes()->name;
        $link = (string)$entry->attributes()->url;
        $type = (string)$entry->attributes()->type;

        $html .= "$text\n";

        $count++;
    }

    //Log and leave
    loggit(3, "Got [$count] items from the opml document.");
    return ($html);
}


//Retrieve a list of the social outlines for all the users in the database
function get_social_outline_directory($query = NULL, $max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$includes/auth.php";

    //Get a list of all matching local users
    $users = search_users($query, $max);

    //Loop through and get the url for each social outline, but only if the user
    //has not marked themselves as private
    $sol = array();
    if (!empty($users)) {
        foreach ($users as $user) {
            $prefs = get_user_prefs($user['id']);
            if ($prefs['hideme'] != 1 && $user['email'] != "cartulary@localhost") {
                $sol[] = array(
                    'name' => $user['name'],
                    'url' => $user['sopmlurl'],
                    'avatarurl' => $user['avatarurl']
                );
            }
        }
    }

    return ($sol);
}