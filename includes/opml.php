<?php
//########################################################################################
// API for managing opml content
//########################################################################################


//Test if the given content is an opml outline
function is_outline($content = NULL)
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
    if (!$x) {
        loggit(2, "Parsing error when checking for opml content.");
        loggit(2, "---------------------------------------------");
        foreach (libxml_get_errors() as $error) {
            loggit(2, "XML PARSER[is_outline()]: " . $error->message);
        }
        loggit(2, "---------------------------------------------");
        libxml_clear_errors();
        return (FALSE);
    }
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
        loggit(2, "The outline type is blank or corrupt: [$type]");
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

    if (!empty($max) && is_numeric($max)) {
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

    if (!empty($max) && is_numeric($max)) {
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
    if (($lastmodtime == $outline['lastmod']) && !empty($lastmodtime)) {
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


//Get and parse out the include nodes from an outline
function get_includes_from_outline($content = NULL, $max = NULL)
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
    $nodes = $x->xpath('//outline[@url and @type="include"]');
    if (empty($nodes)) {
        loggit(2, "This outline content didn't have any include nodes.");
        return (-2);
    }

    //Run through each node and get the url into an array
    $count = 0;
    foreach ($nodes as $entry) {
        $urls[$count] = $entry->attributes()->url;
        $count++;
    }

    //loggit(3, "DEBUG: ".print_r($urls));

    if ($count == 0) {
        loggit(2, "There were no include nodes in this outline.");
        return (-2);
    }

    //Log and leave
    loggit(3, "Got [$count] include nodes from the outline.");
    return ($urls);
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

    if (!empty($max) && is_numeric($max)) {
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
    $stmt = "INSERT IGNORE INTO $table_sopml_outlineitems (id,content,attributes,oid,timeadded,conthash) VALUES (?,?,?,?,?,?)";
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

    if (!empty($max) && is_numeric($max)) {
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
    //Should we even build it?
    if ($prefs['hidesublist'] == 0) {
        loggit(3, "User: [$uid] doesn't want their subscription list shown.");
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
    }

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
            loggit(3, "Wrote file to S3 at url: [$s3url].");
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
        $line = "";
        //loggit(3, "DEBUG: ".print_r($entry, TRUE));

        $text = (string)$entry->attributes()->text;
        $name = (string)$entry->attributes()->name;
        $link = (string)$entry->attributes()->url;
        $type = (string)$entry->attributes()->type;

        $text = trim(html_entity_decode($text));

        if (!empty($link)) {
            $line .= "<p><a href=\"$link\">$text</a></p>";
        } else {
            $line .= "<p>$text</p>";
        }

        if (empty($text)) {
            $line = "<br/>";
        }

        $html .= $line . "\n";

        $count++;
    }

    //Collapse muliple br tags
    $output = preg_replace("/(<br\s*\/?>\s*)+/", "<br/>", $html);

    //Log and leave
    loggit(3, "Got [$count] items from the opml document.");
    return ($output);
}


//Convert an opml document to html
function convert_opml_to_text($content = NULL, $max = NULL)
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
    $raw = "";
    foreach ($nodes as $entry) {
        //loggit(3, "DEBUG: ".print_r($entry, TRUE));

        $text = (string)$entry->attributes()->text;
        $name = (string)$entry->attributes()->name;
        $link = (string)$entry->attributes()->url;
        $type = (string)$entry->attributes()->type;

        $raw .= "$text\n";

        $count++;
    }

    //Log and leave
    loggit(3, "Got [$count] items from the opml document.");
    return ($raw);
}


//Convert an opml document to html using xslt transformation
function transform_opml_to_html($content = NULL)
{
    //Check params
    if (empty($content)) {
        loggit(2, "The opml content is blank, corrupt or not valid opml.");
        return ("");
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //loggit(3, "DEBUG:\n[$content]");

    $content = "<body>$content</body>";

    $xslt_string = <<<XSLTSTRING
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:param name="owner" select="'Owner Name'"/>
  <xsl:output method="html" encoding="iso-8859-1" indent="yes"/>

<xsl:variable name="markerNormal">&#9656;</xsl:variable>
<xsl:variable name="markerClosed">&#9662;</xsl:variable>
<xsl:variable name="markerComment">&#8810;</xsl:variable>
<xsl:variable name="markerLink">&#9788;</xsl:variable>

<xsl:template match = "/opml" >
<html>
  <script language="JavaScript" src="http://www.netcrucible.com/xslt/opml.js" />
  <link rel="stylesheet" href="http://www.netcrucible.com/xslt/opml.css" />
  <head><title><xsl:value-of select="head/title" /></title></head>
  <body>
    <div id="outlineRoot" class="outlineRoot">
        <xsl:for-each select="head/*" >
        <span class="outlineAttribute" title="{name()}"><xsl:value-of select="." /></span>
        </xsl:for-each>
  	<xsl:apply-templates select="body"/>
    </div>
    <span id="markerNormal" style="display:none"><xsl:value-of select="\$markerNormal" /></span>
    <span id="markerComment" style="display:none"><xsl:value-of select="\$markerComment" /></span>
    <span id="markerLink" style="display:none"><xsl:value-of select="\$markerLink" /></span>
  </body>
</html>
</xsl:template>

<xsl:template match = "outline" >
  <div class="outline">
       <xsl:attribute name="style">
           <xsl:if test="parent::outline">margin-left:20px;</xsl:if>
       </xsl:attribute>
       <span style="margin-top:10px;">
           <xsl:attribute name="class">
               <xsl:choose>
                   <xsl:when test="./*">markerClosed</xsl:when>
                   <xsl:when test="contains(@url,'.opml') or contains(@url,'.OPML')">markerClosed</xsl:when>
                   <xsl:otherwise>markerOpen</xsl:otherwise>
               </xsl:choose>
           </xsl:attribute>
           <xsl:choose>
               <xsl:when test="@isComment = 'true'"><xsl:value-of select="\$markerComment" /></xsl:when>
               <xsl:when test="@type = 'link' and not(contains(@url,'.opml') or contains(@url,'.OPML'))"><xsl:value-of select="\$markerLink" /></xsl:when>
               <xsl:otherwise><xsl:value-of select="\$markerNormal" /></xsl:otherwise>
           </xsl:choose>
       </span>
       <span class="outlineText" style="margin-top:10px;">
           <xsl:value-of select="@text" disable-output-escaping="yes" />
       </span>
       <xsl:apply-templates />
  </div>
</xsl:template>
</xsl:stylesheet>
XSLTSTRING;

    libxml_use_internal_errors(true);
    $xslt = new XSLTProcessor();
    $xslt->importStylesheet(new SimpleXMLElement($xslt_string));
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$x) {
        libxml_clear_errors();
        loggit(2, "Parsing error when checking for opml content.");
        return ("");
    }
    $xml = $xslt->transformToXml($x);
    libxml_clear_errors();

    return ($xml);
}


//Recursive function for parsing an entire outline structure into html format
function buildHtmlFromOpmlRecursive($x = NULL, &$html, $indent = 0, $line = 0, $expansionState = array(), $expand = 1, $expanded = FALSE, &$parents, &$extrahtml, $menuexists = 0, &$extrahead)
{

    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    foreach ($x->children() as $child) {
        $text = (string)$child->attributes()->text;
        $name = (string)$child->attributes()->name;
        $link = (string)$child->attributes()->url;
        $type = strtolower((string)$child->attributes()->type);
        $attr = (string)$child->attributes();
        $oldindent = 0;

        //Set up class strings for different conditions
        $classes = "outline";
        if (!empty($type) && $type != "outline") {
            $classes .= " $type";
        }

        //Push the current type onto the stack
        if (($type == "tabs" || $type == "html" || $type == "document" || $type == "menu" || $type == "presentation") && end(array_values($parents)) != "tabs") {
            array_push($parents, $type);
        }

        //If no expansionState value matches the current visible node count then add a collapsed class
        $exco = "";
        if (!in_array($expand, $expansionState)) {
            $exco .= " collapsed";
        }

        //If this is an outline node, open a tag for it
        if ((string)$child->getName() == "outline") {
            if ($type == "link") {
                $nodetext = "<a href=\"$link\" target=\"_blank\">" . (string)$child->attributes()->text . "</a>";
            } else {
                $nodetext = (string)$child->attributes()->text;
            }
            if ($type == "code") {
                $nodetext = "<pre><code>" . (string)$child->attributes()->text . "</code></pre>";
            }
            if (empty($nodetext)) {
                $nodetext = "&nbsp;";
            }

            //Check for aspects of the outline node that might need more classes added for styling
            if (stripos($nodetext, "<a") !== FALSE) {
                $classes .= " wanchor";
            }
            if (stripos($nodetext, "<img") !== FALSE) {
                $classes .= " wimg";
            }

            //Set the variable for holding the next content under certain conditions like tabs
            if (in_array('tab', $parents)) {
                $htmlcontent =& $extrahtml;
            } else {
                $htmlcontent =& $html;
            }

            //Set an expanded class on outline nodes that match the expansionState counter
            $parent = end(array_values($parents));
            if ($type == "menu" && $menuexists == 0) {
                $htmlcontent .= "<div class=\"navbar navbar-fixed-top navbar-inverse\" role=\"navigation\">\n<div class=\"container\">\n<div class=\"navbar-header\">\n<button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\"#navbar-collapse-1\">\n<span class=\"sr-only\">Toggle navigation</span>\n<span class=\"icon-bar\"></span>\n<span class=\"icon-bar\"></span>\n<span class=\"icon-bar\"></span>\n</button>\n<a class=\"navbar-brand\" href='#'>$nodetext</a>\n</div>\n<div class=\"collapse navbar-collapse\" id=\"navbar-collapse-1\"><ul class=\"nav navbar-nav\">\n";
                $menuexists++;
            } else
                if ($type == "collaborate") {
                    $colltime = time();
                    $extrahead .= "<script>var TogetherJSConfig_findRoom = \"$colltime\";var TogetherJSConfig_inviteFromRoom = true; var TogetherJSConfig_suppressJoinConfirmation = true;</script><script id='togetherJS' src=\"//togetherjs.com/togetherjs-min.js\"></script>";
                } else
                    if ($type == "presentation") {
                        //Bring in the reveal.js style
                        $fh = fopen("$confroot/$templates/$cg_editor_presentation_style_filename", "r");
                        $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_editor_presentation_style_filename"));
                        fclose($fh);
                        $extrahead .= "\n      <style>" . $rftemplate . "</style>";
                        //Now the script
                        $fh = fopen("$confroot/$templates/$cg_editor_presentation_js_filename", "r");
                        $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_editor_presentation_js_filename"));
                        fclose($fh);
                        $extrahead .= "\n      <script id='revealJS'>" . $rftemplate . "</script>";

                        //Make collaboration track clicks
                        $extrahead .= "<script>var TogetherJSConfig_cloneClicks = true;</script>";

                        //Begin the slide sections
                        $htmlcontent .= "<section>$nodetext</section>";
                    } else
                        if ($type == "tabs") {
                            $html .= "\n" . str_repeat('    ', $indent + 1) . "<ul class=\"nav nav-tabs\" id=\"myTab\">";
                            $extrahtml .= "<div class=\"tab-content\">\n";
                        } else
                            if ($parent == "slide" || $type == "slide" || in_array('slide', $parents)) {
                                if (isset($child->outline)) {
                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<section>$nodetext</section><section>\n";
                                } else {
                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<section>$nodetext</section>\n";
                                }
                            } else
                                if ($parent == "presentation") {
                                    array_push($parents, 'slide');
                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<section>$nodetext</section>\n";
                                } else
                                    if ($parent == "tabs") {
                                        array_push($parents, 'tab');
                                        $tabid = 'tab' . stripText((string)$child->attributes()->text);
                                        $html .= "\n" . str_repeat('    ', $indent + 1) . "<li><a href=\"#$tabid\" data-toggle=\"tab\">" . strip_tags($nodetext) . "</a></li>";
                                        $extrahtml .= "<div class=\"tab-pane\" id=\"$tabid\">\n";
                                    } else
                                        if (in_array('menu', $parents)) {
                                            if (stripos($nodetext, "navatar") !== FALSE) {
                                                $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "</ul><ul class=\"nav navbar-nav pull-right\"><li>$nodetext</li></ul><ul class=\"nav navbar-nav\">";
                                            } else {
                                                $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<li>$nodetext</li>";
                                            }
                                        } else
                                            if (in_array('html', $parents)) {
                                                $htmlcontent .= str_repeat('    ', $indent) . "$nodetext\n";
                                            } else
                                                if ($type == 'html') {
                                                    $oldindent = $indent;
                                                    $indent = 0;
                                                    $htmlcontent .= str_repeat('    ', $indent) . "$nodetext\n";
                                                } else {
                                                    if (isset($child->outline)) {
                                                        $expandible = "<li class=\"owedge$exco\"><span>$nodetext</span>";
                                                    } else {
                                                        $expandible = "";
                                                        $expandible = "<li class=\"ou $classes\">$nodetext";
                                                        $exco = "";
                                                    }
                                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<ul class=\"$classes\">$expandible";
                                                }
        }

        //Adjust the expansion state tracking
        $lb = $line + 1;
        $ne = $expand;
        $ex = FALSE;
        if (in_array($expand, $expansionState)) {
            $ex = TRUE;
        }
        if ($expanded || $ex) {
            $ne = $expand + 1;
        }

        //Make the recursion call for the next set of nodes
        list($line, $expand) = buildHtmlFromOpmlRecursive($child, $html, $indent + 1, $line + 1, $expansionState, $ne, $ex, $parents, $extrahtml, $menuexists, $extrahead);


        //If this is an outline node, close the open tag.  We take care to keep the html looking good, so don't add spaces
        //to the end of single line node tags
        $indention = $indent + 1;
        if ($lb == $line) {
            $indention = 0;
        }
        if ((string)$child->getName() == "outline") {
            if ($type == "menu" && $menuexists < 2) {
                $htmlcontent .= str_repeat('    ', $indention) . "</ul>\n</div>\n</div>\n</div>";
                $menuexists++;
            } else
                if ($type == "collaborate") {
                    $htmlcontent .= "\n";
                } else
                    if ($type == "presentation") {
                        $htmlcontent .= "";
                    } else
                        if ($type == "tabs") {
                            $html .= str_repeat('    ', $indention) . "</ul>\n";
                            $extrahtml .= str_repeat('    ', $indention) . "</div>\n";
                        } else
                            if ($parent == "slide" || $type == "slide" || in_array('slide', $parents)) {
                                if (isset($child->outline)) {
                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "</section>\n";
                                } else {
                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "\n";
                                }
                            } else
                                if ($parent == "presentation") {
                                    array_pop($parents);
                                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "\n";
                                } else
                                    if ($parent == "tabs") {
                                        array_pop($parents);
                                        $html .= str_repeat('    ', $indention) . "\n";
                                        $extrahtml .= str_repeat('    ', $indention) . "</div>\n";
                                    } else
                                        if (in_array('menu', $parents) && $type != "html") {
                                            $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "\n";
                                        } else
                                            if (in_array('html', $parents)) {
                                                $htmlcontent .= str_repeat('    ', $indent) . "";
                                            } else
                                                if ($type == 'html') {
                                                    $htmlcontent .= str_repeat('    ', $indent) . "";
                                                    $indent = $oldindent;
                                                } else {
                                                    $htmlcontent .= str_repeat('    ', $indention) . "</li></ul>\n";
                                                }

        }

        if ($indent == 0 && $ex == FALSE) {
            $expand++;
        }
        if (($type == "tabs" || $type == "html" || $type == "document" || $type == "menu" || $type == "presentation") && end(array_values($parents)) != "tabs") {
            array_pop($parents);
        }
    }


    return (array($line, $expand));
}


//Convert an opml document to html with processing
function process_opml_to_html($content = NULL, $title = "", $uid = NULL, $dodisqus = FALSE, $opmlurl = "", $rendertitle = TRUE, $htmlurl = "")
{
    //Check params
    if ($content == NULL) {
        loggit(2, "The opml content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$includes/posts.php";

    $extrahead = "";
    $extrabody = "";
    $prefs = get_user_prefs($uid);
    $analyticscode = $prefs['analyticscode'];
    $disqus = "";
    if ($dodisqus && !empty($prefs['disqus_shortname'])) {
        $disqus = $cg_disqus_embed;
        $disqus = str_replace('[SHORTNAME]', $prefs['disqus_shortname'], $disqus);
    }

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Get rss opml for the microblog
    if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
        $microblogrss = get_s3_url($uid, NULL, get_microblog_feed_filename($uid));
    }

    //Was there an opml url given
    $linktoopml = "";
    if (!empty($opmlurl)) {
        loggit(3, "DEBUG: Opml url is: [$opmlurl].");
        $linktoopml = "<a class=\"opmlicon\" href=\"$opmlurl\" title=\"Link to the opml for this document.\">&nbsp;</a>";
    }

    //Get byline if a username was given or if not, look for an author element in the opml
    if (!empty($uid)) {
        $byline = '<br><small>by ' . get_user_name_from_uid($uid) . '</small>';
    } else {
        $authorname = (string)$x->head->ownerName;
        if (!empty($authorname)) {
            $byline = '<br><small>by ' . $authorname . '</small>';
        }
    }

    //Roll through all of the outline nodes
    $nodes = $x->xpath('//outline');
    if (empty($nodes)) {
        loggit(3, "This opml document is blank.");
        return (-2);
    }

    //Get the expansion state
    $expansionState = explode(',', (string)$x->head->expansionState);
    $serialES = (string)$x->head->expansionState;
    $parents = array();

    //Parse the outline
    buildHtmlFromOpmlRecursive($x->body, $body, 0, 1, $expansionState, 1, FALSE, $parents, $extrabody, 0, $extrahead);

    //Put in some extra space if we don't have a title
    if (empty($title) || $rendertitle == FALSE) {
        $bodypadding = "120px";
    } else {
        $bodypadding = "60px";
    }

    //Generate a qr code for this file
    $qrcode = create_s3_qrcode_from_url($uid, $htmlurl);
    $qrimg = "<img class='pull-right qrcode' title ='This code always links to this page url.' src='" . $qrcode . "' />";
    if (!empty($qrcode)) {
        $qrline = "<div class='row'>$qrimg</div>";
    }

    //See what type of build out do we need?
    //Get the title
    if (!empty($title)) {
        $titleline = '<div class="page-header">' . $qrimg . '<h2>' . $title . $byline . '</h2></div>';
        $qrline = "";
    }
    $precontent = "";
    $container_start = "<div class='container'>";
    $container_stop = "</div>\n<div class='container text-right'><script>document.write(\"<small>Last Modified \" + document.lastModified + \" by <a href='http://freedomcontroller.com'>Freedom Controller</a></small>\")</script> $linktoopml<div class='ocomments'><div id='disqus_thread'></div></div></div>";
    $inlinestyle = <<<OPML2HTMLCSS
	<style type="text/css">
		body {
			font-family: 'Noto Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
			padding-top: $bodypadding;
			padding-bottom: 60px;
		}

        img.qrcode {
            margin:0;
            margin-top:-26px;
        }

		.navbar-nav > li > a {
			padding-top: 16px;
			padding-bottom: 14px;
		}

        .navbar > img.navatar {
            float: right;
            margin:0;
            height:51px;
            width:51px;
        }

		.container {
			max-width: 900px;
		}

		.page-header {
			margin-bottom: 40px;
		}

		/* outliner */

		ul.outline {
			list-style-type: none;
			padding: 0;
			margin: 10px 0;
		}

        body > div.container > ul.outline {
            margin: 30px 0;
        }

		ul.outline li {
			font-size: 16px;
			line-height: 24px;
		}

		ul.outline li.owedge {
			background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAANCAYAAABy6+R8AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3QwJFjoSFDd3KQAAAJhJREFUKM+9kCESgzAQRV+DquQCBVNdPBfocAqOwDV6DG4AJromnuqaMj1AwCExm5l0GiYovtq/+//+2YWjcAJIy+oDZDv0ozU6V0LqnSE1gAKwRj+BPmLoRYfymg0wbxhmmQOQuGL5vqfz5boA94DpYY3ufh7hIy2rAbh5rZc1uvA1KrC1ifB/kxzbCm3d8bEkt30MpRyLFdXdKFaW+5X5AAAAAElFTkSuQmCC) 0px 7px no-repeat;
			padding-left: 20px;
		}

		ul.outline li.owedge.collapsed {
			background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAANCAYAAABy6+R8AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3QwJFjkvZ3Jo+wAAAIdJREFUKM9jYCAXCFp7PRC09nIgVj0TlJZnYGDYL2jttUHQ2kuBWE0w4M/AwHBB0NqrAZ8mRqjz/mORu8jAwFDw/ui2A6RogoGFUM0fcDkPG4hnYGC4gM9PuGwyQBZgwaMYp5+wafrIwMDQ8P7otgm4TEPXtBFq+gN87oVpesjAwJCAzSlUAwDgjSci5nmpKgAAAABJRU5ErkJggg==) 0px 6px no-repeat;
			padding-left: 20px;
		}

		ul.outline li span {
			cursor: pointer;
		}

		ul.outline img {
		    margin:10px;
		}

		.collapsed li {
			display: none;
		}

        div.ocomments iframe {  margin-top:80px;  }
        .tab-content {  min-height:300px;  }

        a.opmlicon {  display:inline-block; background:url(data:image/png;base64,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) 0 0 no-repeat; height:16px; width:16px;  }
        a.opmlicon:hover { text-decoration:none; }
	</style>

OPML2HTMLCSS;
    if (stripos($extrahead, 'revealJS') !== FALSE) {
        if (!empty($title)) {
            $titleline = '<section><h2>' . $title . $byline . '</h2></section>';
        }
        $container_start = "<div class=\"reveal\"><div class=\"slides\">";
        $container_stop = "</div></div>";
        $inlinestyle = "";
    }

    if (strpos($extrahead, 'togetherJS') !== FALSE) {
        $precontent .= "<div id=\"togetherjs-div\"><button id=\"start-togetherjs\" type=\"button\" onclick=\"TogetherJS(this); return false\" data-end-togetherjs-html=\"End TogetherJS\">Collaborate!</button></div>\n";
        $inlinestyle .= "<style>div#togetherjs-div { z-index:9999; position:absolute; top:10px; right:10px; float:right; }</style>";
    }

    //Was title rendering set to false?
    if ($rendertitle == FALSE) {
        $titleline = "";
        $byline = "";
    }

    //Render into html
    $html = <<<OPML2HTML1
<!DOCTYPE html>
<html lang="en">
<head>
    <title>$title</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="alternate" type="text/xml" title="OPML" href="$opmlurl" />
    <link rel="alternate" type="application/rss+xml" title="RSS" href="$microblogrss" />
    <link href='//fonts.googleapis.com/css?family=Noto+Sans:400,700' rel='stylesheet' type='text/css' />
    <link href='//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css' rel='stylesheet' type='text/css' />
    $inlinestyle

	<!--[if lt IE 9]>
		<script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="//oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
	<![endif]-->

	$extrahead
</head>

  <body>
    $precontent
    $container_start
    $titleline

    $body
    $extrabody
    $qrline
    $disqus
    $container_stop

	<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	<script>
        $(document).ready(function() {
            //Dont propagate link click events up to the wedge text
            $('li.owedge a').click(function(event) {
                event.stopPropagation();
            });
            //Toggle wedges
            $('li.owedge span').click(function() {
                $(this).parent().toggleClass('collapsed');
            });
            //If there are tabs then show the first one
            if( $('#myTab').length > 0 ) {
                $('#myTab a:first').tab('show');
            }
            //If this is a presentation start reveal
            if( $('div.reveal').length > 0 ) {
                Reveal.initialize({ controls: true, progress: true, history: true, center: true, theme: 'default', transition: 'default' });
            }
        });
	</script>
    $analyticscode
  </body>
</html>
OPML2HTML1;

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


//Return a list of files recently edited by this user in the editor
function get_recent_files($uid = NULL, $max = NULL, $type = 0)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Type check?
    $notype = FALSE;
    if ($type == -1) {
        $notype = TRUE;
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    if ($notype) {
        $sqltxt = "SELECT title, url, time, disqus, wysiwyg, watched, locked, type FROM $table_recentfiles WHERE userid=?";
    } else {
        $sqltxt = "SELECT title, url, time, disqus, wysiwyg, watched, locked, type FROM $table_recentfiles WHERE userid=? AND type=?";
    }

    $sqltxt .= " ORDER BY time DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT $default_max_list";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    if ($notype) {
        $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql->bind_param("sd", $uid, $type) or loggit(2, "MySql error: " . $dbh->error);
    }
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No files returned for: [$uid] with the given criteria.");
        return (array());
    }

    $sql->bind_result($ftitle, $furl, $ftime, $fdisqus, $fwysiwyg, $fwatched, $flocked, $ftype) or loggit(2, "MySql error: " . $dbh->error);

    $files = array();
    $count = 0;
    while ($sql->fetch()) {
        $files[$count] = array('title' => $ftitle,
            'url' => $furl,
            'time' => $ftime,
            'disqus' => $fdisqus,
            'wysiwyg' => $fwysiwyg,
            'watched' => $fwatched,
            'locked' => $flocked,
            'type' => $ftype
        );
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] files for user: [$uid]");
    return ($files);
}


//Return a list of files recently edited by this user in the editor
function get_watched_files($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    $sqltxt = "SELECT userid, url FROM $table_recentfiles WHERE watched=1";

    $sqltxt .= " ORDER BY time DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT $default_max_list";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "There are no watched files in the system.");
        return (array());
    }

    $sql->bind_result($fuserid, $furl) or loggit(2, "MySql error: " . $dbh->error);

    $files = array();
    $count = 0;
    while ($sql->fetch()) {
        $files[$count] = array(
            'uid' => $fuserid,
            'url' => $furl
        );
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] watched files.");
    return ($files);
}


