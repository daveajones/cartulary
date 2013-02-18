    <div id="stream-header">
        <!-- <a id="stream-view" href="#">Switch to collapsed view</a> -->
        <div id="stream-updated">
          <span class="river-timestamp">Updated <span class="time">${dateFormat(metadata.whenGMT, 'timeDate')}</span></span>
          <span class="river-stats">Items: <span id="spnItemCount"></span> <a href="${River.settings.url}">JSON</a>
            <a id="stream-filter" href="#"><img class="icon-filter" src="/images/blank.gif" alt="" /></a>
          </span>
        </div>
    </div>

    <a id="stream-notice" href="#"></a>

    <div id="stream-filterbox" style="display:none;">
    <u>River Filter</u><br />
    <input class="chkOutlineFilterAll" type="checkbox" checked="checked" data-id="all" /> All<br/>
    <input class="chkOutlineFilter" type="checkbox" checked="checked" data-id=".sopml" /> People<br/>
    <input class="chkOutlineFilter" type="checkbox" checked="checked" data-id="" /> Non-list<br/>
    <br/>
    <?
       $outlines = get_outlines($uid,$max);
       foreach($outlines as $outline) {
         if($outline['type'] != "sopml") {
         ?><input class="chkOutlineFilter" type="checkbox" checked="checked" data-id=".<?echo $outline['id']?>" /> <?echo $outline['title']?><br/><?
         }
       }
    ?>
    </div>

    <div id="stream-items">

    {{each(f,feed) updatedFeeds.updatedFeed}}

        <div class="section{{if linkedOutlineId}} ${linkedOutlineId}{{/if}}{{if feedHidden == 1}} elHidden{{/if}}{{if linkedOutlineType}} ${linkedOutlineType}{{/if}}">
            <div class="header">
                <?if( $cg_terror == 1) {?><div class="pull-right"><button class="btn btn-mini btn-danger" onclick="javascript:alert('Nice job douchebag!');">Report this post<br/>as terrorism.</button></div><?}?>
                <h2>
                    <span class="header-tools">
                    {{if feedSticky == 1}}<a class="aFeedUnSticky" href="#" data-id="${feedId}"><img class="icon-feed-unsticky" src="/images/blank.gif" alt="" /></a>
                    {{else}}<a class="aFeedSticky" href="#" data-id="${feedId}"><img class="icon-feed-sticky" src="/images/blank.gif" alt="" /></a>
                    {{/if}}
                    </span>
                    {{if websiteUrl}}<a href="${websiteUrl}">{{/if}}${feedTitle}{{if websiteUrl}}</a>{{/if}}
                </h2>
                <span class="time">${dateFormat(whenLastUpdate, 'timeDate')}</span>
            </div>

        {{each(i,item) item}}

            <div class="article{{if item.sticky}} sticky{{/if}} fulltext" id="${item.id}" data-index="${item.index}">
                {{if avatarUrl}}{{if i < 1}}<img class="riverminitar" src="${avatarUrl}" src="/images/blank.gif" alt="" />{{/if}}{{/if}}
                {{each(e,enclosure) item.enclosure}}
                  {{if River.methods.isAvatar(enclosure.url)}}
                    <img class="riverminitar" src="${enclosure.url}" src="/images/blank.gif" alt="" title="Enclosure: ${e}."/>
                  {{/if}}
                {{/each}}
                <div class="header">
                    <h3>
                    <?if($prefs['riverheadlinecart'] == 1) {?>
                    {{if item.permaLink || item.link}}<a class="articlelink" name="${item.id}" href="<?echo $cartulizecgi?>?title=${encodeURIComponent(item.title)}&url=${encodeURIComponent(item.link) || encodeURIComponent(item.permaLink)}&surl=${encodeURIComponent(feedUrl)}&stitle=${encodeURIComponent(feedTitle)}" rel="external">{{/if}}
                    <?} else {?>
                    {{if item.permaLink || item.link}}<a class="articlelink" name="${item.id}" href="${item.permaLink || item.link}" rel="external">{{/if}}
                    <?}?>
                    {{if item.title}}
                        ${item.title}
                    {{else}}
                        ${River.methods.newGetText(item.body)}
                    {{/if}}
                    {{if item.permaLink || item.link}}</a>{{/if}}
                    </h3>
                </div>

            {{if item.title && item.body}}
                <div class="description">
                    ${River.methods.newGetText(item.body)}
                    {{if Hidepics == false && River.methods.convertYoutube(item.link) != false}}
                        <br/><br/><iframe class="bodyvid" src="${River.methods.convertYoutube(item.link)}" frameborder="0" allowfullscreen></iframe>
                    {{/if}}
                </div>
            {{/if}}

                <div class="enclosureview">
		    {{each(e,enclosure) item.enclosure}}
                        {{if River.methods.isImage(enclosure.url, enclosure.type) && (Hidebigpics == false || enclosure.length < 50000) && River.methods.isAvatar(enclosure.url) == false}}
                            <a href="${enclosure.url}">
			    {{if River.methods.countEnclosuresOfType(item.enclosure, 'image') == 2}}
                              <img class="encpicture2" src="${enclosure.url}" alt="" />
			    {{else River.methods.countEnclosuresOfType(item.enclosure, 'image') == 3}}
                              <img class="encpicture3" src="${enclosure.url}" alt="" />
			    {{else River.methods.countEnclosuresOfType(item.enclosure, 'image') == 4}}
                              <img class="encpicture4" src="${enclosure.url}" alt="" />
			    {{else River.methods.countEnclosuresOfType(item.enclosure, 'image') >= 5}}
                              <img class="encpictures" src="${enclosure.url}" alt="" />
			    {{else}}
                              <img class="encpicture" src="${enclosure.url}" alt="" />
			    {{/if}}
                            </a>
                        {{else River.methods.isAudio(enclosure.url, enclosure.type)}}
                            <audio class="encaudio" src="${enclosure.url}" preload="metadata" controls></audio>
                            <div class="enclosure ${River.methods.getMediaType(enclosure.type)}"><a href="${enclosure.url}">Download enclosure{{if enclosure.type && enclosure.length}} (${enclosure.type}, ${River.methods.getEnclosureSize(enclosure.length)}){{/if}}</a></div>
                        {{else River.methods.isVideo(enclosure.url, enclosure.type)}}
                            <video class="encvideo" src="${enclosure.url}" preload="metadata" controls></video>
                            <div class="enclosure ${River.methods.getMediaType(enclosure.type)}"><a href="${enclosure.url}">Download enclosure{{if enclosure.type && enclosure.length}} (${enclosure.type}, ${River.methods.getEnclosureSize(enclosure.length)}){{/if}}</a></div>
			<?if( $platform != "mobile" ) {?>
                        {{else River.methods.isIframe(enclosure.url, enclosure.type)}}
                            <iframe src="${enclosure.url}" frameborder="0" allowfullscreen></iframe>
			<?}?>
                        {{/if}}
		    {{/each}}
		</div>

                <div class="footer" style="clear:both;">
		    <div class="actionwrap">
                    <div class="time">${River.methods.prettyDate(item.pubDate)}{{if item.sourceurl}}<span class="source"> via: <a href="${item.sourceurl}">${item.sourcetitle}</a> | <a class="aSubscribe" data-sourceurl="${encodeURIComponent(item.sourceurl)}" href="#">Subscribe</a></span>{{/if}}</div>
                    <div class="actions">
                        <?if($prefs['riverheadlinecart'] != 1) {?>
                          <div class="cartform"><a class="cartlink" href="<?echo $cartulizecgi?>?title=${encodeURIComponent(item.title)}&url=${encodeURIComponent(item.link)}&surl=${encodeURIComponent(feedUrl)}&stitle=${encodeURIComponent(feedTitle)}" rel="external nofollow"><img class="icon-book-small" src="/images/blank.gif" alt="" /></a></div>
                        <?}?>
                        <?if(!empty($prefs['linkblog'])) {?>
			  <div><a href="<?echo $prefs['linkblog']?>/?description=${encodeURIComponent(item.title)}&link=${encodeURIComponent(item.link)}" rel="external nofollow" target="_blank">RT</a></div>
			<?} else {?>
                          <div class="rtriverform">
			  <form id="frm${item.id}" action="<?echo $microblogpage?>" method="post" target"_new">
			  <a class="rtlink" href="#" onclick="javascript:document.getElementById('frm${item.id}').submit();return false;"><img class="icon-retweet" src="/images/blank.gif" alt="" /></a>
			  {{if item.title}}<input type="hidden" name="title" value="${item.title}" />{{/if}}
			  {{if item.title || item.description || item.body}}<input type="hidden" name="description" value="${item.title || item.description || item.body}" />{{/if}}
			  {{if item.permaLink || item.link}}<input type="hidden" name="link" value="${item.permaLink || item.link}" />{{/if}}
			  {{each(e,enclosure) item.enclosure}}
                            <input type="hidden" name="extenclosure[${e}][url]" value="${enclosure.url}" />
                            <input type="hidden" name="extenclosure[${e}][type]" value="${enclosure.type}" />
                            <input type="hidden" name="extenclosure[${e}][length]" value="${enclosure.length}" />
                          {{/each}}
			  <input type="hidden" name="source[url]" value="${feedUrl}" />
			  <input type="hidden" name="source[title]" value="${feedTitle}" />
			  </form>
                          </div>
			<?}?>
                {{if item.sticky}}<a class="aUnSticky" href="#" data-id="${item.id}"><img class="icon-unsticky" src="/images/blank.gif" alt="" /></a>{{/if}}
                    {{if River.settings.social === true && (item.permaLink || item.link)}}
                        <div><a href="http://twitter.com/share?text={{if item.title}}${item.title}{{else}}${River.methods.getText(item.body)}{{/if}}&amp;url={{if item.permaLink}}${encodeURIComponent(item.permaLink)}{{else}}${encodeURIComponent(item.link)}{{/if}}" rel="external nofollow" target="_blank" title="Share this on Twitter">Tweet</a></div>
                        <div><a href="http://www.facebook.com/sharer.php?t={{if item.title}}${item.title}{{else}}${River.methods.getText(item.body)}{{/if}}&amp;u={{if item.permaLink}}${encodeURIComponent(item.permaLink)}{{else}}${encodeURIComponent(item.link)}{{/if}}" rel="external nofollow" target="_blank" title="Share this on Facebook">Share</a></div>
                    {{/if}}
                    {{if item.comments}}
                        <div><a href="${item.comments}" rel="external nofollow" target="_blank">Comment</a></div>
                    {{/if}}
                    </div>
		    </div>
                    <div class="footclear"></div>
                </div>
            </div>

        {{/each}}

        </div>

    {{/each}}
   </div>
