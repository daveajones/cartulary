<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<?
//Get the error message from the code given
$message = get_system_message($_REQUEST['id'], 2);

$section = "Error";
$tree_location = "Error";
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>
<? //--- The body tag and anything else needed ---?>
<? include "$confroot/$templates/$template_html_bodystart" ?>
<? //--- Include the logo and menu bar html fragments --?>
<? include "$confroot/$templates/$template_html_logotop" ?>
<? include "$confroot/$templates/$template_html_menubar" ?>

<div class="row page-header" id="divPageTitle">
    <h1><? echo $tree_location ?></h1>
</div>

<? //--- Stuff between the title and content --?>
<? include "$confroot/$templates/$template_html_precontent" ?>

<div class="row" id="divErrorPage">

    <h3>
        <center>An error was encountered: <span style="color:#b00;">"<? echo $message ?>"</span></center>
    </h3>

</div>


<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
</body>
<? include "$confroot/$templates/$template_html_postbody" ?>
</html>