//Return a list of files recently edited by this user in the editor
function get_recent_file_by_url($uid = NULL, $url = NULL, $blob = FALSE)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }
    if (empty($url)) {
        loggit(2, "The url given is corrupt or blank: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    if ($blob) {
        $sqltxt = "SELECT id, title, url, time, disqus, wysiwyg, watched, type, locked, ipfshash, outline FROM $table_recentfiles WHERE userid=? AND url=?";
    } else {
        $sqltxt = "SELECT id, title, url, time, disqus, wysiwyg, watched, type, locked, ipfshash FROM $table_recentfiles WHERE userid=? AND url=?";
    }


    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No files returned for: [$uid | $url] with the given criteria.");
        return (array());
    }

    if ($blob) {
        $sql->bind_result($fid, $ftitle, $furl, $ftime, $fdisqus, $fwysiwyg, $fwatched, $ftype, $flocked, $ipfshash, $foutline) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql->bind_result($fid, $ftitle, $furl, $ftime, $fdisqus, $fwysiwyg, $fwatched, $ftype, $flocked, $ipfshash) or loggit(2, "MySql error: " . $dbh->error);
    }


    $files = array();
    $count = 0;
    while ($sql->fetch()) {
        $files[$count] = array('id' => $fid,
            'title' => $ftitle,
            'url' => $furl,
            'time' => $ftime,
            'disqus' => $fdisqus,
            'wysiwyg' => $fwysiwyg,
            'watched' => $fwatched,
            'type' => $ftype,
            'locked' => $flocked,
            'ipfshash' => $ipfshash
        );
        if ($blob) {
            $files[$count]['content'] = $foutline;
        }
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] files for user: [$uid]");
    return ($files);
}


