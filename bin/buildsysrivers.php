<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    loggit(1, "Building server river...");
    echo "Building server river.\n\n";

    //Build the server-wide river if those values aren't blank
    $tstart = time();
    $s3info = get_sys_s3_info();
    if (!empty($s3info['riverbucket'])) {
        build_server_river_json(100);
    } else {
        loggit(1, "Skipping server-wide river build. Bucket or file value is empty.");
        echo "Skipping server-wide river build. Bucket or file value is empty.\n\n";
    }

    //Calculate how long it took to build the server-wide river
    $took = time() - $tstart;
    loggit(1, "It took: [$took] seconds to build the server-wide river.");


    //Build the individual public rivers for each user in the system that has enabled it
    $users = get_users();
    echo "Building public rivers...\n";
    loggit(3, "Building public rivers...");
    $tstart = time();
    foreach ($users as $user) {
        $rtstart = time();
        $prefs = get_user_prefs($user['id']);
        if ($prefs['publicriver'] == 1) {
            build_public_river($user['id']);
            echo "Built public river for user: [" . $user['id'] . " | " . $user['name'] . " | " . (time() - $rtstart) . "].\n";
            loggit(3, "Built public river for user: [" . $user['id'] . " | " . $user['name'] . " | " . (time() - $rtstart) . "].");
        }
    }
    $took = time() - $tstart;
    loggit(3, "It took: [$took] seconds to build all of the public rivers.");


    //Release the lock
    loggit(1, "Done.");
    cronHelper::unlock();
}
exit(0);

?>
