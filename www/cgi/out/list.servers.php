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
$query="";
if( isset($_REQUEST['q']) && !empty($_REQUEST['q']) ) {
  $query = trim($_REQUEST['q']);
}

//Parse the query
$query = parse_search_query($query);
$jsondata['query'] = $query['flat'];
$jsondata['max'] = $query['max'];

//What are we searching?
$section = $query['section'];
$results = get_all_servers();

//Did we get any results?
if( $results != FALSE ) {
  $jsondata['servers'] = $results;
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

return(0);

?>
