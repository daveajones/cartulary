<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  $section = "Wizard";
  $tree_location = "New User - Step 2";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
$(document).ready( function() {
        //The feed subscription form
        $('#frmSubscribe').ajaxForm({
                dataType:       'json',
                timeout:        60000,
                beforeSubmit:   function() {
                        $('#frmSubscribe .imgSpinner').show();
                        $('.btnSubmit').attr("disabled", true);
                },
                success:        function(data) {
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
                        } else {
                                showMessage( data.description, data.status, 5 );
                                window.location.href = '<?echo $startpage?>';
                        }
                        $('#frmSubscribe .imgSpinner').hide();
                        $('.btnSubmit').attr("disabled", false);
                }
        });
        //The skip form
        $('#frmSkip').ajaxForm({
                dataType:       'json',
                timeout:        60000,
                beforeSubmit:   function() {
                        $('#frmSkip .imgSpinner').show();
                        $('.btnSubmit').attr("disabled", true);
                },
                success:        function(data) {
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
                        } else {
                                showMessage( data.description, data.status, 5 );
                                window.location.href = '<?echo $startpage?>';
                        }
                        $('#frmSubscribe .imgSpinner').hide();
                        $('.btnSubmit').attr("disabled", false);
                }
        });
        //Bind events to the file import button
        $('#btnFileShow').click( function() {
                $('#fileOpml').trigger('click');
        });
        $('#fileOpml').change( function() {
                $('#btnSubscribe').trigger('click');
        });
});
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar_postauth"?>

<div class="row page-header" id="divPageTitle">
	<h1><?echo get_user_name_from_uid($uid)?><small><span id="message"></span></small></h1>
</div>

<div class="row" id="divNewUser2">

        <h3>New User - Step 2</h3>

	<p><em>Now, do you have any OPML subscription lists you want to bring in?  If not, just hit skip and we'll bring in some default 
               feeds for you to get your river started.</em></p>

        <br/><br/>

        <form id="frmSubscribe" name="subscribe" action="/cgi/auth/setup2" method="POST">
        <fieldset>
        <div class="control-group">
                        <div id="divSubscribeUrl"><input type="text" name="url" id="txtSubscribeUrl" placeholder="Paste in a feed, list or outline url..." />
                        <div id="divSubscribeUpload">
                                <input id="fileOpml" name="fileopml" class="btn btn-primary" type="file" value="Import" />
                        </div>
                        </div>
                        <div id="divSubscribeButtons">
                                <img class="imgSpinner" src="/images/spinner.gif" />
                                <input id="btnSubscribe" name="submitSubscribe" class="btn btn-primary btnSubmit" type="submit" value="Subscribe" />
                                <?if( $platform != "mobile" && $platform != "tablet" ) {?>
                                or <input id="btnFileShow" name="submitImport" class="btn btn-primary btnSubmit" type="button" value="Import..." />
				<?}?>
                        </div>
        </div>
        </fieldset>
        </form>

        <form id="frmSkip" name="skip" action="/cgi/auth/setup2" method="POST">
                <input name="url" type="hidden" value="<?echo $system_url.$default_subscription_list_url?>" />
        	<div id="divSkip" class="pull-right">
                <span class="message"></span>
                <img class="imgSpinner" src="/images/spinner.gif" />
                <button id="btnPrefSubmit" class="btn btn-primary btnSubmit" type="submit">Skip</button></div>
        </form>

</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
