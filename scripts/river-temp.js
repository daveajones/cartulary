<!-- /*This is the river template*/ -->
<div id="stream-items">
	<ul class="stream-list">

    <!-- /*Each feed is parsed*/ -->
    {{each(f,feed) updatedFeeds.updatedFeed}}
		{{if linkedOutlineType}}
			{{if linkedOutlineType == 'sopml'}}
				{{if River.methods.prependActiveFeed(linkedOutlineId, ownerName, linkedOutlineUrl, avatarUrl, "person")}}{{/if}}
			{{else}}
				{{if River.methods.appendActiveFeed(feedId, feedTitle, feedUrl, websiteUrl, "feed")}}{{/if}}
			{{/if}}
		{{else}}
			{{if River.methods.appendActiveFeed(feedId, feedTitle, feedUrl, websiteUrl, "feed")}}{{/if}}
		{{/if}}
    	<!-- /*Each item is parsed*/ -->
        {{each(i,item) item}}
            <li class="article{{if item.sticky}}{{if River.methods.alreadyUnsticky(item.id)}} noshow{{else}} sticky{{/if}}{{/if}}{{if item.enclosure && item.enclosure.length > 0}} with-enclosures{{/if}} fulltext {{if linkedOutlineType}}{{if linkedOutlineType == 'sopml'}}${linkedOutlineId}{{else}}${feedId}{{/if}}{{else}}${feedId}{{/if}}" id="${item.id}" data-index="${item.index}" data-feedid="${feedId}">
				<div class="postinfo hide"
					 data-url="${item.permaLink || item.link}" 
					 data-title="${item.title || item.body}"
					 data-sourceurl="${item.sourceurl || feedUrl}"
					 data-sourcetitle="${item.sourcetitle || feedTitle}"
					 data-origin="{{if item.origin}}${item.origin}{{/if}}">
				</div>

                {{if item.sticky}}<a class="aUnSticky" href="#" data-id="${item.id}" data-feedid="{{if linkedOutlineType}}{{if linkedOutlineType == 'sopml'}}${linkedOutlineId}{{else}}${feedId}{{/if}}{{else}}${feedId}{{/if}}"><img class="icon-unsticky" src="/images/blank.gif" alt="" /></a>{{/if}}
	            {{if avatarUrl}}
				{{if i < 1}}
					{{if linkedOutlineUrl}}<a class="sopmllink" href="#mdlSocialOutlineView" label="View Social Outline" data-href="${linkedOutlineUrl}">{{/if}}
					<img class="riverminitar" src="${avatarUrl}" alt="" />
					{{if linkedOutlineUrl}}</a>{{/if}}
				{{/if}}
				{{/if}}

		    	<!-- /*Each enclosure is parsed looking for an avatar*/ -->
                {{each(e,enclosure) item.enclosure}}
                  {{if River.methods.isAvatar(enclosure.url)}}
    	              <img class="riverminitar" src="${enclosure.url}" alt="" title="Enclosure: ${e}."/>
                  {{/if}}
                {{/each}}

		    	<!-- /*The article header*/ -->
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

	            {{if item.author}}<div class="byline">by: <span class="author">${item.author}</span></div>{{/if}}

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
                        {{if River.methods.isImage(enclosure.url, enclosure.type) && (Hidebigpics == false || enclosure.length < 50000) && Hidepics == false && River.methods.isAvatar(enclosure.url) == false}}
                            <a href="${enclosure.url}">
			    {{if River.methods.countEnclosuresOfType(item.enclosure, 'image') == 2}}
                              <img class="encobj enclosurepic encpicture2" src="${enclosure.url}" data-type="${enclosure.type}" data-length="${enclosure.length}" alt="" />
			    {{else River.methods.countEnclosuresOfType(item.enclosure, 'image') == 3}}
                              <img class="encobj enclosurepic encpicture3" src="${enclosure.url}" data-type="${enclosure.type}" data-length="${enclosure.length}" alt="" />
			    {{else River.methods.countEnclosuresOfType(item.enclosure, 'image') == 4}}
                              <img class="encobj enclosurepic encpicture4" src="${enclosure.url}" data-type="${enclosure.type}" data-length="${enclosure.length}" alt="" />
			    {{else River.methods.countEnclosuresOfType(item.enclosure, 'image') >= 5}}
                              <img class="encobj enclosurepic encpictures" src="${enclosure.url}" data-type="${enclosure.type}" data-length="${enclosure.length}" alt="" />
			    {{else}}
                              <img class="encobj enclosurepic encpicture" src="${enclosure.url}" data-type="${enclosure.type}" data-length="${enclosure.length}" alt="" />
			    {{/if}}
                            </a>
                        {{else River.methods.isAudio(enclosure.url, enclosure.type)}}
                            <audio class="encobj encaudio" src="${enclosure.url}" preload="metadata" controls data-type="${enclosure.type}" data-length="${enclosure.length}"></audio>
                            <div class="enclosure ${River.methods.getMediaType(enclosure.type)}"><a href="${enclosure.url}">Download enclosure{{if enclosure.type && enclosure.length}} (${enclosure.type}, ${River.methods.getEnclosureSize(enclosure.length)}){{/if}}</a></div>
                        {{else River.methods.isVideo(enclosure.url, enclosure.type) && Hidepics == false}}
                            <video class="encobj encvideo" src="${enclosure.url}" preload="metadata" controls data-type="${enclosure.type}" data-length="${enclosure.length}"></video>
                            <div class="enclosure ${River.methods.getMediaType(enclosure.type)}"><a href="${enclosure.url}">Download enclosure{{if enclosure.type && enclosure.length}} (${enclosure.type}, ${River.methods.getEnclosureSize(enclosure.length)}){{/if}}</a></div>
			<?if( $device != "iphone" && $device != "ipad" ) {?>
                        {{else River.methods.isIframe(enclosure.url, enclosure.type) && Hidepics == false}}
                            <div class="encobj enciframe inactive hide" data-src="${enclosure.url}" data-type="${enclosure.type}" data-length="${enclosure.length}" data-frameborder="0" allowfullscreen>
                              <div class="play"><img class="icon-play" src="/images/blank.gif" alt="" /> Click to load...</div>
                            </div>
			<?}?>
                        {{/if}}
		    {{/each}}
		</div>

                <div class="footer" style="clear:both;">
		    <div class="actionwrap">
					<a class="jumpTop pull-left" title="Jump back to the top of the page." href="#"><img class="icon-jumptop" src="/images/blank.gif"></a>

                    <div class="time">
                      ${River.methods.prettyDate(item.pubDate)}
                      {{if item.sourceurl}}
                        <span class="source"> via: <a href="${item.sourceurl}">${item.sourcetitle}</a> | <a class="aSubscribe" data-sourceurl="${encodeURIComponent(item.sourceurl)}" href="#">Subscribe</a></span>
				      {{else}}
                        <span class="source"> | <a href="${feedUrl}">${feedTitle}</a></span>
                      {{/if}}
                      {{if item.origin}}<span class="origin hide">${item.origin}</span>{{/if}}
                    </div>

                    <div class="actions">
                        <?if( $g_prefs['riverheadlinecart'] != 1 ) {?>
                          <?if( $g_prefs['cartinriver'] == 1 && $g_platform['mobile'] ) {?>
                            <div class="cartform action"><a class="_cartlink" href="<?echo $cartulizecgi?>?title=${encodeURIComponent(item.title)}&url=${encodeURIComponent(item.link)}&surl=${encodeURIComponent(feedUrl)}&stitle=${encodeURIComponent(feedTitle)}" rel="external nofollow"><img class="icon-bookmark" src="/images/blank.gif" alt="" /></a></div>
						  <?} else {?>
                            <a class="cartlink" href="#" data-id="${item.id}" label="Cartulize Article"><img class="icon-bookmark" src="/images/blank.gif" alt="" /></a>
                            <!-- <div class="cartform action"><a class="cartlink" href="#mdlShowArticle" data-id="${item.id}" data-toggle="modal" label="Cartulize Article" data-href="<?echo $cartulizecgi?>?title=${encodeURIComponent(item.title)}&url=${encodeURIComponent(item.link)}&surl=${encodeURIComponent(feedUrl)}&stitle=${encodeURIComponent(feedTitle)}"><img class="icon-bookmark" src="/images/blank.gif" alt="" /></a></div> -->
						  <?}?>
                        <?}?>

                        <?if(!empty($prefs['linkblog'])) {?>
			  <div><a href="<?echo $prefs['linkblog']?>/?description=${encodeURIComponent(item.title)}&link=${encodeURIComponent(item.link)}" rel="external nofollow" target="_blank">RT</a></div>
			<?} else {?>
			  <a class="mblink rtgo action" href="#" data-id="${item.id}"><img class="icon-share" src="/images/blank.gif" alt="" /></a>
<!--                          <div class="rtriverform">
			  <form id="frm${item.id}" action="<?echo $microblogpage?>" method="post" target"_new">
			  <a class="rtlink rtgo" href="#" onclick="javascript:document.getElementById('frm${item.id}').submit();return false;"><img class="icon-share" src="/images/blank.gif" alt="" /></a>
			  {{if item.title}}<input type="hidden" name="title" value="${item.title}" />{{/if}}
			  {{if item.title || item.description || item.body}}<input type="hidden" name="description" value="${item.title || item.description || item.body}" />{{/if}}
			  {{if item.permaLink || item.link}}<input type="hidden" name="link" value="${item.permaLink || item.link}" />{{/if}}
			  {{if item.origin}}<input type="hidden" name="origin" value="${item.origin}" />{{/if}}
			  {{each(e,enclosure) item.enclosure}}
                            <input type="hidden" name="extenclosure[${e}][url]" value="${enclosure.url}" />
                            <input type="hidden" name="extenclosure[${e}][type]" value="${enclosure.type}" />
                            <input type="hidden" name="extenclosure[${e}][length]" value="${enclosure.length}" />
                          {{/each}}
					  <input type="hidden" name="source[url]" value="${feedUrl}" />
					  <input type="hidden" name="source[title]" value="${feedTitle}" />
			  </form>
                          </div>
-->
			<?}?>
                    </div>
		    </div>
                    <div class="footclear"></div>
                </div>

	    <!-- Other items that reference this item's origin value. -->
	    {{each(i,subitem) subitem}}
		<div class="subitem{{if subitem.sticky}} sticky{{/if}}" id="${subitem.id}">
                    {{if subitem.avatarUrl}}<img class="rivermicrotar" src="${subitem.avatarUrl}" alt="" />{{/if}}
	            {{each(e,enclosure) subitem.enclosure}}
                      {{if River.methods.isAvatar(enclosure.url)}}
                        <img class="rivermicrotar" src="${enclosure.url}" alt="" title="Enclosure: ${e}."/>
                      {{/if}}
                    {{/each}}
                    {{if subitem.permaLink || subitem.link}}<a class="articlelink" name="${subitem.id}" href="${subitem.permaLink || subitem.link}" rel="external">{{/if}}
                    {{if subitem.title}}
		      {{if subitem.title == item.title}}<img class="icon-retweet" src="/images/blank.gif" alt="" />{{else}}
                        ${subitem.title}
		      {{/if}}
                    {{else}}
		      {{if subitem.body == item.body}}<img class="icon-retweet" src="/images/blank.gif" alt="" />{{else}}
                        ${River.methods.newGetText(subitem.body)}
		      {{/if}}
                    {{/if}}
                    {{if subitem.permaLink || subitem.link}}</a>{{/if}}
                    <div class="time">${River.methods.prettyDate(subitem.pubDate)}</div>
                    {{if subitem.avatarUrl}} {{else}}[from: ${subitem.feedTitle}]{{/if}}
		</div>
		<div class="footclear"></div>           
	    {{/each}}

            </li>
        {{/each}}
    {{/each}}
     </ul>
   </div>

    <!-- /*This is the stream information*/ -->
    <div id="stream-header">
        <div id="stream-updated">
          <span class="river-timestamp">Updated <span class="time">${dateFormat(metadata.whenGMT, 'timeDate')}</span></span>
          <span class="river-stats">Items: <span id="spnItemCount"></span> <a href="${River.settings.url}">JSON</a></span>
        </div>
    </div>
