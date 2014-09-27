<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
// --------------------------------------------------------------------------------

loggit(3, "DEBUG: ".print_r($_REQUEST, TRUE));

//This will hold the search results
$jsondata['data'] = "";

//First we process the incoming query for shenanigans
$query = $_REQUEST['q'];
if( !empty($query) ) {
  $trimmed = trim($query);
} else {
  //Log it
  loggit(2,"User: [$uid | $sid] gave a blank query.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The query is blank.";
  echo json_encode($jsondata);
  exit(1);
}
$query = $trimmed;

//Section given?
$section = $_REQUEST['s'];

//Parse the query
$query = parse_search_query($query, $section);
$jsondata['query'] = $query['flat'];
$jsondata['section'] = $query['section'];
$jsondata['max'] = $query['max'];

//What are we searching?
$section = $query['section'];
if( $section == "articles" ) {
  $results = search_articles($uid, $query, $query['max']);
} elseif( $section == "river" ) {
    if( $cg_search_v2_enable ) {
        $results = search2_feed_items($uid, $query, $query['max']);
    } else {
        $results = search_feed_items($uid, $query, $query['max']);
    }
} elseif( $section == "microblog" ) {
  $results = search_posts($uid, $query, $query['max']);
} elseif( $section == "subscribe" ) {
  $results = search_feeds($uid, $query, $query['max']);
} elseif( $section == "people" ) {
  $results = get_social_outline_directory($query, $query['max']);
} elseif( $section == "editor" ) {
  $results = search_editor_files($uid, $query, NULL);
} else {
  //Log it
  loggit(2, "Given section:[$section] not searchable.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "This type of search not supported yet.";
  echo json_encode($jsondata);
  exit(1);
}


//Did we get any results?
if( $results != FALSE ) {
  $jsondata['data'] = $results;
}


// --------------------------------------------------------------------------------
$jsondata['status'] = "true";

//Give feedback that all went well
$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
header("Cache-control: no-cache, must-revalidate");
if(!$xhr) {
  header("Content-Type: text/html");
} else {
  header("Content-Type: application/json");
}

if(!$xhr) {
  $resp = '<textarea>'.json_encode($jsondata).'</textarea>';
} else {
  $resp = json_encode($jsondata);
}
echo $resp;
//Give feedback that all went well

return(0);

?>
