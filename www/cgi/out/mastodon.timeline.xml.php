<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/xml");

if( empty($_REQUEST['u']) || strlen($_REQUEST['u']) > 70 || !user_exist($_REQUEST['u'])) {
    echo "Invalid request.";
    return(1);
}

$dom = dom_import_simplexml(mastodon_timeline_to_rss($_REQUEST['u']))->ownerDocument;
$dom->formatOutput = true;
echo $dom->saveXML();

return(0);