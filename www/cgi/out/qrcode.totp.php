<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?php

//Bring in qr code library
set_include_path("$confroot/$libraries".PATH_SEPARATOR.get_include_path());
include "phpqrcode/qrlib.php";

//It's important that the river page not cache in the browser
header("Cache-Control: no-cache, max-age=0, must-revalidate, no-store");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");


//This is a png file
header("Content-Type: image/png");

//Get this user's totp seed value
$utps16 = get_totp_seed_from_uid($uid);
if( !empty($utps16) ) {
    $b32 = new Base32;
    $utps32 = $b32->encode($utps16, true);
    $sn = rawurlencode($system_name);
    $qrdata = QRcode::png("otpauth://totp/$sn:$g_myemail?secret=".$utps32."&issuer=$sn");
}