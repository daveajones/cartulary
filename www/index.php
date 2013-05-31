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
<script type="text/javascript">
<!--
        $(document).ready(function() {
		$('#loginForm').validate({
			errorPlacement: function(error, element) {
				element.parent().next().empty();
				error.appendTo( element.parent().next() );
			},
			messages: {
				email: {
					required: " Email required."
				},
				password: {
					required: " Password required."
				}
			}
		});
	});
-->
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar_preauth"?>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

    <div id="divLoginFrame">
	<div id="divLoginInner">
	    <form id="loginForm" action="/cgi/auth/login" method="post">
		<label class="lblLoginLabel" id="lblUsername" for="txtUsername"><h2>Username:</h2></label>
		  <input id="txtUsername" name="email" class="txtinput required" type="text" />
		<label class="lblLoginLabel" id="lblPassword" for="txtPassword"><h2>Password:</h2></label>
                  <input id="txtPassword" name="password" class="txtinput required password" type="password" />
		<br/><?if( $cg_opensignup == 1 ) {?><input id="register" name="register" class="btn pull-left" type="submit" value="Register" /><?}?>
                     <input id="submit" name="submit" class="btn pull-right" type="submit" value="Login" />
	    </form>
	</div>
    </div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>
<?include "$confroot/$templates/$template_html_postbody"?>
</html>
