<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_script_init.php" ?>


//System-wide globals
var systemUrl = '<?echo $system_fqdn?>';
var platform = '<?echo $platform?>';
var gPlatform = platform;
var gDatestamp = '<?echo date('YmdHis')?>';
var gDevice = '<?echo $device?>';
var gSection = '<?if(isset($section)) echo $section?>';
var gPlatformMenubarTopOffset = <?if( $g_platform == "mobile" ) {?>30<?}else{?>80<?}?>;
var gStartPage = '<?echo $startpage?>';
var gDefaultBlogPostMaxCharacters = <?echo $default_blog_post_max_chars?>;
var msgtimer;
var cgTwitterCharacterLimit = '<?echo $cg_twitter_character_limit?>';
var cgMastodonCharacterLimit = '<?echo $cg_mastodon_character_limit?>';

//User globals
var cuTwitterIsEnabled = <?if( twitter_is_enabled($g_uid) ) {?>true<?}else{?>false<?}?>;
var cuMastodonIsEnabled = <?if( mastodon_is_enabled($g_uid) ) {?>true<?}else{?>false<?}?>;
var cuRiverColumnCount = <?if( isset($g_prefs['rivercolumns'])) { echo $g_prefs['rivercolumns']; } else { echo "1"; }?>;
var cuMbReturnHome = <?if( $g_prefs['mbreturnhome'] == 1) { echo "true"; } else { echo "false"; }?>;

//Library prefs
$.Finger = {
    pressDuration: 500,
    doubleTapInterval: 300,
    flickDuration: 150,
    motionThreshold: 5
};