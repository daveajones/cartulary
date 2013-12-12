//System-wide globals
var systemUrl = '<?echo $system_fqdn?>';
var platform = '<?echo $platform?>';
var gDatestamp = '<?echo date('YmdHis')?>';
var gPlatformMenubarTopOffset = <?if( $g_platform == "mobile" ) {?>10<?}else{?>60<?}?>;
var msgtimer;

//User globals
var cuTwitterIsEnabled = <?if( twitter_is_enabled($g_uid) ) {?>true<?}else{?>false<?}?>;
var cuRiverColumnCount = <?echo $g_prefs['rivercolumns']?>;
