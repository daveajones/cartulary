<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_noauth.php"?>
<?
if( $cg_opensignup != 1 ) {
    header("Location: $loginpage");
    exit(0);
}

$section = "Login";
$tree_location = "Registration";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
    <?include "$confroot/$templates/$template_html_meta"?>
    <title><?echo $tree_location?></title>
    <?include "$confroot/$templates/$template_html_styles"?>
    <?include "$confroot/$templates/$template_html_scripts"?>
    <script>
        $(document).ready( function() {
            //Ajaxify the form
            $('#frmRegister').ajaxForm({
                cache:          false,
                clearForm:      false,
                resetForm:      false,
                timeout:        30000,
                beforeSubmit:   function(data) {
                    $('#btnRegister').attr('disabled', true);
                    $('h3.msg').html('<img src="/images/spinner.gif" /> Submitting...');
                },
                success:        function(data) {
                    $('h3.msg').removeClass('text-success').addClass('text-error');
                    if(data.status == "false") {
                        $('h3.msg').html( data.description );
                    } else {
                        $('h3.msg').removeClass('text-error').addClass('text-success');
                        $('h3.msg').html( data.description );
                        $('a.gologin').show();
                    }
                    $('#btnRegister').attr('disabled', false);
                },
                error:			function(x, t, m) {
                    $('#btnRegister').attr('disabled', false);
                    $('h3.msg').html('Error: ' + m + ' (' + t + ')');
                }
            });
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

<div class="row text-center" id="divRegister">
    <h4>To create an account on this server, you must know the secret phrase the server operator has set.</h4>
    <br/>
    <form id="frmRegister" name="register" action="<?echo $opensignupcgi?>" method="POST" class="<?if ($g_platform != "mobile"){?>form-horizontal<?}?>">
        <fieldset>
            <div class="control-group text-center">
                <input type="text" name="name" id="txtName" placeholder="Enter your full name..." />
                <input type="text" name="email" id="txtEmail" placeholder="Enter a valid email address..." />
                <input type="text" name="secret" id="txtSecret" placeholder="Enter the secret phrase..." />

                <?if( $g_platform == "mobile" ) {?><br/><br/><?}?>

                <img class="imgSpinner" src="/images/spinner.gif" />
                <input id="btnRegister" name="submitRegister" class="btn btn-primary btnSubmit" type="submit" value="Register" />
            </div>
        </fieldset>
    </form>
    <h3 class="msg text-error"></h3>
    <a class="gologin btn btn-success hide" href="/">Login Page</a>
</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
