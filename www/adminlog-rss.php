<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_noauth.php" ?>
<?

//Check for an admin feed token
if(!isset($_REQUEST['t']) || empty($_REQUEST['t']) || $_REQUEST['t'] != $admin_feed_check_token) {
    exit(1);
}

//Did they specify how many articles they wanted in the list?
if (isset($_REQUEST['max'])) {
    $max = $_REQUEST['max'];
} else if (isset($prefs['maxlist'])) {
    $max = $prefs['maxlist'];
} else {
    $max = $default_max_list;
}

echo build_admin_log_rss_feed($max);