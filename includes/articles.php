<?php
//########################################################################################
// API for managing articles in the database
//########################################################################################


//Check if an article is already in the article repository
function article_exists($url = NULL)
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

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT id FROM $table_article WHERE url=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The article at url: [$url] does not exist in the repository.");
        return (FALSE);
    }
    $sql->bind_result($articleid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "The article at url: [$url] is already in the repository.");
    return ($articleid);
}


//Retrieve an article from the repository
function get_article($id = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($id)) {
        loggit(2, "The article id given is corrupt or blank: [$id]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$includes/opml.php";

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT a.id,
                                 a.title,
                                 a.url,
                                 a.shorturl,
                                 a.createdon,
                                 a.content,
                                 a.sourceurl,
                                 a.sourcetitle,
                                 c.edited
                          FROM $table_article AS a
                          JOIN $table_catalog AS c ON a.id = c.articleid
                          WHERE a.id=? AND c.userid = ?
    ") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $id, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve article content for article id: [$id]");
        return (FALSE);
    }
    $article = array();
    $sql->bind_result($article['id'],
        $article['title'],
        $article['url'],
        $article['shorturl'],
        $article['createdon'],
        $article['content'],
        $article['sourceurl'],
        $article['sourcetitle'],
        $article['edited']
    ) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //If edited was non-blank, convert it from opml to html
    if(!empty($article['edited'])) {
        $article['content'] = $article['edited'];
        unset($article['edited']);
    }

    //If a user id was given, get those details
    if (!empty($uid)) {
        $article['staticurl'] = get_article_static_url($id, $uid);
    }

    loggit(1, "Returning article content for article id: [$id]");
    return ($article);
}


//Retrieve an article from the repository
function get_article_as_opml($id = NULL, $uid = NULL, $withmeta = FALSE)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The article id given is corrupt or blank: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT a.id,
                                 a.title,
                                 a.url,
                                 a.shorturl,
                                 a.createdon,
                                 a.content,
                                 a.sourceurl,
                                 a.sourcetitle,
                                 c.edited
                          FROM $table_article AS a
                          JOIN $table_catalog AS c ON a.id = c.articleid
                          WHERE a.id=? AND c.userid = ?
    ") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $id, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve article content for article id: [$id]");
        return (FALSE);
    }
    $article = array();
    $sql->bind_result($article['id'],
        $article['title'],
        $article['url'],
        $article['shorturl'],
        $article['createdon'],
        $article['content'],
        $article['sourceurl'],
        $article['sourcetitle'],
        $article['edited']
    ) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //If edited was non-blank, convert it from opml to html
    if(!empty($article['edited'])) {
        $article['content'] = $article['edited'];
        unset($article['edited']);
    }

    //If a user id was given, get those details
    if (!empty($uid)) {
        $article['staticurl'] = get_article_static_url($id, $uid);
    }

    //Generate the opml structure
    ob_start();
    include "$confroot/$templates/$template_opml_head";
    $opmlout = ob_get_clean();

    $dacon = date("D, d M Y H:i:s O", $article['createdon']);
    $uname = get_user_name_from_uid($uid);
    $artitle = htmlspecialchars(trim(str_replace("\n", '', $article['title'])));
    $arurl = xmlentities( $article['url'] );
    $aiurl = xmlentities($system_url.$showarticlepage."?"."aid=".$article['id']);


$opmlout .= <<<OPMLOUT1
<head>
    <title>$artitle</title>
    <dateCreated>$dacon</dateCreated>
    <dateModified>$dacon</dateModified>
    <ownerName>$uname</ownerName>
    <ownerId>$uid</ownerId>
    <expansionState></expansionState>
    <expansionState>1, 5</expansionState>
    <vertScrollState>1</vertScrollState>
    <windowTop>146</windowTop>
    <windowLeft>107</windowLeft>
    <windowBottom>468</windowBottom>
    <windowRight>560</windowRight>
</head>
<body>
OPMLOUT1;
    if( $withmeta ) {
        $opmlout .= "<outline text=\"Link to Article\" type=\"link\" url=\"$arurl\" />";
        if (!empty($article['staticurl'])) {
            $opmlout .= "    <outline text=\"Archived Version\" type=\"link\" url=\"" . xmlentities($article['staticurl']) . "\" />" . "\n";
        }
        if (!empty($article['sourceurl']) || !empty($article['sourcetitle'])) {
            $opmlout .= '    <outline text="Source: ' . htmlspecialchars(trim($article['sourcetitle'])) . '" type="link" url="' . htmlspecialchars(trim($article['sourceurl'])) . '" />' . "\n";
        }
        $opmlout .= "      <outline text=\"" . date("D, d M Y H:i", $article['createdon']) . "\" />";
        $opmlout .= "      <outline text=\"\" />";
    }

    $uniform = preg_replace("/[\r\n]{2,}/", "\n", $article['content']);

    //We need to know if this document had html tags
    $washtml = FALSE;
    if($uniform != strip_tags($uniform)) {
        $washtml = TRUE;
        loggit(3, "DEBUG: This article has HTML");
        $parts = preg_split("/(<\/p>|<br>|<br\/>|<\/li>|<hr>|<hr\/>|<\/h1>|<\/h2>|<\/h3>|<\/h4>|<\/h5>)/", $uniform, -1, PREG_SPLIT_DELIM_CAPTURE);

        $sentences = array();
        for ($i=0, $n=count($parts)-1; $i<$n; $i+=2) {
            $sentences[] = $parts[$i].$parts[$i+1];
        }
        if ($parts[$n] != '') {
            $sentences[] = $parts[$n];
        }
    } else {
        loggit(3, "DEBUG: This article is plain text.");
        $sentences = explode("\n", $uniform);
    }


    //loggit(3, "DEBUG: ".print_r($sentences,TRUE));
    //$delims = array("</p>","<br>","<br/>","</li>","<hr>","<hr/>","</h1>","</h2>","</h3>","</h4>","</h5>");
    //$uniform = str_replace($delims, "[$@$]", trim( str_replace("\n", '', $article['content'] )));
    
    foreach ( $sentences as $line ) {
        if($washtml) {
            $line = str_replace('<ul>', '', $line);
            $line = str_replace('<li>', '<ul><li>', $line);
            $line = str_replace('</li>', '</li></ul>', $line);
        }
        $line = str_replace("\n", '', $line);
        $line = str_replace("\r", '', $line);
        $opmlout .= "<outline text=\"".xmlentities(trim(strip_tags($line, '<a><b><i><em><u><img><ul><li><h1><h2><h3><h4><h5>')))."\" />";
    }

