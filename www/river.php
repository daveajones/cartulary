<?
$section = "River";
$tree_location = "River";
include get_cfg_var("cartulary_conf") . '/includes/env.php';
include "$confroot/$templates/php_page_init.php";


header("Content-Security-Policy-Report: script-src 'self' https://apis.google.com upgrade-insecure-requests");
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>

    <!-- Local custom -->
    <script src="/script/local-river.js"></script>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>
<body>
<? include "$confroot/$templates/$template_html_logotop" ?>  <!-- This container is empty for the river so it can be fluid in the next line. -->
</div>
<div class="container-fluid">
    <? include "$confroot/$templates/$template_html_menubar" ?>
    <? include "$confroot/$templates/$template_html_precontent" ?>

    <div class="row-fluid" id="divRiver">

        <div id="stream-wrap" class="stream-wrap">
            <div id="stream" class="stream">
                <button id="unstickyAllItems" title="Unsticky All"><i class="fa fa-star-half-o"></i></button>
                <p class="notice"><img id="imgLoadRiverSpinner" class="imgSpinner" src="/images/spinner.gif" alt=""/>
                    Loading river...</p>

                <div id="stream-items" class="stream-items"></div>
            </div>
        </div>

        <? if ($platform != "mobile") { ?>
            <div id="stream-sidebar-right">
                <div id="divActiveFeeds">
                    <ul class="feedlist">
                    </ul>
                </div>
            </div>
        <? } ?>


        <script id="template" type="text/x-jquery-tmpl">
            <? include "$confroot/$scripts/river-temp-item.js" ?>

        </script>

        <script id="template-sticky" type="text/x-jquery-tmpl">
            <? include "$confroot/$scripts/river-temp-sticky.js" ?>

        </script>

        <script id="template-subitem" type="text/x-jquery-tmpl">
            <? include "$confroot/$scripts/river-temp-subitem.js" ?>

        </script>

        <? //--- Include the footer bar html fragments -----------?>
        <? include "$confroot/$templates/$template_html_footerbar" ?>
        <!-- Local postload scripts -->
        <script src="/script/river-foot.js"></script>
        <? include "$confroot/$templates/$template_html_postbody" ?>
</body>
</html>
