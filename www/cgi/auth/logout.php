<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Get the current cookie value
$sid = $_COOKIE[$sidcookie];

unset($_COOKIE[$sidcookie]);
unset($_COOKIE[$postfollowcookie]);

// Expire the cookie
setcookie($sidcookie, "", time() - 3600, '/');
setcookie($postfollowcookie, "", time() - 3600, '/');

// Remove the sid from the database
expire_session($sid);

//Log it
loggit(1,"User logged out: [$sid]");
?>
<?include "$confroot/$templates/$template_html_prehead"?>
<head>
    <?include "$confroot/$templates/$template_html_meta"?>
    <?include "$confroot/$templates/$template_html_styles"?>
    <?include "$confroot/$templates/$template_html_scripts"?>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
</body>
<?include "$confroot/$templates/$template_html_postbody"?>
<script>
    $(document).ready(function() {
        freedomController.v1.river.methods.removeSessionData();
        window.location = '<?echo $startpage?>';
    });
</script>
</html>