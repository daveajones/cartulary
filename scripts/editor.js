$(document).ready(function () {
    var hoverTimer = null;
    var outliner = $('#outliner');
    var sheetopen = $('#divEditSheetOpen');
    var chkToggleRender = $('.rendertoggle');
    var chkDisqusInclude = $('.menuDisqusToggle');
    var includeDisqus = false;
    var menubar = $('#menubarEditor');
    var elTitle = $('.divOutlineTitle input.title');


    //New button
    menubar.find('.menuNew').click(function() {
        window.location = "/editor";
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
                "disqus" : includeDisqus,
                "title": title
            },
            dataType: "json",
            beforeSend: function () {
                //Disable the save button and show a spinner
                menubar.find('.menuSave').attr('disabled', true);
                menubar.find('.menuSave').html('<i class="icon-spinner"></i> Saving...');
            },
            success: function (data) {
                //Show returned info and re-enable the save button
                url = data.url;
                htmlurl = data.html;
                updateOutlineInfo(url, data.html);

                showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                menubar.find('.menuSave').html('Save');
                menubar.find('.menuSave').attr('disabled', false);
            }
        });

        return false;
    });

    //Publish button
    menubar.find('.menuPublish').click(function () {
       if( isEmpty(url) ) {
           showMessage("You must save the document first.", false, 3);
           return(false);
       }
       $('#divEditOutline .ouitem').remove();
       $('#divEditOutline').append('<div class="ouitem hide"><div class="header"><a class="articlelink" href="' + htmlurl + '">' + title + '</a></div></div>');
       $('#divEditOutline .ouitem').append('<div class="footer"><span class="source"><a class="articlelink" href=""></a></span></div>');
       $('#divEditOutline .ouitem .footer').append('<span class="origin">' + url + '</span>');
       newMicroblogPostWindow( '#divEditOutline .ouitem' );
       return(false);
    });

    //Open button
    menubar.find('.menuOpen').click(function() {

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.recentfiles',
            dataType: "json",
            success: function (data) {
                //Clear the table for new data
                $('.recentfilesopen').empty();

                //Iterate
                $.each(data.files, function(i, item) {
                    var re = /\.$/;
                    var newtitle = item.title.replace(re, "").toLowerCase();
                    $('.recentfilesopen').append('<li>You worked on "<a href="/editor?url='+ item.url +'">' + newtitle + '</a>" ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>')
                });

                //Open the dropdown sheet
                sheetopen.toggleClass('open');
            }
        });

        return false;
    });

    //Close sheet button
    sheetopen.find('a.sheetclose').click( function() {
        sheetopen.toggleClass('open');
    });

    //Open by url button
    sheetopen.find('a.openbyurl').click( function() {
         bootbox.prompt("What url to open?", function(result) {
            if (result !== null) {
                window.location = "/editor?url=" + result;
            }
         });
    });

    //Type dropdown button
    menubar.find('.menuType .menuTypeSelection').click(function() {
        var thistype = $(this).attr('data-type');

        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + $(this).html() + ') <b class="caret"></b>');
        if( thistype == 'link' || thistype == 'redirect' ) {
            bootbox.prompt("What url to point to?", function(result) {
                if (result !== null) {
                    opSetOneAtt('type', thistype);
                    opSetOneAtt('url', result);
                }
            });
        } else {
            opSetOneAtt('type', thistype);
        }
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

    //Toggle disqus comments in html
    chkDisqusInclude.click(function () {
        if ( $(this).parent().hasClass('active') ) {
            $(this).parent().removeClass('active');
            includeDisqus = false;
        } else {
            $(this).parent().addClass('active');
            includeDisqus = true;
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
        "callbacks": {
            "opCursorMoved": opCursorMovedCallback
        },
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
    if( typeof(rootnodetype) == "undefined" )  {  rootnodetype = "not set";  }
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


//When a new node is clicked on, this callback fires
function opCursorMovedCallback (op) {
    var nodetype = op.attributes.getOne('type');

    if( typeof(nodetype) == "undefined" )  {  nodetype = "not set";  }
    $('#menubarEditor').find('.menuType > a.dropdown-toggle').html('Type (' + nodetype + ') <b class="caret"></b>');
    return true;

}
