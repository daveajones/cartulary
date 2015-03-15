<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_noauth.php"?>
<?
  if( !empty($_REQUEST['u']) ) {
    $uid = $_REQUEST['u'];
    if( !user_exist($uid) ) {
      header("Location: $loginpage");
      exit(0);
    }

    $prefs = get_user_prefs($uid);
    if( $prefs['opensubs'] == 0 ) {
      header("Location: $loginpage");
      exit(0);
    }
  } else {
      header("Location: $loginpage");
      exit(0);
  }

  $section = "Subscribe";
  $tree_location = "Open Subscription Add";
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
        $('#frmSubscribe').ajaxForm({
                dataType:       'json',
                timeout:        60000,
                beforeSubmit:   function() {
                        $('#frmSubscribe .imgSpinner').show();
                        $('#frmSubscribe .btnSubmit').attr("disabled", true);
                },
                success:        function(data) {
                        $('#spnMessage').html(" " + data.description);
                        if(data.status == "true") {
                            $('#spnMessage').removeClass('msgbad').removeClass('msgwarn').addClass('msggood');
                        } else {
                            $('#spnMessage').removeClass('msggood').removeClass('msgwarn').addClass('msgbad');
                        }
                        $('#frmSubscribe .imgSpinner').hide();
                        $('#frmSubscribe .btnSubmit').attr("disabled", false);
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

<div class="row" id="divSubscribe">
  <center><h4>Add a feed to <?echo $prefs['pubrivertitle']?></h4></center>
  <br/>
  <form id="frmSubscribe" name="subscribe" action="<?echo $opensubscribecgi?>" method="POST">
  <fieldset>
  <div class="control-group">
    <div id="divSubscribeUrl"><input type="text" name="url" id="txtSubscribeUrl" placeholder="Paste in a feed, list or outline url..." /></div>
    <div id="divSubscribeButtons">
      <img class="imgSpinner" src="/images/spinner.gif" />
      <input id="btnSubscribe" name="submitSubscribe" class="btn btn-primary btnSubmit" type="submit" value="Subscribe" />
        <span id="spnMessage"></span>
    </div>
  </div>
  </fieldset>
  <input type="hidden" name="uid" value="<?echo $uid?>" />
  <input type="hidden" name="opensub" value="true" />
  </form>
</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
