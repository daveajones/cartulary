<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();

//Get the article id to change
if ( isset($_REQUEST['id']) && !empty($_REQUEST['id']) ) {
    $aid = $_REQUEST['id'];
} else {
    //Log it
    loggit(2,"There was no article id in the request.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No article id was specified.";
    echo json_encode($jsondata);
    exit(1);
};

//Get the new title
if ( isset($_REQUEST['title']) && !empty($_REQUEST['title']) ) {
    $title = $_REQUEST['title'];
} else {
    //Log it
    loggit(2,"There was no title string in the request.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "No title was specified.";
    echo json_encode($jsondata);
    exit(1);
};

//Make the change
set_article_title($aid, $title);

//Log ict
loggit(3,"User: [$uid] changed title on article: [$aid].");

//Give feedback that all went well
$jsondata['id'] = $aid;
$jsondata['status'] = "true";
echo json_encode($jsondata);

return(0);