<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_noauth.php"?>
<?

//Make sure the session type is 1
$sid = $_COOKIE[$sidcookie];
$sesstype = get_session_type($sid);
if( $sesstype != 1 ) {
    header("Location: /");
    exit(0);
}

$section = "Login";
$tree_location = "Validate";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
    <?include "$confroot/$templates/$template_html_meta"?>
    <title><?echo $company_name?></title>
    <?include "$confroot/$templates/$template_html_styles"?>
    <?include "$confroot/$templates/$template_html_scripts"?>
    <script>
        $(document).ready(function() {
            var btnClicked = 'login';

            //Login button
            $('#aLogin').click( function() {
                $('#loginForm').attr('action', '/cgi/auth/validate');
                $('#loginForm').submit();
                return false;
            });

            //Ajaxify the login form
            $('#loginForm').ajaxForm({
                cache:          false,
                clearForm:      true,
                resetForm:      true,
                timeout:        30000,
                beforeSubmit:   function(data) {
                    $('a#aLogin').attr('disabled', true);
                    $('h3.msg').html('<img src="/images/spinner.gif" /> Checking...');
                },
                success:        function(data) {
                    if(data.status == "false") {
                        $('h3.msg').html( data.description );
                    } else
                    if(data.status == "move") {
                        window.location = "/";
                    } else {
                        window.location = data.goloc;
                    }
                },
                error:			function(x, t, m) {
                    $('a#aLogin').attr('disabled', false);
                    $('h3.msg').html('Error: ' + m + ' (' + t + ')');
                }
            });

            //Show the elements
            $('#divLoginFrame').show();
            $('#txtCode').focus();
        });
    </script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar_preauth"?>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

<div id="divLoginFrame" class="row hide">
    <center>
        <div id="divLoginInner">
            <form id="loginForm" action="/cgi/auth/validate" method="post" class="<?if ($g_platform != "mobile"){?>form-horizontal<?}?>">
                <input id="txtCode" name="totp" class="txtinput required" placeholder="Enter Code" type="text" />
                <input type="hidden" name="type" value="json" />

                <a id="aLogin" class="btn" href="#">Validate</a>
            </form>
        </div>
        <br/>
        <h3 class="msg text-error"></h3>
    </center>
</div>

<noscript>
    <center><h3 class="text-error">This site doesn't work without javascript.</h3></center>
</noscript>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>
<?include "$confroot/$templates/$template_html_postbody"?>
</html>
