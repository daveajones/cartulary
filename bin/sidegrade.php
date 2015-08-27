<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    echo "Installing any extra modules needed for this release. Please wait...\n";

    //Sidegrade file name for this release if any
    $sgscript = "$confroot/releases/v".$cg_sys_version."-apt.sh";

    if(file_exists($sgscript)) {
        $exoutput = shell_exec($sgscript." 2>&1 > /tmp/v".$cg_sys_version."-sidegrade.log");
        echo $exoutput."\n";
    }

    echo "Done.\n";

    //Release the lock
    cronHelper::unlock();
}
exit(0);