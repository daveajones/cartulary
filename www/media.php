<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  $section = "Media";
  $tree_location = "Media";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<style>

#divMedia {  margin-top:20px;  color:#bbb;  }
#divMedia .pinboard {  margin:0 auto;  }
#divMedia .pinboard .pin {
  float:left;
  margin:10px;
  font-family: "CreteRoundRegular", serif;
  word-wrap:break-word;
  word-break:hyphenate;
  background:#111;
  padding:5px;
  min-width:250px;
}
#divMedia .pinboard .pin .pinmedia {
  display:block;
  margin-left:auto;
  margin-right:auto;
  position:relative;
}
#divMedia .pinboard .pin .pinenclosure {
  min-height:30px;
}
#divMedia .pinboard .pinimage,
#divMedia .pinboard .pinaudio {
  width:260px;
}
#divMedia .pinboard .pin img,
#divMedia .pinboard .pin audio {
  max-width:250px;
}
#divMedia .pinboard .pinhtml,
#divMedia .pinboard .pinvideo {
  width:540px;
  height:300px;
}
#divMedia .pinboard .pin iframe,
#divMedia .pinboard .pin video {
  width:530px;
  height:290px;
}
#divMedia .pinboard .pin .pinfooter {
  margin-top:10px;
}
</style>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
<?include "$confroot/$scripts/subscribe.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>


<!-- ----- Page header:Start ----- -->
<div class="row page-header" id="divPageTitle">
  <h1>Today's Media</h1>
</div>
<!-- ----- Page header:Stop  ----- -->


<div class="row" id="divMedia"></div>


<script id="media-template" type="text/x-jquery-tmpl">
<?include "$confroot/$scripts/media-temp.js"?>
</script>


<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
<script>
<?include "$confroot/$scripts/media.js"?>
</script>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
