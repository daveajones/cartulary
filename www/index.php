<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_noauth.php"?>
<?
  // If this person is already logged in, send them to the start page
  if(is_logged_in()) {
    header("Location: $startpage");
    exit(0);
  }

  $section = "Login";
  $tree_location = "Login";
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
			$('#loginForm').attr('action', '/cgi/auth/login');
			$('#loginForm').submit();
			return false;
		});

		//Register button
		$('#aRegister').click( function() {
			$('#loginForm').attr('action', '/cgi/auth/register');
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
    	        $('h3.msg').html('<img src="/images/spinner.gif" /> Logging in...');
			},
            success:        function(data) {
	            if(data.status == "false") {
    	            $('h3.msg').html( data.description );
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
	    <form id="loginForm" action="/cgi/auth/login" method="post" class="form-horizontal">
		<input id="txtUsername" name="email" class="txtinput required" placeholder="Username" type="text" />
		<input id="txtPassword" name="password" class="txtinput required password" placeholder="Password" type="password" />
		<input type="hidden" name="type" value="json" />

		<?if( $g_platform == "mobile" ) {?><br/><br/><?}?>

        <a id="aLogin" class="btn" href="#">Login</a>
        <?if( $cg_opensignup == 1 ) {?> or <a id="aRegister" class="btn" href="#">Register</a><?}?>
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
