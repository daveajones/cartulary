<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Globals
$jsondata = array();

//Get the article list
if ( isset($_REQUEST['articles']) ) {
    $articles = $_REQUEST['articles'];
} else {
    //Log it
    loggit(2,"No articles were selected for export.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No articles selected.";
    echo json_encode($jsondata);
    exit(1);
};

//How many articles do they want?
$acount = count($articles);

//Generate an opml file containing the articles selected
$arlist = array();
foreach( $articles as $article ) {
    $arlist[] = get_article($article, $uid);
}
$opml = build_opml_feed($uid, 9999, FALSE, $arlist, TRUE);

//Update the last import time stamp
$importtime = time();
set_last_article_import_time($uid, $importtime);

//Log it
loggit(3,"Exported: [$acount] articles to opml for user: [$uid]. ");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['time'] = $importtime;
$jsondata['opml'] = $opml;
$jsondata['description'] = "Articles exported.";
echo json_encode($jsondata);

return(0);