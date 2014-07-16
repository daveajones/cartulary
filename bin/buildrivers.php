<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    loggit(1, "Building rivers...");

    //Build the individual rivers for each user in the system
    $users = get_users();
    echo "Building " . count($users) . " rivers.\n\n";
    loggit(1, "Building " . count($users) . " rivers.");
    $tstart = time();
    foreach ($users as $user) {
        $rtstart = time();
        $prefs = get_user_prefs($user['id']);

        //We don't want to waste time building rivers for users that are not active
        //so if the user hasn't logged in for 3 weeks we just build twice a day
        $ri = get_river_info($user['id']);
        if( !is_user_active($user['id']) || ($user['lastlogin'] < ($tstart - 2419200) && get_user_time_last_active($user['id']) == 0 && (!empty($ri) && ($tstart - $ri['lastbuild']) < 43200)) ) {
            loggit(3, "Skipping river build for inactive user: [".$user['id']."]. River last built at: [".$ri['lastbuild']."]");
        } else {
            if (river_updated($user['id'])) {
                if ($prefs['collapseriver'] == 1) {
                    build_river_json2($user['id'], NULL, TRUE);
                } else {
                    build_river_json($user['id'], NULL, TRUE);
                }
            } else {
                if ($prefs['collapseriver'] == 1) {
                    build_river_json2($user['id']);
                } else {
                    build_river_json($user['id']);
                }
            }

            echo "Built river for user: [" . $user['id'] . " | " . $user['name'] . " | " . (time() - $rtstart) . "].\n";
            loggit(3, "Built river for user: [" . $user['id'] . " | " . $user['name'] . " | " . (time() - $rtstart) . "].");
        }
    }

    //Calculate how long it took to build the rivers
    $took = time() - $tstart;
    echo "It took: [$took] seconds to build: [" . count($users) . "] rivers.\n";
    loggit(1, "It took: [$took] seconds to build: [" . count($users) . "] rivers.");

    loggit(1, "Done.");

    //Release the lock
    cronHelper::unlock();
}
exit(0);