//Update a file into the recent files table
function update_recent_file($uid = NULL, $url = NULL, $title = NULL, $outline = "", $type = 0, $oldurl = "", $disqus = FALSE, $wysiwyg = FALSE, $watched = FALSE, $articleid = NULL, $locked = FALSE, $ipfshash = "")
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }
    if (empty($title)) {
        $title = "Edited at - " . time();
        loggit(1, "No title for file, so generating one: [$title]");
    }
    if (!$disqus) {
        $disqus = 0;
    } else {
        $disqus = 1;
    }
    if (!$wysiwyg) {
        $wysiwyg = 0;
    } else {
        $wysiwyg = 1;
    }
    if (!$watched) {
        $watched = 0;
    } else {
        $watched = 1;
    }
    if (!$locked) {
        $locked = 0;
    } else {
        $locked = 1;
    }
    if(empty($ipfshash)) {
        $ipfshash = "";
    }


    //Timestamp
    $time = time();

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Insert recent file entry
    if (empty($oldurl)) {
        $stmt = "INSERT INTO $table_recentfiles (userid, url, title, outline, time, disqus, wysiwyg, watched, articleid, locked, type, ipfshash)
                                         VALUES (     ?,   ?,     ?,       ?,    ?,      ?,       ?,       ?,         ?,      ?,    ?,        ?)
                 ON DUPLICATE KEY UPDATE title=?, time=?, outline=?, disqus=?, wysiwyg=?, watched=?, articleid=?, locked=?, ipfshash=?";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("ssssddddsddssdsdddsds", $uid, $url, $title, $outline, $time, $disqus, $wysiwyg, $watched, $articleid, $locked, $type, $ipfshash, $title, $time, $outline, $disqus, $wysiwyg, $watched, $articleid, $locked, $ipfshash) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $stmt = "INSERT INTO $table_recentfiles (userid, url, title, outline, time, disqus, wysiwyg, watched, articleid, locked, type, ipfshash)
                                         VALUES (     ?,   ?,     ?,       ?,    ?,      ?,       ?,       ?,         ?,      ?,    ?,        ?)
                 ON DUPLICATE KEY UPDATE title=?, time=?, outline=?, url=?, disqus=?, wysiwyg=?, watched=?, articleid=?, locked=?, ipfshash=?";
        $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
        $sql->bind_param("ssssddddsddssdssdddsds", $uid, $oldurl, $title, $outline, $time, $disqus, $wysiwyg, $watched, $articleid, $locked, $type, $ipfshash, $title, $time, $outline, $url, $disqus, $wysiwyg, $watched, $articleid, $locked, $ipfshash) or loggit(2, "MySql error: " . $dbh->error);
        loggit(3, "User: [$uid] changed old url: [$oldurl] to new url: [$url].");
    }
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $rid = $sql->insert_id;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "User: [$uid] edited a file: [$title] at: [$url]. Article: [$articleid].");
    return ($rid);
}


