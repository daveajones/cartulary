<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<? include "$confroot/$templates/php_page_admin.php" ?>
<?
$section = "Admin-user";
$tree_location = "User Administration";
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

<? //--- Stuff between the title and content --?>
<? include "$confroot/$templates/$template_html_precontent" ?>

<div class="row" id="divAdmin">
    <div class="" id="divUserCreate">
        <p><b><u>Create User</u></b></p>

        <div id="divSendInvite" class="multiForm">
            <div id="tblCreateUser" class="fclist">
                <form id="frmCreateUser" action="/cgi/fc/create.user" method="POST">
                    <div class="divCreateEmail">
                        Name:
                        <input id="uname" name="userName" type="text" class="required"/>
                        Email:
                        <input id="email" name="userEmail" type="text" class="required email"/>
                    </div>
                    <div class="divCreateSubmit">
                        <img class="spinner" src="/images/spinner.gif" alt=""/><input class="btn btn-primary"
                                                                                      id="createSubmit"
                                                                                      name="createSubmit" type="submit"
                                                                                      value="Create"/>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script id="manageusers-template" type="text/x-jquery-tmpl">
                <? include "$confroot/$scripts/admin-user-temp.js" ?>

    </script>

    <div id="divUserManage"></div>
</div>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
<script>
    <?include "$confroot/$scripts/admin.js"?>
</script>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>


