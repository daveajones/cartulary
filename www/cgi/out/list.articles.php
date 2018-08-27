<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
//--------------------------------------------------------------------------------

//Check for parameters
$max = 10;
if( isset($_REQUEST['max']) && !empty($_REQUEST['max']) && is_numeric($_REQUEST['max']) ) {
    $max = $_REQUEST['max'];
} else {
    $max = 100;
}
if( isset($_REQUEST['since']) && !empty($_REQUEST['since']) && is_numeric($_REQUEST['since']) ) {
    $since = $_REQUEST['since'];
}

loggit(3, "Getting a list of articles since: [".date('r', $since)."]");

//Pull a list of recently edited files for this user
$articles = get_article_list($g_uid, $max, FALSE, FALSE, $since);


//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['articles'] = $articles;
$jsondata['description'] = "Pulled articles list.";
echo json_encode($jsondata);

return(0);