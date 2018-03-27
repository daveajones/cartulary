<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

//Check for command line switch whatif
$doit = TRUE;
if (in_array("whatif", $argv) || in_array("-whatif", $argv) || in_array("--whatif", $argv) || in_array("/whatif", $argv)) {
    $doit = FALSE;
    echo "Test mode.\n\n";
    loggit(3, "Cleaning bad/dead feeds - TEST MODE.");
} else {
    loggit(3, "Cleaning bad/dead feeds.");
}
echo "Cleaning bad/dead feed urls from the database.\n";

$allfeeds = get_all_feeds(NULL, TRUE, TRUE);
$count = 0;
foreach($allfeeds as $feed) {
    $fi   = get_feed_info($feed['id']);
    $id   = $feed['id'];
    $url  = $fi['url'];
    $link = $fi['link'];

    if( $fi['errors'] > 1000 && $fi['lasthttpstatus'] >= 400 && $fi['lastgoodhttpstatus'] < (time() - (86400 * 30)) ) {
        mark_feed_as_dead($feed['id']);
        echo "Removed bad/dead feed: [".$feed['id']."]"."\n";
        loggit(2, "Removed bad/dead feed: [".$feed['id']."]");

        $count++;
    }

}


if($count > 0) {
    echo "Removed: [$count] bad/dead feeds.\n";
    loggit(3, "Removed: [$count] bad/dead feeds.");
} else {
    echo "No bad/dead feeds found.\n";
    loggit(3, "No bad/dead feeds found.");
}