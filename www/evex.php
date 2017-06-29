<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_noauth.php" ?>
<?

if( isset($_REQUEST['pt']) && !empty($_REQUEST['pt']) ) {
    $file = get_recent_file_by_privtoken($_REQUEST['pt'], TRUE);
}

if( isset($file['url']) && !empty($file['url']) ) {
    $erurl = $file['url'];

    //Convert the opml url into an html url
    $erurl = str_replace('.html', '.opml', $erurl);
    $erurl = str_replace('/html/', '/opml/', $erurl);

    $urlparts = explode("/", substr($erurl, 9), 2);


    $htmldata = process_opml_to_html($file['content'], $file['title'], $file['uid'], $file['disqus'], $file['url'], TRUE, $erurl);

    header("Content-type: text/html");
    echo $htmldata;
    exit(0);
}

header("Location: $loginpage");