<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    echo "Installing any extra modules needed for this release. Please wait...\n";

    //Sidegrade file name for this release if any
    $sgscript = "$confroot/releases/v" . $cg_sys_version . "-apt.sh";

    echo "Looking for [$sgscript]... ";
    if (file_exists($sgscript)) {
        echo "found. Executing... \n\n";
        echo "This may take a while, so please be patient...";
        $exoutput = shell_exec($sgscript . " 2>&1 | tee /tmp/v" . $cg_sys_version . "-sidegrade.log");
        echo $exoutput . "\n";
        echo "finished.\n";
    }

    echo "\n\n";

    //Release the lock
    cronHelper::unlock();
}
exit(0);