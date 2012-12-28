<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Get the current cookie value
$sid = $_COOKIE[$sidcookie];

// Expire the cookie
setcookie($sidcookie, "", time() - 3600);

// Remove the sid from the database
expire_session($sid);

//Log it
loggit(1,"User logged out: [$sid]");

// Redirect to the start page
header("Location: $startpage");
?>
