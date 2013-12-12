$(document).ready(function () {
    var hoverTimer = null;
    var outliner = $('#outliner');
    var chkToggleRender = $('.rendertoggle');
    var menubar = $('#menubarEditor');
    var elTitle = $('.divOutlineTitle input.title');


    //New button
    menubar.find('.menuNew').click(function() {
        mode = "";
        url = "";
        title = "";
        lasttitle = "";
        filename = '';
        bufilename = title.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
        elTitle.val('').focus();
        opXmlToOutline(initialOpmltext);
        updateOutlineInfo(url, "");

        //Set root node type menu text
        getRootNodeType();
    });

    //Save button
    menubar.find('.menuSave').click(function () {
        //Grab the current title
        title = elTitle.val();

        //Get a file name
        if (filename == "") {
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
                "mode" : mode,
                "filename": filename,
                "title": title
            },
            dataType: "json",
            beforeSend: function () {
                //Disable the save button and show a spinner
                $('#btnOpmlSave').attr('disabled', true);
                $('#btnOpmlSave').html('<i class="icon-spinner"></i>');
            },
            success: function (data) {
                //Show returned info and re-enable the save button
                url = data.url;
                updateOutlineInfo(url, data.html);

                showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                $('#btnOpmlSave').html('Save');
                $('#btnOpmlSave').attr('disabled', false);
            }
        });

        return false;
    });


    //Open button
    menubar.find('.menuOpen').click(function() {
        bootbox.prompt("What url to open?", function(result) {
            if (result !== null) {
                window.location = "/editor?url=" + result;
            }
        });

        return false;
    });


    //Type dropdown button
    menubar.find('.menuType .menuTypeSelection').click(function() {
        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + $(this).html() + ') <b class="caret"></b>');
        opFirstSummit();
        opSetOneAtt('type', $(this).attr('data-type'));
        return true;
    });


    //Toggle render mode
    chkToggleRender.click(function () {
        if ( $(this).parent().hasClass('active') ) {
            console.log('T: ' + outliner.concord().op.setRenderMode(false));
            $(this).parent().removeClass('active');
        } else {
            console.log('F: ' + outliner.concord().op.setRenderMode(true));
            $(this).parent().addClass('active');
        }
    });


    //Toolbox buttons
    menubar.find('.menuAddLink').click( function() {
       editorToolAddLink();
       return false;
    });


    //Handle opacity on focus change
    elTitle.on("focus", function() {
        clearTimeout(hoverTimer);
        $('.divOutlineTitle').removeClass('dim');
    });
    elTitle.on("blur", function() {
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(function() {
            $('.divOutlineTitle').addClass('dim');
        }, 3000);
    });


    //Full opacity on title hover
    $('.divOutlineTitle').hover(
        function() {
            clearTimeout(hoverTimer);
            $('.divOutlineTitle').removeClass('dim');
        },
        function() {
            if( !$('.divOutlineTitle input.title').is(':focus') ) {
                hoverTimer = setTimeout(function() {
                    $('.divOutlineTitle').addClass('dim');
                }, 3000);
            }
        }
    )

    //Load up the outline
    outliner.concord({
        "prefs": {
            "outlineFont": "Calibri",
            "outlineFontSize": 18,
            "outlineLineHeight": 24,
            "renderMode": chkToggleRender.parent().hasClass('active'),
            "readonly": false,
            "typeIcons": appTypeIcons
        },
    });
    opXmlToOutline(initialOpmlText);
    title = opGetTitle();


    //Refresh the outliner info pane
    updateOutlineInfo(url, "");
    if( badurl == true ) {
        showMessage('Parse error. Please check the url.', false, 5);
    }

    //Set up the root node type correctly
    getRootNodeType();

    //Dim the title area when not in use
    hoverTimer = setTimeout(function() {
        $('.divOutlineTitle').addClass('dim');
    }, 7000);


    //Set a title
    if( title == "Untitled") {
        elTitle.val('');
    } else {
        elTitle.val(title);
    }


    //Hot keys
    key('ctrl+y', function() {
        editorToolAddLink();
    })


});


function editorToolAddLink() {
    var outliner = $('#outliner');
    bootbox.prompt("Type the target link.", function(result) {
        if (result !== null) {
            console.log(result);
            outliner.concord().op.link(result);
        }
    });
    return false;
}


//Get root node type and set menu text
function getRootNodeType() {
    var rootnodetype;

    opFirstSummit();
    rootnodetype = opGetOneAtt('type');
    if( typeof(rootnodetype) == "undefined" )  {  rootnodetype = "outline";  }
    $('#menubarEditor').find('.menuType > a.dropdown-toggle').html('Type (' + rootnodetype + ') <b class="caret"></b>');
    return true;
}


//Display outline info
function updateOutlineInfo(url, html) {
    var elOutlineinfo = $('#menubarEditor').find('.outlineinfo');

    elOutlineinfo.html('');
    if (url != "") {
        elOutlineinfo.html('<li><a target="_blank" href="' + url + '">OPML</a></li>');
        if( html != "") {
            elOutlineinfo.append('<li><a target="_blank" href="' + html + '">HTML</a></li>');
        }
    }
    return true;
}