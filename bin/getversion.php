<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    $action = "";
    $rawver = FALSE;
    if (in_array("checknew", $argv)) {
        $action = "checknew";
    }
    if (in_array("raw", $argv)) {
        $rawver = TRUE;
    }
    $myVersion = $cg_sys_version;

    $alreadynotified = FALSE;

    if($rawver) {
        echo "$myVersion";
    } else {
        echo "Running version: [$myVersion]\n";
    }

    if( $action == "checknew" ) {
        //See if there is a new version
        $ghIniContent = fetchUrlSafe($cg_sys_master_update_url);
        $ghIni = parse_ini_string($ghIniContent);
        if(!$ghIni) {
            echo "Can't fetch the latest version from: [$cg_sys_master_update_url]\n";
            loggit(2, "Cannot fetch the latest Cartulary version from: [$cg_sys_master_update_url]");
            //Release the lock
            cronHelper::unlock();
            exit(0);
        }
        $ghVersion = $ghIni['cg_sys_version'];

        //Make sure versions are sane by removing anything non-numeric
        $rawGhVersion = preg_replace("/[^0-9]/", '', $ghIni['cg_sys_version']);
        $rawMyVersion = preg_replace("/[^0-9]/", '', $cg_sys_version);

        //TODO: Add upgrade command text to the admin feed item and parse the release notes to include in item as well

        //See if the gh version is newer
        if( (int)$rawGhVersion > (int)$rawMyVersion ) {
            $items = get_admin_log_items(100);
            foreach( $items as $item ) {
                if (preg_match("/new.*version.*is.*available.*\[".preg_quote($ghVersion, '/')."\]/i", $item['content'])) {
                    $alreadynotified = TRUE;
                }
            }
            if(!$alreadynotified) {
                $upgmessage = "A new version of cartulary is available: [$ghVersion].\n\nYou are currently on version: [$myVersion].\n\n";
                $releasenotes = fetchUrlSafe($cg_sys_master_release_url_prefix."v".$ghVersion."-notes.txt");
                $upgmessage .= $releasenotes;
                add_admin_log_item('<pre>'.$upgmessage.'</pre>', "Upgrade Available");
            }
            loggit(3, "New version of cartulary available: [$ghVersion]");
            echo "A new version is available: $ghVersion\n";
        } else {
            echo "You are on the latest version.\n";
        }
    }

    //Release the lock
    cronHelper::unlock();
}
exit(0);