//Search for editor files that match query
function search_editor_files($uid = NULL, $query = NULL, $max = NULL)
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

    //Build the sql bindings
    $colnames = array(
        "$table_recentfiles.url",
        "$table_recentfiles.title",
        "$table_recentfiles.outline"
    );
    $qsql = build_search_sql($query, $colnames);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    $sqltxt = "SELECT url, title, outline
	           FROM $table_recentfiles
	           WHERE userid=?
    ";

    //Append search criteria
    $sqltxt .= $qsql['text'];

    //Limit
    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);

    //Adjust bindings
    $newsetup = "s" . $qsql['bind'][0];
    $qsql['bind'][0] = &$newsetup;
    array_splice($qsql['bind'], 1, 0, array(&$uid));

    $ref = new ReflectionClass('mysqli_stmt');
    $method = $ref->getMethod("bind_param");
    $method->invokeArgs($sql, $qsql['bind']);

    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No editor files returned for user: [$uid] with given criteria.");
        return (FALSE);
    }

    $sql->bind_result($furl, $ftitle, $foutline) or loggit(2, "MySql error: " . $dbh->error);

    $files = array();
    $count = 0;
    while ($sql->fetch()) {
        $files[$count] = array('url' => $furl, 'title' => $ftitle, 'outline' => $foutline);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(3, "Returning: [$count] editor files for user: [$uid]");
    return ($files);
}


