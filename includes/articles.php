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
    $sql->bind_param("sssssssss", $id, $url, $title, $content, $analysis, $createdon, $shorturl, $sourceurl, $sourcetitle) or loggit(2, "MySql error: " . $dbh->error);
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


//Retrieve an article from the repository
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
function search_articles($uid = NULL, $query = NULL, $max = NULL, $pub = FALSE)
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
        $sqltxt = "SELECT $table_article.id,$table_article.title,$table_article.url
	     FROM $table_article,$table_catalog
	     WHERE ( $table_catalog.userid=? AND ($table_catalog.articleid=$table_article.id OR $table_catalog.public=1) )
    ";
    } else {
        $sqltxt = "SELECT $table_article.id,$table_article.title,$table_article.url
	     FROM $table_article,$table_catalog
	     WHERE ( $table_catalog.userid=? AND ($table_catalog.articleid=$table_article.id) )
    ";
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

    $sql->bind_result($aid, $atitle, $aurl) or loggit(2, "MySql error: " . $dbh->error);

    $articles = array();
    $count = 0;
    while ($sql->fetch()) {
        $articles[$count] = array('id' => $aid, 'title' => $atitle, 'url' => $aurl);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

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
function build_opml_feed($uid = NULL, $max = NULL, $archive = FALSE, $articles = NULL, $nos3 = FALSE)
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
    if ($prefs['publicopml'] == 1 && $nos3 == FALSE) {
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

    //The feed string
    $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n";
    $opml .= "<!-- OPML generated by " . $system_name . " v" . $version . " on " . date("D, d M Y H:i:s O") . " -->\n";
    $opml .= '<opml version="2.0">' . "\n";

    $opml .= "
      <head>
        <title>" . xmlentities("What $username is reading") . "</title>
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
    if ((s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3) {
        //First we get all the key info
        $s3info = get_s3_info($uid);

        //Get the microblog feed file name
        $filename = $default_opml_file_name;
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
            loggit(1, "Wrote feed to S3 at url: [$s3url].");
        }
    }


    loggit(1, "Built article opml feed for user: [$username | $uid].");
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