$opmlout .= <<<OPMLOUT2
</body>
OPMLOUT2;

    ob_start();
    include "$confroot/$templates/$template_opml_feet";
    $opmlout .= ob_get_clean();

    loggit(3, "Returning opml for article id: [$id]");
    return (str_replace("\n", "", str_replace('"', '\\"', $opmlout)));
}


//Retrieve an article's text analysis from the repository
function get_article_analysis($id = NULL)
{
    //Check parameters
    if ($id == NULL) {
        loggit(2, "The article id given is corrupt or blank: [$id]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT analysis FROM $table_article WHERE id=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $id) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve article content for article id: [$id]");
        return (FALSE);
    }
    $sql->bind_result($analysis) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Put the analysis text into an array
    $analarray = explode(',', $analysis);


    loggit(1, "Returning article analysis for article id: [$id]");
    return ($analarray);
}


//Retrieve a random article from the article table
function get_random_article()
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT id,title,url,shorturl,createdon,content,sourceurl,sourcetitle FROM $table_article ORDER BY RAND() LIMIT 1") or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "Failed to retrieve article content for article id: [$id]");
        return (FALSE);
    }
    $article = array();
    $sql->bind_result($article['id'],
        $article['title'],
        $article['url'],
        $article['shorturl'],
        $article['createdon'],
        $article['content'],
        $article['sourceurl'],
        $article['sourcetitle']
    ) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(3, "Returning article titled: [" . $article['title'] . "] as a random article.");
    return ($article);
}


//Add an article to the article repository
function add_article($url = NULL, $title = NULL, $content = NULL, $analysis = NULL, $uid = FALSE, $shorturl = FALSE, $pub = FALSE, $sourceurl = NULL, $sourcetitle = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($url == NULL) {
        loggit(2, "The article url is blank or corrupt: [$url]");
        return (FALSE);
    }
    if ($title == NULL) {
        loggit(2, "The article title is blank or corrupt: [$title]");
        return (FALSE);
    }
    if ($content == NULL) {
        loggit(2, "The article content is blank or corrupt: [$content]");
        return (FALSE);
    }
    if ($analysis == NULL) {
        loggit(2, "The textual analysis is blank or corrupt: [$analysis]");
        $analysis = "";
    }
    if (empty($sourceurl)) {
        //loggit(2,"The ual analysis is blank or corrupt: [$analysis]");
        $sourceurl = "";
    }
    if (empty($sourcetitle)) {
        //loggit(2,"The textual analysis is blank or corrupt: [$analysis]");
        $sourcetitle = "";
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Timestamp
    $id = random_gen(128);
    $createdon = time();

    //Is there a short url
    if ($shorturl == FALSE) {
        loggit(1, "No short url given for: [$url].");
        $shorturl = "";
    } else {
        loggit(1, "Short url for: [$url] is: [$shorturl].");
    }

    //Now that we have a good id, put the article into the database
    $stmt = "INSERT INTO $table_article (id,url,title,content,analysis,createdon,shorturl,sourceurl,sourcetitle) VALUES (?,?,?,?,?,?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sssssdsss", $id, $url, $title, $content, $analysis, $createdon, $shorturl, $sourceurl, $sourcetitle) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Now link the article's id to this user's id
    if ($uid != FALSE) {
        link_article_to_user($id, $uid, $pub);
    }

    //Log and return
    loggit(1, "Put a new article in the repository: [$title] with url [($shorturl) $url].");
    return ($id);
}


//Link an article to a specific user account, making it private
function link_article_to_user($aid = NULL, $uid = NULL, $pub = FALSE)
{
    //Check parameters
    if ($aid == NULL) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
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

    //Check if public
    if ($pub == TRUE) {
        $publ = 1;
    } else {
        $publ = 0;
    }

    //Link the article to the user
    $stmt = "INSERT INTO $table_catalog (userid,articleid,public,linkedon) VALUES (?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssds", $uid, $aid, $publ, $tstamp) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Linked article: [$aid] with user: [$uid].");
    return (TRUE);
}