//Update a file into the recent files table
function add_watched_url($rid = NULL, $url = NULL, $lastmodified = "", $content = "")
{
    //Check parameters
    if (empty($rid)) {
        loggit(2, "The recent file id is blank or corrupt: [$rid]");
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

    //Insert watched file entry
    $stmt = "INSERT INTO $table_watched_urls (rid, url, lastmodified, content)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE rid=?, lastmodified=?, content=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("dsssdss", $rid, $url, $lastmodified, $content, $rid, $lastmodified, $content) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Added url: [$url] from recent file: [$rid] to watched url table.");
    return (TRUE);
}


//Update content of a watched url
function update_watched_url_content_by_url($url = NULL, $lastmodified = "", $content = "")
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Insert watched file entry
    $stmt = "UPDATE $table_watched_urls SET lastmodified=?,content=? WHERE url=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $lastmodified, $content, $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Updated content for url: [$url] with data modified at: [$lastmodified].");
    return (TRUE);
}


//Return a list of files recently edited by this user in the editor
function get_watched_urls($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    $sqltxt = "SELECT urls.rid, urls.url, urls.lastmodified, files.userid
               FROM $table_watched_urls as urls
               JOIN $table_recentfiles as files ON urls.rid = files.id";

    $sqltxt .= " ORDER BY time DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT $default_max_list";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "There are no watched files in the system.");
        return (array());
    }

    $sql->bind_result($frid, $furl, $flastmod, $fuid) or loggit(2, "MySql error: " . $dbh->error);

    $files = array();
    $count = 0;
    while ($sql->fetch()) {
        $files[$count] = array(
            'rid' => $frid,
            'url' => $furl,
            'lastmodified' => $flastmod,
            'uid' => $fuid
        );
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] watched files.");
    return ($files);
}


//Return information about a watched url
function get_watched_url_by_url($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    $sqltxt = "SELECT urls.rid, urls.url, urls.lastmodified, files.userid, urls.content
               FROM $table_watched_urls as urls
               JOIN $table_recentfiles as files ON urls.rid = files.id
               WHERE urls.url = ?";

    $sqltxt .= " ORDER BY time DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT $default_max_list";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "Url: [$url] is not in the watch list.");
        return (array());
    }

    $sql->bind_result($frid, $furl, $flastmod, $fuid, $fcontent) or loggit(2, "MySql error: " . $dbh->error);

    $files = array();
    $count = 0;
    while ($sql->fetch()) {
        $files[$count] = array(
            'rid' => $frid,
            'url' => $furl,
            'lastmodified' => $flastmod,
            'uid' => $fuid,
            'content' => $fcontent
        );
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning info about url: [$url]");
    return ($files[0]);
}


//Return information about a watched url
function get_watched_url_users_by_url($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the query
    $sqltxt = "SELECT files.userid
               FROM $table_watched_urls as urls
               JOIN $table_recentfiles as files ON urls.rid = files.id
               WHERE urls.url = ? GROUP BY files.userid";

    $sqltxt .= " ORDER BY files.userid ASC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT $default_max_list";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any files for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "Url: [$url] is not in the watch list.");
        return (array());
    }

    $sql->bind_result($fuid) or loggit(2, "MySql error: " . $dbh->error);

    $users = array();
    $count = 0;
    while ($sql->fetch()) {
        $users[$count] = $fuid;
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning list of watching users for url: [$url]");
    return ($users);
}


//Retrieve an array of info about an outline
function remove_watched_urls_by_file_id($id = NULL)
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The file id given is corrupt or blank: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("DELETE FROM $table_watched_urls WHERE rid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //loggit(3,"Returning info for outline: [$id]");
    return (TRUE);
}


