<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_script_init.php" ?>


var Hidebigpics = false;
var Hidepics = false;
<?if ( $prefs['mobilehidebigpics'] == 1 && $platform == "mobile" ) {?>
Hidebigpics = true;
<?}?>
<?if ( $prefs['mobilehidepics'] == 1 && $platform == "mobile"  ) {?>
Hidepics = true;
<?}?>