//Does the user have permission to view this article
function user_can_view_article($aid = NULL, $uid = NULL)
{
    //Check parameters
    if ($aid == NULL) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
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

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT userid FROM $table_catalog WHERE articleid=? AND (userid=? OR public=1)") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $aid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "This user: [$uid] is not allowed to see article: [$aid].");
        return (FALSE);
    }
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "User: [$uid] is allowed to see article: [$aid].");
    return (TRUE);
}


//Retrieve articles from repository as a batch
function get_articles($uid = NULL, $max = NULL, $pub = FALSE, $archive = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';


    //Is this a request for a certain month's worth of posts?
    if ($archive != FALSE) {
        $mfirst = mktime(0, 0, 0);
        //loggit(3, "Timestamp of start of day: [".$mfirst."]");
        $mlast = mktime(23, 59, 00);
        //loggit(3, "Timestamp of end of day: [".$mlast."]");
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sqltxt = "SELECT $table_article.id,
                    $table_article.title,
                    $table_article.url,
                    $table_article.shorturl,
                    $table_article.createdon,
                    $table_article.content,
                    $table_catalog.linkedon,
		    $table_article.sourceurl,
	            $table_article.sourcetitle
	     FROM $table_article,$table_catalog
	     WHERE $table_catalog.userid=?
             AND ($table_catalog.articleid=$table_article.id)";

    if ($pub == TRUE) {
        $sqltxt .= " AND ($table_catalog.articleid=$table_article.id OR $table_catalog.public=1)";
    }

    if ($archive != FALSE) {
        $sqltxt .= " AND $table_catalog.linkedon > $mfirst AND $table_catalog.linkedon < $mlast";
    }

    $sqltxt .= " ORDER BY $table_catalog.linkedon DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any articles for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No articles returned for user: [$uid] with given criteria.");
        return (array());
    }

    $sql->bind_result($aid, $atitle, $aurl, $ashorturl, $acreatedon, $acontent, $clinkedon, $asourceurl, $asourcetitle) or loggit(2, "MySql error: " . $dbh->error);

    $articles = array();
    $count = 0;
    while ($sql->fetch()) {
        $articles[$count] = array('id' => $aid,
            'title' => $atitle,
            'url' => $aurl,
            'shorturl' => $ashorturl,
            'staticurl' => get_article_static_url($aid, $uid),
            'createdon' => $acreatedon,
            'content' => $acontent,
            'linkedon' => $clinkedon,
            'sourceurl' => $asourceurl,
            'sourcetitle' => $asourcetitle);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] articles for user: [$uid]");
    return ($articles);
}


//Retrieve list of articles for a user without the content
function get_article_list($uid = NULL, $max = NULL, $pub = FALSE, $archive = FALSE, $since = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';


    //Is this a request for a certain month's worth of posts?
    if ($archive != FALSE) {
        $mfirst = mktime(0, 0, 0);
        //loggit(3, "Timestamp of start of day: [".$mfirst."]");
        $mlast = mktime(23, 59, 00);
        //loggit(3, "Timestamp of end of day: [".$mlast."]");
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sqltxt = "SELECT $table_article.id,
                    $table_article.title,
                    $table_article.url,
                    $table_article.shorturl,
                    $table_article.createdon,
                    $table_catalog.linkedon,
		    $table_article.sourceurl,
	            $table_article.sourcetitle
	     FROM $table_article,$table_catalog
	     WHERE $table_catalog.userid=?
             AND ($table_catalog.articleid=$table_article.id)";

    if ($pub == TRUE) {
        $sqltxt .= " AND ($table_catalog.articleid=$table_article.id OR $table_catalog.public=1)";
    }

    if (empty($since)) {
        if ($archive != FALSE) {
            $sqltxt .= " AND $table_catalog.linkedon > $mfirst AND $table_catalog.linkedon < $mlast";
        }

        $sqltxt .= " ORDER BY $table_catalog.linkedon DESC";
    } else {
        $sqltxt .= " AND $table_catalog.linkedon > ?";

        $sqltxt .= " ORDER BY $table_catalog.linkedon DESC";
    }

    if (empty($max) || !is_numeric($max)) {
        $max = $default_max_opml_items;
    }
    $sqltxt .= " LIMIT ?";

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    if(empty($since)) {
        $sql->bind_param("sd", $uid, $max) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql->bind_param("sdd", $uid, $since, $max) or loggit(2, "MySql error: " . $dbh->error);
    }

    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any articles for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No articles returned for user: [$uid] with given criteria.");
        return (array());
    }

    $sql->bind_result($aid, $atitle, $aurl, $ashorturl, $acreatedon, $clinkedon, $asourceurl, $asourcetitle) or loggit(2, "MySql error: " . $dbh->error);

    $articles = array();
    $count = 0;
    while ($sql->fetch()) {
        $articles[$count] = array('id' => $aid,
            'title' => $atitle,
            'url' => $aurl,
            'shorturl' => $ashorturl,
            'staticurl' => get_article_static_url($aid, $uid),
            'createdon' => $acreatedon,
            'linkedon' => $clinkedon,
            'sourceurl' => $asourceurl,
            'sourcetitle' => $asourcetitle);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] articles for user: [$uid]");
    return ($articles);
}