//Return the diff between two opml files
function diff_opml($opml1 = "", $opml2 = "")
{
    //Check parameters
    if (empty($opml1)) {
        loggit(2, "The opml 1 input is blank or corrupt: [$opml1]");
        return (FALSE);
    }
    if (empty($opml2)) {
        loggit(2, "The opml 2 input is blank or corrupt: [$opml2]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    $diff = diff(explode("\n", $opml1), explode("\n", $opml2));
    echo print_r($diff, TRUE) . "\n";
    $changed = "";

    $bodygo = FALSE;
    foreach ($diff as $line) {
        if (is_string($line) && trim($line) == "</body>") {
            $bodygo = FALSE;
        }

        if ($bodygo && is_array($line) && isset($line['i'])) {
            foreach ($line['i'] as $subline) {
                if (!empty($subline)) {
                    $changed .= trim($subline) . "\n";
                }
            }
        }

        if (is_string($line) && trim($line) == "<body>") {
            $bodygo = TRUE;
        }
    }

    echo print_r("DEBUG [changed]: " . $changed, TRUE) . "\n";
    return ($changed);
}


//Convert opml to a php multidimensional array
function convert_opml_to_array($opml = "")
{
    if (empty($opml)) {
        return (array());
    }

    $xml = simplexml_load_string($opml);
    $json = json_encode($xml);
    $array = json_decode($json, TRUE);

    return ($array);
}


//Convert opml to a json object
function convert_opml_to_json($opml = "")
{

    $xml = simplexml_load_string($opml);
    $json = json_encode($xml);

    return ($json);
}


//Convert opml to myword.io format json
function convert_opml_to_myword($content = NULL, $max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Check params
    if (empty($content) || !is_outline($content)) {
        loggit(2, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Array for building
    $converted = array();
    $converted['title'] = "";
    $converted['authorname'] = "";
    $converted['when'] = "";
    $converted['img'] = "";
    $converted['subs'] = "";

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Meta-data
    if (isset($x->head->title)) {
        $converted['title'] = (string)$x->head->title;
    }
    if (isset($x->head->ownerName)) {
        $converted['authorname'] = (string)$x->head->ownerName;
    }
    if (isset($x->head->dateModified)) {
        $converted['when'] = (string)$x->head->dateModified;
    }
    $converted['img'] = "";

    //Grab an image node
    $nodes = $x->xpath('//outline[@url and @type="image"]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['img'] = (string)$entry->attributes()->url;
            break;
        }
    }

    //Grab outline nodes
    $nodes = $x->xpath('//outline[not(ancestor-or-self::outline[@type="menu" or @type="collaborate"])]');
    if (empty($nodes)) {
        loggit(2, "This outline content didn't have any outline nodes.");
        return (-2);
    }

    //Run through each node and get the text into the array
    $count = 0;
    foreach ($nodes as $entry) {
        if ((string)$entry->attributes()->type == "link") {
            if (empty($entry->attributes()->text)) {
                $converted['subs'][] = '<a href="' . (string)$entry->attributes()->url . '">' . (string)$entry->attributes()->url . '</a>';
            } else {
                $converted['subs'][] = '<a href="' . (string)$entry->attributes()->url . '">' . (string)$entry->attributes()->text . '</a>';
            }
            $count++;
        } else
            if (empty($entry->attributes()->type)) {
                $converted['subs'][] = (string)$entry->attributes()->text;
                $count++;
            }
    }

    //loggit(3, "DEBUG: ".print_r($json, TRUE));

    if ($count == 0) {
        loggit(2, "There were no outline nodes in this outline.");
        return (-2);
    }

    //Log and leave
    loggit(3, "Got [$count] include nodes from the outline.");
    return (json_encode($converted));
}


//Convert a news feed to opml structure
function convert_feed_to_opml($content = NULL, $max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Check params
    if (empty($content) || !is_feed($content)) {
        loggit(2, "The feed content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Array for building
    $converted = array();
    $converted['title'] = "";
    $converted['authorname'] = "";
    $converted['when'] = "";
    $converted['img'] = "";
    $converted['subs'] = "";

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Meta-data
    if (isset($x->head->title)) {
        $converted['title'] = (string)$x->head->title;
    }
    if (isset($x->head->ownerName)) {
        $converted['authorname'] = (string)$x->head->ownerName;
    }
    if (isset($x->head->dateModified)) {
        $converted['when'] = (string)$x->head->dateModified;
    }
    $converted['img'] = "";

    //Grab an image node
    $nodes = $x->xpath('//outline[@url and @type="image"]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['img'] = (string)$entry->attributes()->url;
            break;
        }
    }

    //Grab outline nodes
    $nodes = $x->xpath('//outline[not(ancestor-or-self::outline[@type="menu" or @type="collaborate"])]');
    if (empty($nodes)) {
        loggit(2, "This outline content didn't have any outline nodes.");
        return (-2);
    }

    //Run through each node and get the text into the array
    $count = 0;
    foreach ($nodes as $entry) {
        if ((string)$entry->attributes()->type == "link") {
            if (empty($entry->attributes()->text)) {
                $converted['subs'][] = '<a href="' . (string)$entry->attributes()->url . '">' . (string)$entry->attributes()->url . '</a>';
            } else {
                $converted['subs'][] = '<a href="' . (string)$entry->attributes()->url . '">' . (string)$entry->attributes()->text . '</a>';
            }
            $count++;
        } else
            if (empty($entry->attributes()->type)) {
                $converted['subs'][] = (string)$entry->attributes()->text;
                $count++;
            }
    }

    //loggit(3, "DEBUG: ".print_r($json, TRUE));

    if ($count == 0) {
        loggit(2, "There were no outline nodes in this outline.");
        return (-2);
    }

    //Log and leave
    loggit(3, "Got [$count] include nodes from the outline.");
    return (json_encode($converted));
}


//Convert opml to rss format
function convert_opml_to_rss($content = NULL, $uid = NULL, $max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    include "$confroot/libraries/FreePod/Podcast.php";

    //Check params
    if (empty($content) || !is_outline($content)) {
        loggit(3, "The outline content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Array for building
    $converted = array();
    $converted['title'] = "";
    $converted['description'] = "";
    $converted['link'] = "";
    $converted['authorname'] = "";
    $converted['when'] = "";
    $converted['pubdate'] = "";
    $converted['img'] = "";
    $converted['items'] = "";
    $converted['explicit'] = FALSE;
    $converted['keywords'] = array();
    $converted['categories'] = array();

    //Parse it
    libxml_use_internal_errors(true);
    $x = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    libxml_clear_errors();

    //Meta-data
    if (isset($x->head->title)) {
        $converted['title'] = (string)$x->head->title;
    }
    if (isset($x->head->ownerName)) {
        $converted['authorname'] = (string)$x->head->ownerName;
    }
    if (isset($x->head->dateModified)) {
        $converted['when'] = (string)$x->head->dateModified;
    }

    //Grab the first description node as the feed description
    $nodes = $x->xpath('//outline[@type="description" and not(ancestor::outline[@type="item"])]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['description'] = (string)$entry->attributes()->text;
            loggit(3, "RSS DEBUG: description");
            break;
        }
    }

    //Grab the first link node as the feed link
    $nodes = $x->xpath('//outline[@type="link"]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['link'] = (string)$entry->attributes()->url;
            loggit(3, "RSS DEBUG: link");
            break;
        }
    }

    //Grab the first image node as the feed image
    $nodes = $x->xpath('//outline[@url and @type="image"]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['img'] = (string)$entry->attributes()->url;
            break;
        }
    }

    //Grab the channel author node if there is one
    $nodes = $x->xpath('//outline[@type="author" and not(ancestor::outline[@type="item"])]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['author'] = (string)$entry->attributes()->text;
            break;
        }
    }

    //Grab the channel pubdate node if there is one
    $nodes = $x->xpath('//outline[@type="pubdate" and not(ancestor::outline[@type="item"])]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['pubdate'] = (string)$entry->attributes()->text;
            break;
        }
    }

    //Grab the explicit tag if there is one
    $nodes = $x->xpath('//outline[@type="explicit"]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $explicit = (string)$entry->attributes()->text;
            if(!empty($explicit) && stripos($explicit, "yes") !== FALSE) {
                $converted['explicit'] = TRUE;
            }
            break;
        }
    }

    //Grab the keywords
    $nodes = $x->xpath('//outline[@type="keyword" and not(ancestor::outline[@type="item"])]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['keywords'][] = (string)$entry->attributes()->text;
        }
    }

    //Grab the categories
    $nodes = $x->xpath('//outline[@type="category" and not(ancestor::outline[@type="item"])]');
    if (!empty($nodes)) {
        foreach ($nodes as $entry) {
            $converted['categories'][] = (string)$entry->attributes()->text;
        }
    }

    //Grab outline nodes
    $nodes = $x->xpath('//outline[@type="item"]');
    if (empty($nodes)) {
        loggit(3, "This outline content didn't have any rss item nodes.");
        return (-2);
    }

    //Start the rss feed structure with what we have
    $podcast = new Podcast($converted['title'], $converted['description'], $converted['link']);
    $podcast->xml(TRUE);

    //Add a webmaster and managingeditor if we have a uid
    if(!empty($uid)) {
        $email = get_email_from_uid($uid);
        if(!empty($email)) {
            $podcast->webMaster = $email;
            $podcast->managingEditor = $email;
            $podcast->itunes_owner['email'] = $email;
        }
        $name = get_user_name_from_uid($uid);
        if(!empty($name)) {
            $podcast->itunes_author = $name;
            $podcast->itunes_owner['name'] = $name;
        }
        if(!empty($name) && !empty($email)) {
            $podcast->webMaster = $email. " ($name)";
            $podcast->managingEditor = $email. " ($name)";
        }
    }

    //Author
    if(!empty($converted['author'])) {
        loggit(3, "Adding channel author: [".$converted['author']."]");
        $podcast->itunes_author = $converted['author'];
    }

    //pubDate
    if(!empty($converted['pubdate'])) {
        loggit(3, "Adding channel pubDate: [".$converted['pubdate']."]");
        $podcast->pubDate = $converted['pubdate'];
    }

    //Add the channel image
    if(!empty($converted['img'])) {
        loggit(3, "Adding channel image: [".$converted['img']."]");
        $podcast->itunes_image = $converted['img'];
    }

    //Add the explicit tag if it's true
    if($converted['explicit']) {
        loggit(3, "Channel explicit tag: [Yes]");
        $podcast->itunes_explicit = "Yes";
    }

    //Add the keywords
    $keywords = "";
    if(!empty($converted['keywords'])) {
        loggit(3, "Adding keywords: [".print_r($converted['keywords'], TRUE)."]");
        $podcast->itunes_keywords = $converted['keywords'];
    }

    //Add the categories
    if(!empty($converted['categories'])) {
        loggit(3, "Adding categories: [".print_r($converted['categories'], TRUE)."]");
        foreach( $converted['categories'] as $cat) {
            $podcast->addCategory($cat);
        }
    }

    //Run through each node and get the text into the array
    $count = 0;
    $pcount = 0;
    foreach ($nodes as $item) {
        $pcount++;

        $converted_urls = array();
        $converted_texts = array();
        $converted_nontypes = array();
        $converted_images = array();
        $converted_author = "";
        $converted_pubdate = "";
        $converted_enclosures = array();
        $converted_explicits = FALSE;
        $converted_keywords = array();
        $converted_desc = "";
        $converted_title = (string)$item->attributes()->text;
        $converted_guid = NULL;
        $converted_url = (string)$item->attributes()->url;
        $converted_created = (string)$item->attributes()->created;

        //Get all subnodes of the item and parse them for various content
        $item_parts = $item->xpath('.//outline');
        foreach ($item_parts as $part) {
            $converted_url = "";
            $converted_text = "";
            $converted_nontype = "";
            $converted_image = "";
            $converted_enclosure = array();
            $converted_explicit = FALSE;
            $converted_keyword = "";

            //Handle each link type
            if ((string)$part->attributes()->type == "link") {
                if (empty($part->attributes()->text)) {
                    $converted_text = (string)$part->attributes()->url;
                } else {
                    $converted_text = (string)$part->attributes()->text;
                }
                $converted_url = (string)$part->attributes()->url;
                if(stripos($converted_text, "http") === 0) {
                    $converted_url = $converted_text;
                }

            } else if ((string)$part->attributes()->type == "description") {
                $desc_parts = $part->xpath('.//outline');
                foreach ($desc_parts as $dpart) {
                    $converted_desc = $converted_desc . "<p>" . (string)$dpart->attributes()->text."</p>\n";
                }

            } else if ((string)$part->attributes()->type == "title") {
                $converted_title = (string)$part->attributes()->text;

            } else if ((string)$part->attributes()->type == "author") {
                $converted_author = (string)$part->attributes()->text;

            } else if ((string)$part->attributes()->type == "pubdate") {
                $converted_pubdate = (string)$part->attributes()->text;

            } else if ((string)$part->attributes()->type == "guid") {
                $converted_guid = trim((string)$part->attributes()->text);
                if(empty($converted_guid)) {
                    $converted_guid = random_gen(64);
                }
                loggit(3, "RSS: Guid value: " . $converted_guid);

            } else if ((string)$part->attributes()->type == "keyword") {
                $converted_keyword = (string)$part->attributes()->text;

            } else if ((string)$part->attributes()->type == "explicit") {
                //Grab the explicit tag if there is one
                $exp = (string)$part->attributes()->text;
                if(!empty($exp) && stripos($exp, "yes") !== FALSE) {
                    $converted_explicit = TRUE;
                }

            } else if ((string)$part->attributes()->type == "enclosure") {
                $converted_enclosure['txturl'] = (string)$part->attributes()->text;
                $converted_enclosure['url'] = (string)$part->attributes()->url;
                $converted_enclosure['type'] = (string)$part->attributes()->mimetype;
                $converted_enclosure['length'] = (string)$part->attributes()->length;
                if(stripos($converted_enclosure['txturl'], "http") === 0) {
                    $converted_enclosure['url'] = $converted_enclosure['txturl'];
                    $converted_enclosure['type'] = "";
                    $converted_enclosure['length'] = "";
                }

            } else if ((string)$part->attributes()->type == "image") {
                $converted_image = (string)$part->attributes()->url;
                $converted_imgtxt = (string)$part->attributes()->text;
                if(stripos($converted_imgtxt, "http") === 0) {
                    $converted_image = $converted_imgtxt;
                }

            } else {
                //Save non-typed nodes for use as description text if no description is found
                $converted_nontype = (string)$part->attributes()->text;
            }

            if (!empty($converted_url)) $converted_urls[] = $converted_url;
            if (!empty($converted_text)) $converted_texts[] = $converted_text;
            if (!empty($converted_image)) $converted_images[] = $converted_image;
            if (!empty($converted_enclosure)) $converted_enclosures[] = $converted_enclosure;
            if (!empty($converted_keyword)) $converted_keywords[] = $converted_keyword;
            if ($converted_explicit) $converted_explicits = TRUE;
            if (!empty($converted_nontype)) $converted_nontypes[] = $converted_nontype;
        }

        //Trim strings
        $converted_desc = trim($converted_desc);
        $converted_title = trim($converted_title);
        $converted_author = trim($converted_author);
        $converted_pubdate = trim($converted_pubdate);

        //Determine if a description was explicitely assigned as a "description" node. If
        //not, use any non-typed nodes found as the description body.  If those are blank
        //too, use a single blank space.
        //loggit(3, "RSS DESCRIPTION: ".print_r($converted_desc, TRUE));
        if(empty($converted_desc)) {
            loggit(3, "RSS: No description nodes found.");
            if(empty($converted_nontypes)) {
                if(empty($converted_title)) {
                    loggit(3, "RSS: Error. Both title and description are blank.");
                    return(-4);
                } else {
                    loggit(3, "RSS: No non-types found to use as description.");
                }
            } else {
                loggit(3, "RSS: Using non-types as description.");
                $converted_desc = "<p>".implode("</p><p>\n", $converted_nontypes)."</p>";
            }
        }

        //Now insert the item and it's attributes into the feed
        //loggit(3, "RSS: Adding the item [$converted_title | $converted_desc].");
        $pitem = $podcast->newItem($converted_title, $converted_desc, $converted_urls[0], $converted_guid);

        //Itunes subtitle
        $pitem->itunes_subtitle = $converted_title;

        //Author
        if(!empty($converted_author)) {
            $pitem->author = $converted_author;
        } else {
            $pitem->author = $converted['author'];
        }

        //Pubdate check
        if(!empty($converted_created)) {
            $pitem->setValue("pubDate", $converted_created);
        }
        if(!empty($converted_pubdate)) {
            $pitem->setValue("pubDate", $converted_pubdate);
        }

        //If this item's pubDate is newer use it for the channel pubDate
        $thispubdate = strtotime($pitem->pubDate);
        $chnlpubdate = strtotime($podcast->pubDate);
        if (($thispubdate != FALSE) && ($thispubdate > $chnlpubdate || $pcount == 1)) {
            $podcast->setValue("pubDate", $converted_created);
            //loggit(3, "RSS DEBUG: Set channel pubdate to: [$converted_created].");
        }

        //Image check
        if(!empty($converted_images[0])) {
            $pitem->itunes_image = $converted_images[0];
        }

        //Enclosure check
        if(!empty($converted_enclosures[0])) {
            $pitem->addEnclosure($converted_enclosures[0]['url'], $converted_enclosures[0]['length'], $converted_enclosures[0]['type']);
        }

        //Explicit episode check
        if($converted_explicits != $converted['explicit']) {
            if($converted_explicits) {
                $pitem->itunes_explicit = "Yes";
            } else {
                $pitem->itunes_explicit = "No";
            }
        }

        //Keyword check
        if(!empty($converted_keywords)) {
            $pitem->itunes_keywords = $converted_keywords;
        }


    }

    //Give an error code if there were no RSS items found
    if ($pcount == 0) {
        loggit(3, "There were zero rss item nodes processed for this outline.");
        return (-2);
    }

    //Log and leave
    loggit(3, "Got [$pcount] rss item nodes from the outline.");
    try {
        $xxp = $podcast->xml(TRUE);
    } catch (Exception $e) {
        loggit(3, 'Caught exception: '.$e->getMessage());
        return(-3);
    }

    loggit(3,"DEBUG! RSS XML generated successfully.");
    return ($xxp);
}


