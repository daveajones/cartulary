//Reloads the post list with an ajax call
function loadMedia(elDiv, elTemplate) {
    $(elDiv).empty();
    $(elDiv).append('<center><p>Loading media...</p><img src="/images/spinner.gif" alt="" /></center>');
    $.getJSON("/cgi/out/list.media.json?start=0&max=100&type=1", function (response) {
        console.log(response);

        if (response.status == "true") {
            $(elDiv).empty();
            //$(elTemplate).tmpl(data.data).appendTo(elDiv);

            for( i=0 ; i <= response.data.items.length ; i++ ) {

                if( isImage(response.data.items[i].url, response.data.items[i].mimetype) ) {
                    $(elDiv).append("<img src='"+response.data.items[i].url+"'><br>");
                }

            }

            // //Get rid of tiny images
            // $(elDiv + ' .pinenclosure img').load(function () {
            //     if ($(this).width() < 90 || $(this).height() < 90) {
            //         $(this).parent().parent().parent().remove();
            //         return false;
            //     }
            // });
            // $(elDiv + ' .pinenclosure img').ready(function () {
            //     if ($(this).width() < 90 || $(this).height() < 90) {
            //         $(this).parent().parent().parent().remove();
            //         return false;
            //     }
            // });
            //
            // //Go masonry
            // if (platform != "mobile") {
            //     $(function () {
            //         var $container = $(elDiv + ' .pinboard');
            //         $container.imagesLoaded(function () {
            //             $container.masonry({
            //                 itemSelector: '.pin',
            //                 isFitWidth: true
            //             });
            //         });
            //     });
            //
            //     //Modalize the enclosure links
            //     $(elDiv + ' .pinenclosure a').unbind('click');
            //     $(elDiv + ' .pinenclosure a').click(function () {
            //         if ($(this).children('img').length > 0) {
            //             openMediaShadowbox($(this).children('img'));
            //             return false;
            //         }
            //     });
            // }

        } else {
            console.log("no data returned");
            $(elDiv).empty();
            $(elDiv).append("<center class='well'>Error retrieving JSON data: [" + data.description + "]</center>");
        }
    });

    return (true);
}

//Gets an abbreviated title for cleaner display in the post list
function getShortTitle(contentString) {
    cleanContent = contentString.replace(/\W/g, '');
    shortContent = cleanContent.substring(0, 20);

    return (shortContent);
}

//Proceed with this when the page is ready
$(document).ready(function () {
    //Populate the user management section
    loadMedia('#divMedia', '#media-template');

});
