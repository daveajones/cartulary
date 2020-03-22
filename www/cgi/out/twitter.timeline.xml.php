<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/xml");

if( !empty($_REQUEST['u']) ) {
	$dom = dom_import_simplexml(twitter_timeline_to_rss($_REQUEST['u']))->ownerDocument;
	$dom->formatOutput = true;
	echo $dom->saveXML();
}

return(0);

?>

