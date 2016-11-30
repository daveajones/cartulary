$(document).ready(function () {
    console.log("SECTION: " + gSection);
    var enclist = new Array();

    //Set the datestamp
    $('#divEnclosures .datestamp').val(gDatestamp);

    //Reveal the enclosure section upon change
    $('#fileMobile').change(function () {
        $('#divEnclosures').show();
        $('#divEnclosures #spnQueueText').text('File selected: ' + $('#fileMobile').val());
    });

    //The enclosure attachment button click handler
    $('#btnAttachFile').bind('click', function () {
        $('#divUpload').show();
    });

    //Start an enclosure upload
    if (gDevice != "android") {
        $('#aUploadTrigger').bind('click', function () {
            $('#divEnclosures').show();
            $('#file_upload').uploadifive('upload');
        });
    }

    //Attach removers to extra elements
    $('.aRemoveListItem').click(function () {
        $(this).parent('li').remove();
        return false;
    });

    //Expose drop zone when a drag and drop comes in
    if (gSection != "Microblog") {
        $('body').on('dragenter', function () {
            if ($('#mdlMicroblogPost').is(':visible')) {
                modalFullHeight('#mdlMicroblogPost', false);
                showEnclosures();
            }
        });
    } else if (gSection == "Microblog") {
        $('body').on('dragenter', function () {
            showEnclosures();
        });
    }

    //Uploadifive handler
    if (gDevice != "android") {
        $(function () {
            $('#file_upload').uploadifive({
                'auto': true,
                'method': 'post',
                'dnd': true,
                'queueID': 'queue',
                'uploadScript': '/cgi/in/upload',
                'simUploadLimit': 1,
                'formData': {
                    'datestamp': gDatestamp
                },
                'onSelect': function (queue) {
                    $('#divEnclosures').show();
                },
                'onAddQueueItem': function (file) {
                    console.log("onAddQueueItem(): " + file.name);
                    console.log(enclist);
                    if ($.inArray(file.name, enclist) > -1) {
                        $('#file_upload').uploadifive('cancel', file, true);
                    } else {
                        enclist.push(file.name);
                    }
                },
                'onClearQueue': function (queue) {
                    enclist = [];
                    $('input.postenclosure').remove();
                },
                'onCancel': function (file) {
                    $("input.postenclosure[data-filename='" + file.name + "']").remove();
                    enclist.splice($.inArray(file.name, enclist), 1);
                },
                'onUploadComplete': function (file, data) {
                    var jdata = $.parseJSON(data);
                    $('.complete .filename:contains("' + file.name + '")').each(function (index) {
                        if (isImage(jdata.url)) {
                            $(this).parent().append('<a href="' + jdata.url + '" title="Click to embiggen."><img class="imgenclosure" src="' + jdata.url + '" alt="" /></a>');
                            $(this).parent().children('span.fileinfo').hide();
                        }
                        $(this).html('<a href="' + jdata.url + '" title="Click to embiggen.">Link to File.</a>');
                        $(this).parent().append('<input type="hidden" class="postenclosure" data-filename="' + file.name + '" name="enclosure[]" />');
                        $(this).parent().children('input').val(data);

                        enclist.push(file.name);
                    });
                }
            })
        });
    }
});

//Unhide the enclosure section
function showEnclosures() {
    $('#divEnclosures').show();
}