//Retrieve articles in a current date range
function get_articles_in_range($uid = NULL, $max = NULL, $pub = FALSE, $dstart = NULL, $dend = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }
    if (empty($dstart) && empty($dend)) {
        loggit(2, "No start and end date were given: [$dstart | $dend].");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Create the proper range values
    if (!empty($dstart)) {
        $mfirst = strtotime($dstart);
    } else {
        $mfirst = mktime(0, 0, 0);
        loggit(3, "Timestamp of start of range: [" . $mfirst . "]");
    }
    if (!empty($dend)) {
        $mlast = strtotime("midnight +1 day" . $dend);
    } else {
        $mlast = mktime(23, 59, 00);
        loggit(3, "Timestamp of end of range: [" . $mlast . "]");
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sqltxt = "SELECT $table_article.id,
                    $table_article.title,
                    $table_article.url,
                    $table_article.shorturl,
                    $table_article.createdon,
                    $table_article.content,
                    $table_catalog.linkedon,
		    $table_article.sourceurl,
	            $table_article.sourcetitle
	     FROM $table_article,$table_catalog
	     WHERE $table_catalog.userid=?
             AND ($table_catalog.articleid=$table_article.id)";

    if ($pub == TRUE) {
        $sqltxt .= " AND ($table_catalog.articleid=$table_article.id OR $table_catalog.public=1)";
    }

    if (isset($dstart) && isset($dend)) {
        $sqltxt .= " AND $table_catalog.linkedon > $mfirst AND $table_catalog.linkedon < $mlast";
    }

    $sqltxt .= " ORDER BY $table_catalog.linkedon DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    }

    loggit(1, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any articles for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No articles returned for user: [$uid] with given criteria.");
        return (FALSE);
    }

    $sql->bind_result($aid, $atitle, $aurl, $ashorturl, $acreatedon, $acontent, $clinkedon, $asourceurl, $asourcetitle) or loggit(2, "MySql error: " . $dbh->error);

    $articles = array();
    $count = 0;
    while ($sql->fetch()) {
        $articles[$count] = array('id' => $aid,
            'title' => $atitle,
            'url' => $aurl,
            'shorturl' => $ashorturl,
            'createdon' => $acreatedon,
            'content' => $acontent,
            'linkedon' => $clinkedon,
            'sourceurl' => $asourceurl,
            'sourcetitle' => $asourcetitle);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] articles for user: [$uid]");
    return ($articles);
}


//Search for articles that match query
function search_articles($uid = NULL, $query = NULL, $max = NULL, $pub = FALSE, $withopml = FALSE)
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
        "$table_article.analysis",
        "$table_article.title",
        "$table_article.enclosure",
        "$table_article.sourcetitle",
        "$table_article.url"
    );
    $qsql = build_search_sql($query, $colnames);

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    if ($pub == TRUE) {
        $sqltxt = "SELECT $table_article.id,
                          $table_article.title,
                          $table_article.url
	              FROM $table_article,$table_catalog
	              WHERE ( $table_catalog.userid=? AND ($table_catalog.articleid=$table_article.id OR $table_catalog.public=1) )";
        if($withopml) {
            $sqltxt = "SELECT $table_article.id,
                              $table_article.title,
                              $table_article.url,
                              $table_article.content,
                              $table_catalog.staticurl
                       FROM $table_article,$table_catalog
	                   WHERE ( $table_catalog.userid=? AND ($table_catalog.articleid=$table_article.id OR $table_catalog.public=1) )";
        }
    } else {
        $sqltxt = "SELECT $table_article.id,$table_article.title,$table_article.url
	     FROM $table_article,$table_catalog
	     WHERE ( $table_catalog.userid=? AND ($table_catalog.articleid=$table_article.id) )";
        if($withopml) {
           $sqltxt = "SELECT $table_article.id,
                             $table_article.title,
                             $table_article.url,
                             $table_article.content,
                             $table_catalog.staticurl
	                  FROM $table_article,$table_catalog
	                  WHERE ( $table_catalog.userid=? AND ($table_catalog.articleid=$table_article.id) )";
        }
    }

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
    $qsql['bind'][0] = & $newsetup;
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
        loggit(1, "No articles returned for user: [$uid] with given criteria.");
        return (FALSE);
    }

    if($withopml) {
        $sql->bind_result($aid, $atitle, $aurl, $acontent, $astaticurl) or loggit(2, "MySql error: " . $dbh->error);
    } else {
        $sql->bind_result($aid, $atitle, $aurl) or loggit(2, "MySql error: " . $dbh->error);
    }

    $articles = array();
    $count = 0;
    while ($sql->fetch()) {
        $articles[$count] = array('id' => $aid, 'title' => $atitle, 'url' => $aurl);
        if($withopml) {
            $articles[$count]['content'] = $acontent;
            $articles[$count]['staticurl'] = $astaticurl;
        }
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    if($withopml) {
        $s3url = build_opml_feed($uid, $max, FALSE, $articles, FALSE, "search/articlesearch", TRUE, "Article search results: [".$query['flat']."]");
        loggit(3, "OPMLURL: $s3url");
        if(is_string($s3url)) {
            $articles['opmlurl'] = $s3url;
            loggit(3, "OPMLURL: ".$articles['opmlurl']);
        }
    }

    loggit(1, "Returning: [$count] articles for user: [$uid]");
    return ($articles);
}


