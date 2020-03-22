<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_noauth.php" ?>
<?

//Check for an admin feed token
$sysprefs = get_system_prefs();
if (empty($sysprefs['admin_feed_check_token'])) {
    loggit(2, "No feed check token set in the system: [" . $sysprefs['admin_feed_check_token'] . "]");
    exit(1);
}
$afctoken = "";
if (isset($_REQUEST['t']) && !empty($_REQUEST['t'])) {
    $afctoken = $_REQUEST['t'];
}
if (($afctoken != $sysprefs['admin_feed_check_token']) && ($afctoken != $cg_admin_feed_check_token)) {
    loggit(2, "Feed check token in REQUEST: [$afctoken] did not match system: [" . $sysprefs['admin_feed_check_token'] . "]");
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