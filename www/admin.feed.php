<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?include "$confroot/$templates/php_page_admin.php"?>
<?
  $section = "Admin-feed";
  $tree_location = "Feed Administration";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
<?include "$confroot/$scripts/admin.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>

<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<div class="row page-header" id="divPageTitle">
	<h1><?echo $tree_location?></h1>
</div>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

<div class="row" id="divAdmin">

  <script id="managefeeds-template" type="text/x-jquery-tmpl">
  <?include "$confroot/$scripts/admin-feed-temp.js"?>
  </script>

  <div id="divFeedManage"></div>

</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>