//Build an rss feed for the given user
function build_rss_feed($uid = NULL, $max = NULL, $archive = FALSE, $articles = NULL)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';
    require_once "$confroot/$libraries/s3/S3.php";
    require_once "$confroot/$includes/feeds.php";

    $username = get_user_name_from_uid($uid);
    $prefs = get_user_prefs($uid);

    //If this user doesn't want his cart feed public, then exit
    if ($prefs['publicdefault'] == 1) {
        loggit(3, "User: [$uid] want's their article feed to be private.");
        return (FALSE);
    }

    //Get a proper max value
    if ($max == NULL) {
        if (!empty($prefs['maxlist'])) {
            $max = $prefs['maxlist'];
        } else {
            $max = $default_max_rss_items;
        }
    }

    //Get the right timezone
    date_default_timezone_set('America/Chicago');

    //Allow passing in a list of articles as a param
    if ($articles == NULL || !is_array($articles)) {
        $articles = get_articles($uid, $max, NULL, $archive);
    }
    $count = 0;

    //Get feed title
    $title = get_cartulary_title($uid);

    //The feed string
    $rss = '<?xml version="1.0"?>' . "\n  <rss version=\"2.0\" xmlns:sopml=\"http://v1.sopml.com/\" xmlns:source=\"http://source.smallpict.com/2014/07/12/theSourceNamespace.html\">\n    <channel>";

    $rss .= "\n
      <title>$title</title>
      <link>" . htmlspecialchars($prefs['homepagelink']) . "</link>
      <description>What $username is reading.</description>
      <language>en-us</language>
      <pubDate>" . date("D, d M Y H:i:s O") . "</pubDate>
      <lastBuildDate>" . date("D, d M Y H:i:s O") . "</lastBuildDate>
      <generator>$system_name, v$version</generator>
      <managingEditor>" . get_email_from_uid($uid) . " ($username)</managingEditor>
      <webMaster>" . $email_filemaster . "</webMaster>\n";

    if ($cg_opmlcloud_enabled == 1) {
        $rss .= "      <sopml:updates host=\"" . $cg_opmlcloud_host . "\" port=\"" . $cg_opmlcloud_port . "\" type=\"" . $cg_opmlcloud_type . "\" value=\"" . random_gen(16) . "\" />\n";
    }

    if (!empty($prefs['avatarurl'])) {
        $rss .= "      <source:avatar>" . $prefs['avatarurl'] . "</source:avatar>\n";
        $rss .= "      <sopml:avatar>" . $prefs['avatarurl'] . "</sopml:avatar>\n";
    }


    foreach ($articles as $article) {
        $linkfull = str_replace('&', '&amp;', $article['url']);
        if ($prefs['sourceurlrss'] == 1) {
            if ($article['shorturl'] != "") {
                $rssurl = str_replace('&', '&amp;', $article['shorturl']);
            } else {
                $rssurl = str_replace('&', '&amp;', $article['url']);
            }
        } else {
            $rssurl = $system_url . $showarticlepage . urlencode("?" . "aid=" . $article['id']);
        }

        $rss .= "
      <item>
        <title>" . trim(str_replace('&', '&amp;', str_replace(array("\r", "\n"), '', $article['title']))) . "</title>
        <link>" . htmlspecialchars($rssurl) . "</link>
        <description>" . htmlspecialchars($article['content']) . "</description>
        <pubDate>" . date("D, d M Y H:i:s O", $article['createdon']) . "</pubDate>
        <guid>" . htmlspecialchars($rssurl) . "</guid>
        <sopml:origin>" . htmlspecialchars($linkfull) . "</sopml:origin>
        <source:linkFull>" . htmlspecialchars($linkfull) . "</source:linkFull>\n";
        if (!empty($article['sourceurl']) || !empty($article['sourcetitle'])) {
            $rss .= '
        <source url="' . htmlspecialchars(trim($article['sourceurl'])) . '">' . htmlspecialchars(trim($article['sourcetitle'])) . '</source>' . "\n";
        }
        $rss .= "      </item>\n";
        $count++;
    }

    $rss .= "\n    </channel>\n  </rss>";

    //If this user has S3 storage enabled, then do it
    if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Get the microblog feed file name
        $filename = get_cartulary_feed_filename($uid);
        $arcpath = '';

        //Was this a request for a monthly archive?
        if ($archive != FALSE) {
            $arcpath = "/arc/" . date('Y') . "/" . date('m') . "/" . date('d');
            //loggit(3, "Archive path: [".$arcpath."]");
        }

        //Put the file
        $s3res = putInS3($rss, $filename, $s3info['bucket'] . $arcpath, $s3info['key'], $s3info['secret'], "application/rss+xml");
        if (!$s3res) {
            loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
            //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
        } else {
            $s3url = get_s3_url($uid, $arcpath, $filename);
            loggit(1, "Wrote feed to S3 at url: [$s3url].");

            //Mark feed as updated internally
            $id = feed_exists($s3url);
            if ($id != FALSE) {
                mark_feed_as_updated($id);
            }
        }
    }


    loggit(1, "Built rss feed for user: [$username | $uid] containing: [$count] items.");
    return ($rss);
}


