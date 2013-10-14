<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  // See if we have a valid url to get source xml from
  $url = $_REQUEST['url'];
  if(!empty($url)) {
      $opmldata = fetchUrl($_REQUEST['url']);
      $filename = basename($_REQUEST['url']);
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
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
    <link rel="stylesheet" href="/style/concord.css" />
    <script src="/script/concord.js"></script>
    <script src="/script/concordUtils.js"></script>
    <script>
        //Globals
        var title = "";
        var lasttitle = "";
        var filename = '<?echo $filename?>';
        var bufilename = '<?echo time()."-".$default_opml_export_file_name;?>';
        <?if( isset($opmldata) ) {?>
        var initialOpmlText = '<?echo str_replace("\n", "", str_replace('\'', '\\\'', $opmldata))?>';
        <?} else {?>
        var initialOpmlText = initialOpmltext;
        <?}?>

        $(document).ready (function () {
            //Save the outline
            $('#btnOpmlSave').click(function() {
                //Grab the current title
                title = $('.divOutlineTitle input.title').val();

                //Get a file name
                if( filename == "" || (title != lasttitle) ) {
                    if( title != "" ) {
                        filename = title.replace(/\W/g, '').substring(0,20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
                    } else {
                        filename = bufilename;
                    }
                }

                //Set a title
                opSetTitle( title );
                lasttitle = title;

                //Store the xml data
                var opml = opOutlineToXml();

                //Make the ajax call
                $.ajax({
                    type: 'POST',
                    url: '/cgi/in/save.opml',
                    data: {
                        "opml": opml,
                        "filename": filename
                    },
                    dataType: "json",
                    beforeSubmit:  function() {
                        $('.imgSpinner').show();
                        $('#btnOpmlSave').attr('disabled', true);
                    },
                    success: function(data) {
                        showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 5);
                        $('.imgSpinner').hide();
                        $('#btnOpmlSave').attr('disabled', false);
                    }
                });

                return false;
            });

            //Load up the outline
            $("#outliner").concord ({
                "prefs": {
                    "outlineFont": "Georgia",
                    "outlineFontSize": 18,
                    "outlineLineHeight": 24,
                    "renderMode": false,
                    "readonly": false,
                    "typeIcons": appTypeIcons
                },
            });
            opXmlToOutline (initialOpmlText);
            title = opGetTitle();
            $('.divOutlineTitle input.title').val(title);
        });
    </script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>

<body id="bodyEditOutline">
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>


<div class="row" id="divEditOutline">

<?if(s3_is_enabled($g_uid) || sys_s3_is_enabled()) {?>
    <div class="divOutlineTitle">
        <img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnOpmlSave" class="btn btn-success">Save</button>
        as <input class="title" placeholder="Title" type="text" />
    </div>
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
