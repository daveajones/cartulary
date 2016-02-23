<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_cgi_init_noauth.php" ?>
<?
header("Content-Type: application/json");

// Get the input
$jsondata = array();
$jsondata['fieldname'] = "";

$sid = $_COOKIE[$sidcookie];
$gcode = $_POST['totp'];

//Make sure the session type is 1
$sesstype = get_session_type($sid);
if ($sesstype != 1) {
    $jsondata['status'] = "move";
    $jsondata['description'] = "Not a valid session type.";
    echo json_encode($jsondata);
    exit(0);
}

//Calculate the time code and compare
$uid = get_user_id_from_sid($sid);
$totpseed = get_totp_seed_from_uid($uid);
$newcode = calculate_totp($totpseed);
if ($gcode != $newcode) {
    loggit(2, "Invalid code received: [$gcode]. Code expected: [$newcode].");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Invalid code.";
    echo json_encode($jsondata);
    exit(0);
}

//Make a new session
expire_session($sid);
if (($sid = new_session($uid)) == FALSE) {
    loggit(2, "Could not create session: [$uid]");
    if ($type == 'json') {
        $jsondata['status'] = "false";
        $jsondata['description'] = "Session creation error.";
        echo json_encode($jsondata);
    } else {
        header("Location: $loginerrorpage?code=4");
    }
    exit(1);
}

//Get user prefs
$prefs = get_user_prefs($uid);

//Should we use session cookies or long term?
$cookieexpire = 0;
if ($prefs['sessioncookies'] == 0) {
    $cookieexpire = time() + (60 * 60 * 24 * 30); //30 days
}

//Make a cookie for this session
setcookie($sidcookie, $sid, $cookieexpire, "/");

//Is there a previously requested uri we need to follow through on?
if( !empty($_COOKIE[$postfollowcookie]) ) {
    $pfc = $_COOKIE[$postfollowcookie];
    loggit(3, "FOLLOW: $pfc");
    $goloc = $pfc;
} else {
    $golog = "/";
}

$jsondata['status'] = "true";
$jsondata['goloc'] = $goloc;
$jsondata['description'] = "Code matched. Logging in.";
echo json_encode($jsondata);
exit(0);