//Unlink an article from the user's catalog
function unlink_article($uid = NULL, $aid = NULL)
{
    //Check params
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($aid == NULL) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_catalog WHERE userid=? AND articleid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $uid, $aid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and leave
    loggit(1, "Deleted: [$delcount] article: [$aid] from user: [$uid].");
    return (TRUE);
}


//Purge article catalogs from the database that aren't linked to anyone
function purge_orphaned_articles()
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Find articles that have no linkage
    $stmt = "DELETE FROM $table_article WHERE NOT EXISTS ( SELECT * FROM $table_catalog WHERE $table_article.id = $table_catalog.articleid )";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and leave
    loggit(3, "Deleted: [$delcount] orphaned articles.");
    return ($delcount);
}


//Build an opml version of the user's article list
function build_opml_feed($uid = NULL, $max = NULL, $archive = FALSE, $articles = NULL, $nos3 = FALSE, $s3filename = NULL, $returns3url = FALSE, $giventitle = NULL)
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

    //If this user doesn't want his cart feed public, then exit
    if ($prefs['publicopml'] == 1 && $nos3 == FALSE && (empty($articles) || !is_array($articles) || empty($s3filename))) {
        loggit(3, "User: [$uid] want's their article feed to be private.");
        return (FALSE);
    }

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
    if ($articles == NULL || !is_array($articles)) {
        $articles = get_articles($uid, $max, NULL, $archive);
    }

    //Get the dates straight
    if (empty($articles)) {
        $dateCreated = date("D, d M Y H:i:s O");
        $dateModified = date("D, d M Y H:i:s O");
    } else {
        $dateCreated = date("D, d M Y H:i:s O", $articles[0]['createdon']);
        $dateModified = date("D, d M Y H:i:s O", $articles[0]['createdon']);
    }

    $outlinetitle = "What $username is reading";
    if(!empty($giventitle)) {
        $outlinetitle = $giventitle;
    }

    //The feed string
    $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
    $opml .= "<!-- OPML generated by " . $system_name . " v" . $version . " on " . date("D, d M Y H:i:s O") . " -->\n";
    $opml .= '<opml version="2.0">' . "\n";

    $opml .= "
      <head>
        <title>" . xmlentities($outlinetitle) . "</title>
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

    foreach ($articles as $article) {
        $opml .= "
              <outline text=\"" . xmlentities(trim(str_replace(array("\r", "\n", "\t", '&#13;'), '', $article['title']))) . "\">
                      <outline text=\"Link to Article\" type=\"link\" url=\"" . htmlspecialchars($article['url']) . "\" />";

        if (!empty($article['staticurl'])) {
            $opml .= "        <outline text=\"Archived Version\" type=\"link\" url=\"" . htmlspecialchars($article['staticurl']) . "\" />" . "\n";
        }

        if (!empty($article['sourceurl']) || !empty($article['sourcetitle'])) {
            $opml .= '        <outline text="Source: ' . htmlspecialchars(trim($article['sourcetitle'])) . '" type="link" url="' . htmlspecialchars(trim($article['sourceurl'])) . '" />' . "\n";
        }


        $opml .= "      <outline text=\"" . date("D, d M Y H:i", $article['createdon']) . "\" />
                      <outline text=\"\" />";
        foreach (explode("</p>", trim(str_replace(array("\r", "\n", "\t", '&#13;'), '', $article['content']))) as $line) {
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

        //Get the microblog feed file name
        if(!empty($s3filename)) {
            $filename = $s3filename.".".time().".opml";
        } else {
            $filename = $default_opml_file_name;
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


    loggit(3, "Built article opml feed for user: [$username | $uid].");
    if($returns3url && $s3res) {
        return($s3url);
    }
    return ($opml);
}


//Get the file name of the article archive feed
function get_cartulary_feed_filename($uid = NULL)
{
    //If uid is zero then balk
    if (empty($uid)) {
        loggit(2, "Can't get the username from this uid: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the user's prefs
    $prefs = get_user_prefs($uid);
    if (empty($prefs['cartfilename'])) {
        return ($default_feed_file_name);
    }

    //loggit(1,"Returning user name: [$username] for uid: [$uid]");
    return ($prefs['cartfilename']);
}


//Get the title of the article archive
function get_cartulary_title($uid = NULL)
{
    //If uid is zero then balk
    if (empty($uid)) {
        loggit(2, "The user id given is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the user's prefs
    $prefs = get_user_prefs($uid);
    $username = get_user_name_from_uid($uid);

    //Return the right title
    if (!empty($prefs['cartularytitle'])) {
        return ($prefs['cartularytitle']);
    } else {
        return ("What $username is reading.");
    }

}


//Get a printer friendly version of an article
function make_article_printable($aid = NULL, $uid = NULL)
{
    //Parameter check
    if (empty($aid)) {
        loggit(2, "Article id is blank or corrupt: [$aid]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "User id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get the article in question
    $article = get_article($aid, $uid);

    //Assemble a printable version
    $printable = "<head>";
    $printable .= "<title>" . $article['title'] . " [Printable]</title>";
    $printable .= "<meta charset='UTF-8'>";
    $printable .= "</head>";
    $printable .= "<body>";
    $printable .= "<div id=\"divPageArticle\">";
    $printable .= "  <div id=\"divHeadline\"><h1>" . $article['title'] . "</h1></div>";
    $printable .= "  <div id=\"divContent\">" . $article['content'] . "</div>";
    $printable .= "  <div id=\"divCite\"><a href=\"" . $article['url'] . "\">" . $article['url'] . "</a></div>";
    $printable .= "</div>";
    $printable .= "</body>";
    $printable .= "</html>";

    //loggit(3, "DEBUG: ".print_r($article, TRUE));

    //Give back
    return ($printable);
}


//Change the static url of an article
function update_article_static_url($aid = NULL, $uid = NULL, $url = NULL)
{
    //Check parameters
    if ($aid == NULL) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($url == NULL) {
        loggit(2, "The article url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article url in the database
    $stmt = "UPDATE $table_catalog SET staticurl=? WHERE articleid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $url, $aid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Changed article: [$aid]'s url to: [$url] for user: [$uid].");
    return (TRUE);
}


//Change the content of an article
function add_edited_content_to_article($aid = NULL, $uid = NULL, $content = NULL)
{
    //Check parameters
    if ($aid == NULL) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
        return (FALSE);
    }
    if ($uid == NULL) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }
    if ($content == NULL) {
        loggit(2, "The edited content is blank or corrupt: [$content]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Do the thing
    $stmt = "UPDATE $table_catalog SET edited=? WHERE articleid=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $content, $aid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Added edited content to article: [$aid] for user: [$uid].");
    return (TRUE);
}


//Get the static url of an article if there is one
function get_article_static_url($aid = NULL, $uid = NULL)
{
    //Check parameters
    if ($aid == NULL) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
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

    //Look for the sid in the session table
    $sql = $dbh->prepare("SELECT staticurl FROM $table_catalog WHERE articleid=? AND userid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $aid, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The article: [$aid] does not exist in the repository.");
        return (FALSE);
    }
    $sql->bind_result($staticurl) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Article: [$aid] has static url: [$staticurl] for user: [$uid].");
    return ($staticurl);
}


//Fetch email as articles from an IMAP server
function imap_fetch_emails_to_articles($uid = NULL, $hostname = "", $username = "", $password = "", $foldername = "") {



    return $count;
}


//Set the feed error count on this feed to a given value
function set_article_title($aid = NULL, $title = NULL)
{
    //Check parameters
    if (empty($aid)) {
        loggit(2, "The article id is blank or corrupt: [$aid]");
        return (FALSE);
    }
    if (empty($title)) {
        loggit(2, "The title is blank or corrupt: [$title]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_article SET title=? WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $title, $aid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    //Log and return
    loggit(1, "Set title for article:[$aid] to: [$title].");
    return (TRUE);
}


//Get the time of the last article import
function get_last_article_import_time($uid = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "User id given is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get user prefs
    $prefs = get_user_prefs($uid);

    //At least one pref was bad
    return ($prefs['lastarticleimporttime']);
}


//Set the last article import time for a user
function set_last_article_import_time($uid = NULL, $time = NULL)
{
    //Check parameters
    if (empty($uid)) {
        loggit(2, "The user id was corrupt or blank: [$uid]");
        return (FALSE);
    }
    if (empty($time)) {
        loggit(2, "The time given was corrupt or blank: [$time]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the uid in the session table
    $stmt = "UPDATE $table_prefs SET lastarticleimporttime=? WHERE uid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ds", $time, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Return
    loggit(1, "Set last article import time to: [$time] for user: [$uid].");
    return (TRUE);
}


//Article extraction as a function
use andreskrey\Readability\Readability;
use andreskrey\Readability\HTMLParser;
use andreskrey\Readability\Configuration;
function cartulize($url = NULL, $content = NULL, $reqtitle = NULL)
{
    //Environment
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

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


    //Punt if the url is blank
    if (empty($url)) {
        loggit(2, "The url was blank or corrupt: [$url].");
        return (FALSE);
    }

    //Globals
    $effective_url = $url;
    $html_only = true;
    $ispdf = FALSE;
    $referer = "";
    $localcontent = FALSE;
    if (!empty($content)) {
        $localcontent = TRUE;
    }
    $title = "";

    // Get a start time
    $tstart = time();

    // Sanitize and validate incoming URL string
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
        loggit(2, "Invalid url supplied: [$url]");
        exit(NULL);
    }

    //Resolve re-directs
    //$newurl = get_final_url($url);
    $newurl = $url;

    //Remove feedburner garbage
    $url = trim(rtrim(preg_replace("/&?utm_(.*?)\=[^&]+/", "", $newurl), '?'));

    //See if the response returned was actually a meta-refresh forwarding document
    //##: -------  PRE-PROCESS the URL here to make sure we dodge any weirdness like proxies or non-HTML content-types
    //Feed proxy?
    if(!$localcontent) {
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
    } else {
        $html = $content;
        loggit(3, "DEBUG: Using local content instead of fetching url: [$url]");
    }

    // ---------- BEGIN ARTICLE PROCESSING ----------
    //We skip all the extraction stuff if the article was already in the database

    //Get the page
    if (!$localcontent && $response) {
        $effective_url = $response['effective_url'];
        loggit(3, "Article effective url is: [$effective_url].");

        $html = $response['body'];
    }

    //loggit(3, "ARTICLE: [$html]");
    if (empty($html)) {
        loggit(3, "DEBUG: Blank content returned for html.");
    }

    // Remove strange things
    $html = str_replace('</[>', '', $html);

    //FC Editor conversion
    $html = preg_replace("/\<ul\ class=\"outline[^>]*\"\>\<li\ class=\"ou\ outline[^>]*\"\>(.*)\<\/li>\<\/ul\>/iU", "<p>$1</p>", $html);

    // Convert encoding
    if(!$localcontent) {
        $html = convert_to_utf8($html, $response['headers']);
    } else {
        $html = convert_to_utf8($html);
    }

    //Was there an error?
    if (!$localcontent && (!$response || $response['status_code'] >= 400)) {
        loggit(2, 'Got back: [' . $response['status_code'] . '] Error retrieving ' . $url . ' [' . $effective_url . ']');
        exit(NULL);
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
        loggit(3, "DEBUG: ----------------------> Askwoody.com post.");

        $dom = new DomDocument();
        $dom->loadHTML($html);
        $eltitle = $dom->getElementsByTagName("title");
        if ($eltitle->length > 0) {
            $title = $eltitle->item(0)->textContent;
        }
        $classname = 'paddings';
        $finder = new DomXPath($dom);
        $nodes = $finder->query("(//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]//ul/li)[1]/*[self::p or self::blockquote or self::img or self::ul or self::ol or self::li or self::a]");
        $tmp_dom = new DOMDocument();
        foreach ($nodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $title, $effective_url);

        $analysis = "";
        $slimcontent = $content;

        //Slate
    } else if (preg_match('/^http.*slate\.com.*/i', $url) && preg_match('/.*slate\-paragraph.*/i', $html)) {
        loggit(3, "DEBUG: ----------------------> Slate.com post.");

        $html = str_replace('<aside', '<div', $html);
        $html = str_replace('</aside>', '</div>', $html);

        $dom = new DomDocument();
        $dom->loadHTML($html);
        $eltitle = $dom->getElementsByTagName("title");
        if ($eltitle->length > 0) {
            $title = $eltitle->item(0)->textContent;
        }
        $classname = 'article__content';
        $finder = new DomXPath($dom);
        $nodes = $finder->query("(//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')])/*[self::p or self::blockquote or self::img or self::ul or self::ol or self::li or self::a]");
        $tmp_dom = new DOMDocument();
        foreach ($nodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $title, $effective_url);

        $analysis = "";
        $slimcontent = $content;

        //Mondaq
    } else if (preg_match('/^http.*mondaq\.com.*/i', $url) && preg_match('/.*mondaq.*/i', $html)) {
        loggit(3, "DEBUG: ----------------------> Slate.com post.");

        $luie = libxml_use_internal_errors(true);
        $dom = new DomDocument();
        $dom->loadHTML($html);
        $eltitle = $dom->getElementsByTagName("title");
        if ($eltitle->length > 0) {
            $title = $eltitle->item(0)->textContent;
        }
        $classname = 'articlebody';
        $finder = new DomXPath($dom);
        $nodes = $finder->query("(//div[contains(concat(' ', normalize-space(@id), ' '), ' $classname ')])/*[self::p or self::blockquote or self::img or self::ul or self::ol or self::li or self::a]");
        $tmp_dom = new DOMDocument();
        foreach ($nodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $reqtitle, $effective_url);
        libxml_use_internal_errors($luie);

        $analysis = "";
        $slimcontent = $content;

        //Is this a blogger post?
    } else if (preg_match('/^http.*blogspot\.com.*/i', $url)) {
        loggit(3, "DEBUG: ----------------------> Getting a blogger.com post.");

        $dom = new DomDocument();
        $dom->loadHTML($html);
        $eltitle = $dom->getElementsByTagName("title");
        if ($eltitle->length > 0) {
            $title = $eltitle->item(0)->textContent;
        }
        $classname = 'post-body';
        $finder = new DomXPath($dom);
        $nodes = $finder->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
        $tmp_dom = new DOMDocument();
        foreach ($nodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $content = clean_article_content($tmp_dom->saveHTML(), 0, FALSE, FALSE, $title, $effective_url);

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
        if (empty($title) && isset($details['title']) && !empty($details['title'])) {
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
        $slimcontent = clean_article_content($content, 0, FALSE, FALSE, $title);

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
            if (!empty($title)) {
                loggit(3, "Got article: [$title] with Readability.");
            }
        } catch (\andreskrey\Readability\ParseException $e) {
            loggit(3, "DEBUG: New cart process failed with error: [" . $e->getMessage . "]");
            return (NULL);
        }

        //Do textual analysis and save it in the database
        $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));

        //Reduce all that whitespace
        $content = clean_article_content($content, 0, TRUE, TRUE, $title, $effective_url);
        $slimcontent = $content;
    }

    //Calculate how long it took to cartulize this article
    $took = time() - $tstart;
    loggit(3, "Article: [$url] took: [$took] seconds to cartulize.");

    //Title override?
    if (!empty($reqtitle)) {
        $title = $reqtitle;
    }

    return (Array('title' => $title, 'content' => $slimcontent, 'analysis' => $analysis));
}