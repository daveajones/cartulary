<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  // See if we have a valid url to get source xml from
  $url = trim($_REQUEST['url']);
  if( !empty($url) ) {
      $filename = basename($url);

      //Get opml data and clean it
      $protpos = stripos($url, 'http');
      if( $protpos <> 0 || $protpos === FALSE ) {
          $badurl = true;
      } else {
          $opmldata = fetchUrl($url);
          if( !is_outline($opmldata) ) {
              $badurl = true;
          } else {
              $opmldata = preg_replace("/\ +\n\n\ +/", "\n\n", $opmldata);
              $opmldata = preg_replace("/\n\ +\n/", "\n\n", $opmldata);
              $opmldata = preg_replace("/[\r\n]\n+/", "\n\n", $opmldata);
              $opmldata = preg_replace("/\r?\n/", "", $opmldata);
              $opmldata = preg_replace("/\n/", "", $opmldata);
              $opmldata = preg_replace("/\'/", "\\\'", $opmldata);
          }
      }
  } else {
      $filename = "";
  }

  $section = "Editor";
  $tree_location = "Edit Outline";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<link rel="stylesheet" href="/style/font-awesome.css" />
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<link rel="stylesheet" href="/style/concord.css" />
<script src="/script/concord.js"></script>
<script src="/script/concordUtils.js"></script>
<script src="/script/bootbox.min.js"></script>
<script>
    //Globals
    var url = '<?echo $url?>';
    var title = "";
    var lasttitle = "";
    var filename = '<?echo $filename?>';
    var bufilename = '<?echo time()."-".$default_opml_export_file_name;?>';
    <?if( isset($badurl) ) {?>
    var badurl = true;
    <?}?>
    <?if( isset($opmldata) && !isset($badurl) ) {?>
    var initialOpmlText = '<?echo $opmldata?>';
    <?} else {?>
    var initialOpmlText = initialOpmltext;
    <?}?>
    <?include "$confroot/$scripts/editor.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>


<body id="bodyEditOutline">
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

<button id="openUrl" class="btn pull-right">Open</button>
<div class="row" id="divEditOutline">
<?if(s3_is_enabled($g_uid) || sys_s3_is_enabled()) {?>
    <div class="divOutlineTitle">
        <img class="imgSpinner" src="/images/spinner.gif" /><button id="btnOpmlSave" class="btn btn-success">Save</button>
        as <input class="title" placeholder="Title" type="text" />
        WYSIWYG? <input class="rendertoggle" type="checkbox" style="margin-top:0;" />
    </div>
    <div class="outlineinfo pull-right"></div>
    <div class="divOutlinerContainer">
        <div id="outliner"></div>
    </div>
<?}else{?>
    <center>You must have S3 enabled on either your server or in your user <a href="<?echo $prefspage?>">prefs</a> to use the editor.</center>
<?}?>
</div>


<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