//Recursive function for parsing an entire outline structure into rss format
function buildRssFromOpmlRecursive($x = NULL, &$html, $indent = 0, $line = 0, $expansionState = array(), $expand = 1, $expanded = FALSE, &$parents, &$extrahtml, $menuexists = 0, &$extrahead)
{

    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    foreach ($x->children() as $child) {
        $text = (string)$child->attributes()->text;
        $name = (string)$child->attributes()->name;
        $link = (string)$child->attributes()->url;
        $type = strtolower((string)$child->attributes()->type);
        $attr = (string)$child->attributes();
        $oldindent = 0;

        //Set up class strings for different conditions
        $classes = "outline";
        if (!empty($type) && $type != "outline") {
            $classes .= " $type";
        }

        //Push the current type onto the stack
        if (($type == "tabs" || $type == "html" || $type == "document" || $type == "menu" || $type == "presentation") && end(array_values($parents)) != "tabs") {
            array_push($parents, $type);
        }

        //If no expansionState value matches the current visible node count then add a collapsed class
        $exco = "";
        if (!in_array($expand, $expansionState)) {
            $exco .= " collapsed";
        }

        //If this is an outline node, open a tag for it
        if ((string)$child->getName() == "outline") {
            if ($type == "link") {
                $nodetext = "<a href=\"$link\" target=\"_blank\">" . (string)$child->attributes()->text . "</a>";
            } else {
                $nodetext = (string)$child->attributes()->text;
            }
            if (empty($nodetext)) {
                $nodetext = "&nbsp;";
            }

            //Check for aspects of the outline node that might need more classes added for styling
            if (stripos($nodetext, "<a") !== FALSE) {
                $classes .= " wanchor";
            }
            if (stripos($nodetext, "<img") !== FALSE) {
                $classes .= " wimg";
            }

            //Set the variable for holding the next content under certain conditions like tabs
            if (in_array('tab', $parents)) {
                $htmlcontent =& $extrahtml;
            } else {
                $htmlcontent =& $html;
            }

            //Set an expanded class on outline nodes that match the expansionState counter
            $parent = end(array_values($parents));
            if ($type == "menu" && $menuexists == 0) {
                $htmlcontent .= "<div class=\"navbar navbar-fixed-top navbar-inverse\" role=\"navigation\">\n<div class=\"container\">\n<div class=\"navbar-header\">\n<button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\"#navbar-collapse-1\">\n<span class=\"sr-only\">Toggle navigation</span>\n<span class=\"icon-bar\"></span>\n<span class=\"icon-bar\"></span>\n<span class=\"icon-bar\"></span>\n</button>\n<a class=\"navbar-brand\" href='#'>$nodetext</a>\n</div>\n<div class=\"collapse navbar-collapse\" id=\"navbar-collapse-1\"><ul class=\"nav navbar-nav\">\n";
                $menuexists++;
            } else if ($type == "collaborate") {
                $colltime = time();
                $extrahead .= "<script>var TogetherJSConfig_findRoom = \"$colltime\";var TogetherJSConfig_inviteFromRoom = true; var TogetherJSConfig_suppressJoinConfirmation = true;</script><script id='togetherJS' src=\"//togetherjs.com/togetherjs-min.js\"></script>";
            } else if ($type == "presentation") {
                //Bring in the reveal.js style
                $fh = fopen("$confroot/$templates/$cg_editor_presentation_style_filename", "r");
                $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_editor_presentation_style_filename"));
                fclose($fh);
                $extrahead .= "\n      <style>" . $rftemplate . "</style>";
                //Now the script
                $fh = fopen("$confroot/$templates/$cg_editor_presentation_js_filename", "r");
                $rftemplate = fread($fh, filesize("$confroot/$templates/$cg_editor_presentation_js_filename"));
                fclose($fh);
                $extrahead .= "\n      <script id='revealJS'>" . $rftemplate . "</script>";

                //Make collaboration track clicks
                $extrahead .= "<script>var TogetherJSConfig_cloneClicks = true;</script>";

                //Begin the slide sections
                $htmlcontent .= "<section>$nodetext</section>";
            } else if ($type == "tabs") {
                $html .= "\n" . str_repeat('    ', $indent + 1) . "<ul class=\"nav nav-tabs\" id=\"myTab\">";
                $extrahtml .= "<div class=\"tab-content\">\n";
            } else if ($parent == "slide" || $type == "slide" || in_array('slide', $parents)) {
                if (isset($child->outline)) {
                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<section>$nodetext</section><section>\n";
                } else {
                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<section>$nodetext</section>\n";
                }
            } else if ($parent == "presentation") {
                array_push($parents, 'slide');
                $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<section>$nodetext</section>\n";
            } else if ($parent == "tabs") {
                array_push($parents, 'tab');
                $tabid = 'tab' . stripText((string)$child->attributes()->text);
                $html .= "\n" . str_repeat('    ', $indent + 1) . "<li><a href=\"#$tabid\" data-toggle=\"tab\">" . strip_tags($nodetext) . "</a></li>";
                $extrahtml .= "<div class=\"tab-pane\" id=\"$tabid\">\n";
            } else if (in_array('menu', $parents)) {
                if (stripos($nodetext, "navatar") !== FALSE) {
                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "</ul><ul class=\"nav navbar-nav pull-right\"><li>$nodetext</li></ul><ul class=\"nav navbar-nav\">";
                } else {
                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<li>$nodetext</li>";
                }
            } else if (in_array('html', $parents)) {
                $htmlcontent .= str_repeat('    ', $indent) . "$nodetext\n";
            } else if ($type == 'html') {
                $oldindent = $indent;
                $indent = 0;
                $htmlcontent .= str_repeat('    ', $indent) . "$nodetext\n";
            } else {
                if (isset($child->outline)) {
                    $expandible = "<li class=\"owedge$exco\"><span>$nodetext</span>";
                } else {
                    $expandible = "";
                    $expandible = "<li class=\"ou $classes\">$nodetext";
                    $exco = "";
                }
                $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "<ul class=\"$classes\">$expandible";
            }
        }

        //Adjust the expansion state tracking
        $lb = $line + 1;
        $ne = $expand;
        $ex = FALSE;
        if (in_array($expand, $expansionState)) {
            $ex = TRUE;
        }
        if ($expanded || $ex) {
            $ne = $expand + 1;
        }

        //Make the recursion call for the next set of nodes
        list($line, $expand) = buildHtmlFromOpmlRecursive($child, $html, $indent + 1, $line + 1, $expansionState, $ne, $ex, $parents, $extrahtml, $menuexists, $extrahead);


        //If this is an outline node, close the open tag.  We take care to keep the html looking good, so don't add spaces
        //to the end of single line node tags
        $indention = $indent + 1;
        if ($lb == $line) {
            $indention = 0;
        }
        if ((string)$child->getName() == "outline") {
            if ($type == "menu" && $menuexists < 2) {
                $htmlcontent .= str_repeat('    ', $indention) . "</ul>\n</div>\n</div>\n</div>";
                $menuexists++;
            } else if ($type == "collaborate") {
                $htmlcontent .= "\n";
            } else if ($type == "presentation") {
                $htmlcontent .= "";
            } else if ($type == "tabs") {
                $html .= str_repeat('    ', $indention) . "</ul>\n";
                $extrahtml .= str_repeat('    ', $indention) . "</div>\n";
            } else if ($parent == "slide" || $type == "slide" || in_array('slide', $parents)) {
                if (isset($child->outline)) {
                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "</section>\n";
                } else {
                    $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "\n";
                }
            } else if ($parent == "presentation") {
                array_pop($parents);
                $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "\n";
            } else if ($parent == "tabs") {
                array_pop($parents);
                $html .= str_repeat('    ', $indention) . "\n";
                $extrahtml .= str_repeat('    ', $indention) . "</div>\n";
            } else if (in_array('menu', $parents) && $type != "html") {
                $htmlcontent .= "\n" . str_repeat('    ', $indent + 1) . "\n";
            } else if (in_array('html', $parents)) {
                $htmlcontent .= str_repeat('    ', $indent) . "";
            } else if ($type == 'html') {
                $htmlcontent .= str_repeat('    ', $indent) . "";
                $indent = $oldindent;
            } else {
                $htmlcontent .= str_repeat('    ', $indention) . "</li></ul>\n";
            }

        }

        if ($indent == 0 && $ex == FALSE) {
            $expand++;
        }
        if (($type == "tabs" || $type == "html" || $type == "document" || $type == "menu" || $type == "presentation") && end(array_values($parents)) != "tabs") {
            array_pop($parents);
        }
    }


    return (array($line, $expand));
}