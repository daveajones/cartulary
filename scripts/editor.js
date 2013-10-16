$(document).ready(function () {
    var outliner = $('#outliner');
    var chkToggleRender = $('.rendertoggle');

    //Save button
    $('#btnOpmlSave').click(function () {
        //Grab the current title
        title = $('.divOutlineTitle input.title').val();

        //Get a file name
        if (filename == "" || (title != lasttitle)) {
            if (title != "") {
                filename = title.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
            } else {
                filename = bufilename;
            }
        }

        //Set a title
        opSetTitle(title);
        lasttitle = title;

        //Store the xml data
        var opml = opOutlineToXml();

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/in/save.opml',
            data: {
                "opml": opml,
                "filename": filename,
                "title": title
            },
            dataType: "json",
            beforeSubmit: function () {
                $('.imgSpinner').show();
                $('#btnOpmlSave').attr('disabled', true);
            },
            success: function (data) {
                url = data.url;
                updateOutlineInfo(url, data.html);

                showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                $('.imgSpinner').hide();
                $('#btnOpmlSave').attr('disabled', false);
            }
        });

        return false;
    });

    //Open by Url button
    $('#openUrl').click(function() {
        bootbox.prompt("What url to open?", function(result) {
            if (result !== null) {
                window.location = "/editor?url=" + result;
            }
        });

        return false;
    });

    //Display outline info
    function updateOutlineInfo(url, html) {
        $('div.outlineinfo').html('');
        if (url != "") {
            $('div.outlineinfo').html('<a href="' + url + '">OPML</a>');
            if( html != "") {
                $('div.outlineinfo').append('<br/><a href="' + html + '">HTML</a>');
            }
        }
        return true;
    }

    //Toggle render mode
    chkToggleRender.change(function () {
        if ( $(this).is(':checked') ) {
            console.log('T: ' + outliner.concord().op.setRenderMode(true));
        } else {
            console.log('F: ' + outliner.concord().op.setRenderMode(false));
        }
    });

    //Load up the outline
    outliner.concord({
        "prefs": {
            "outlineFont": "Georgia",
            "outlineFontSize": 18,
            "outlineLineHeight": 24,
            "renderMode": chkToggleRender.is(':checked'),
            "readonly": false,
            "typeIcons": appTypeIcons
        },
    });
    opXmlToOutline(initialOpmlText);
    title = opGetTitle();
    $('.divOutlineTitle input.title').val(title);
    chkToggleRender.trigger('change');
    updateOutlineInfo(url, "");
    if( badurl ) {
        showMessage('Parse error. Please check the url.', false, 5);
    }
});