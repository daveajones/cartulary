<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_noauth.php" ?>
<?
//Did they specify how many articles they wanted in the list?
if (isset($_REQUEST['max'])) {
    $max = $_REQUEST['max'];
} else if (isset($prefs['maxlist'])) {
    $max = $prefs['maxlist'];
} else {
    $max = $default_max_list;
}

if (isset($_REQUEST['uid'])) {
    $uid = $_REQUEST['uid'];
} else {
    http_response_code(404);
    echo "Feed not found.";
    loggit(2, "External request for a feed with no valid UID given: [$uid]");
    exit(1);
}

if( isset($_REQUEST['type']) && $_REQUEST['type'] == 'opml') {
    echo build_blog_opml_feed($uid, $max);
}

if( isset($_REQUEST['type']) && $_REQUEST['type'] == 'html') {
    echo build_blog_html_archive($uid, $max);
}

//header("Content-Type: text/plain");
if( isset($_REQUEST['feed']) && $_REQUEST['feed'] == 'blog' ) {
    echo build_blog_rss_feed($uid, $max, FALSE, NULL, FALSE, TRUE);
} else {
    echo build_blog_rss_feed($uid, $max);
}