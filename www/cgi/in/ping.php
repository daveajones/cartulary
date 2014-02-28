<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?

//Dump the incoming POST to debug
loggit(3, "PING: ".print_r($_REQUEST, TRUE));

//Is this a registration challenge?
if( !empty($_REQUEST['challenge']) ) {
  echo $_REQUEST['challenge'];
  exit(0);
}

//Make sure this request is valid
if( empty($_REQUEST['url']) ) {
  exit(0);
}

//See if this feed exists
loggit(1, "PING: Incoming ping for url: [".$_REQUEST['url']."].");
$id = feed_exists($_REQUEST['url']);
if( empty($id) ) {
  exit(0);
}

//Flag the feed as needing to be scanned
mark_feed_as_updated($id);