<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  // We have to have a valid article id to show
  $aid = $_REQUEST['aid'];
  if(empty($aid)) {
    header("Location: $errorpage?id=1");
    loggit(2,"There was no article id in the request: [$aid]");
    exit(0);
  }

  //Does the user have permission to see this article?
  if(!user_can_view_article($aid, $uid)) {
    header("Location: $errorpage?id=13");
    loggit(2,"The user tried to view a private article they weren't linked to: [$aid | $uid]");
    exit(0);
  }

  //Get the requested article
  $article = get_article($aid);

  $tree_location = "Show Article";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<title><?echo $article['title']?> [Printable]</title>
<link rel="stylesheet" type="text/css" href="<?echo $url_print_style?>" />
</head>
<body>
<div id="divPageArticle">
		<div id="divHeadline"><h1><?echo $article['title']?></h1></div>
		<div id="divContent"><?echo $article['content'];?></div>
		<div id="divCite"><a href="<?echo $article['url'];?>"><?echo $article['url'];?></a></div>
</div>
</body>
<?include "$confroot/$templates/$template_html_postbody"?>
</html>
