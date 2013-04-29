<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
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
if( isset($_REQUEST['callback']) && !empty($_REQUEST['callback']) ) {
  $callback = trim($_REQUEST['callback']);
}

//Parse the query
$query = parse_search_query($query);
$jsondata['query'] = $query['flat'];
$jsondata['max'] = $query['max'];

//What are we searching?
$section = $query['section'];
$results = get_social_outline_directory($query, $query['max']);

loggit(3, "LIST: ".print_r($results, TRUE));

//Did we get any results?
if( $results != FALSE ) {
  $jsondata['data'] = $results;
}


// --------------------------------------------------------------------------------
$jsondata['status'] = "true";

//We wrap it for jsonp
$resp = $callback."(".json_encode($jsondata).")";
echo $resp;


return(0);
?>
