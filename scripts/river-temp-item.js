<li class="article ${feed.feedId} {{if feed.linkedOutlineType == "sopml"}}${feed.linkedOutlineId}{{/if}}"
	id="${id}"
    data-url="{{if permaLink}}${permaLink}{{else link}}${link}{{/if}}"
    data-sourceurl="{{if sourceurl}}${sourceurl}{{else feed.feedUrl}}${feed.feedUrl}{{/if}}"
	data-sourcetitle="{{if sourcetitle}}${sourcetitle}{{else feed.feedTitle}}${feed.feedTitle}{{/if}}"
	data-origin="{{if origin}}${origin}{{/if}}"
	data-feedid="${feed.feedId}">


	<!-- Feed level avatar -->
	{{if feed.avatarUrl}}
		{{if feed.linkedOutlineUrl}}<a class="sopmllink" href="#mdlSocialOutlineView" label="View Social Outline" data-href="${feed.linkedOutlineUrl}">{{/if}}
			<img class="riverminitar" src="${feed.avatarUrl}" alt="" />
		{{if feed.linkedOutlineUrl}}</a>{{/if}}
	{{/if}}


   	<!-- Post level avatar -->
    {{each(e,enc) enclosure}}
	    {{if freedomController.v1.river.methods.isAvatar(enc.url)}}
    	    <img class="riverminitar" src="${enc.url}" alt="" title="Enclosure: ${e}."/>
        {{/if}}
    {{/each}}


   	<!-- Article header -->
    <div class="header">
	    <h3>
	        {{if permaLink || link}}<a class="articlelink" name="${id}" href="${permaLink || link}" rel="external" target="_blank">{{/if}}
            {{if title}}
	            ${title}
            {{else}}
                ${freedomController.v1.river.methods.getBodyText(body)}
            {{/if}}
            {{if permaLink || link}}</a>{{/if}}
        </h3>
    </div>


	<!-- Article body -->
    {{if title && body}}
	<div class="description">
        ${freedomController.v1.river.methods.getBodyText(body)}
    </div>
    {{/if}}


	<!-- Enclosure section -->
	<div class="enclosureview">
	    {{each(e,enc) enclosure}}
    	    {{if freedomController.v1.river.methods.isImage(enc.url, enc.type) && (Hidebigpics == false || enc.length < 50000) && Hidepics == false && freedomController.v1.river.methods.isAvatar(enc.url) == false}}
				<!-- Picture -->
	            <a href="${enc.url}">
			    {{if freedomController.v1.river.methods.countEnclosuresOfType(enclosure, 'image')   == 2}}
	                <img class="encobj enclosurepic encpicture2" src="${enc.url}" data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}" alt="" />
			    {{else freedomController.v1.river.methods.countEnclosuresOfType(enclosure, 'image') == 3}}
                    <img class="encobj enclosurepic encpicture3" src="${enc.url}" data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}" alt="" />
			    {{else freedomController.v1.river.methods.countEnclosuresOfType(enclosure, 'image') == 4}}
                    <img class="encobj enclosurepic encpicture4" src="${enc.url}" data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}" alt="" />
			    {{else freedomController.v1.river.methods.countEnclosuresOfType(enclosure, 'image') >= 5}}
                    <img class="encobj enclosurepic encpictures" src="${enc.url}" data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}" alt="" />
			    {{else}}
                    <img class="encobj enclosurepic encpicture" src="${enc.url}" data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}" alt="" />
			    {{/if}}
                </a>
            {{else freedomController.v1.river.methods.isAudio(enc.url, enc.type)}}
				<!-- Audio -->
                <audio class="encobj encaudio" src="${enc.url}" preload="metadata" controls data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}"></audio>
                <div class="enclosure ${freedomController.v1.river.methods.getMediaType(enc.type)}">
					<a href="${enc.url}">Download enclosure{{if enc.type && enc.length}} (${enc.type}, ${freedomController.v1.river.methods.getEnclosureSize(enc.length)}){{/if}}</a>
				</div>
            {{else freedomController.v1.river.methods.isVideo(enc.url, enc.type) && Hidepics == false}}
				<!-- Video -->
				<video class="encobj encvideo" src="${enc.url}" preload="metadata" controls data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}"></video>
                <div class="enclosure ${freedomController.v1.river.methods.getMediaType(enc.type)}">
					<a href="${enc.url}">Download enclosure{{if enc.type && enc.length}} (${enc.type}, ${freedomController.v1.river.methods.getEnclosureSize(enc.length)}){{/if}}</a>
				</div>
            {{else freedomController.v1.river.methods.isIframe(enc.url, enc.type) && Hidepics == false}}
				<!-- Embed -->
				<div class="encobj enciframe inactive hide" data-src="${enc.url}" data-type="${enc.type}" data-length="${enc.length}" data-frameborder="0" allowfullscreen>
	                <div class="play"><img class="icon-play" src="/images/blank.gif" alt="" /> Click to load...</div>
                </div>
            {{/if}}
	    {{/each}}
	</div>


	<!-- Article footer -->
    <div class="footer">
		<div class="actionwrap">
			<a class="jumpTop pull-left" title="Jump back to the top of the page." href="#">
				<img class="icon-jumptop" src="/images/blank.gif">
			</a>

            <div class="time">
	            ${prettyDate(pubDate)}
                {{if sourceurl}}
                	<span class="source"> via:
						<a href="${sourceurl}">${sourcetitle}</a>
						|
						<a class="aSubscribe" data-sourceurl="${encodeURIComponent(sourceurl)}" href="#">Subscribe</a>
					</span>
				{{else}}
                    <span class="source">
						|
						<a href="${feed.feedUrl}">${feed.feedTitle}</a>
					</span>
                {{/if}}
                {{if origin}}<span class="origin hide">${origin}</span>{{/if}}
            </div>

            <div class="actions">
	            <?if( $g_prefs['riverheadlinecart'] != 1 ) {?>
					<?if( $g_prefs['cartinriver'] == 1 && $g_platform['mobile'] ) {?>
                    	<div class="cartform action">
							<a class="_cartlink" href="<?echo $cartulizecgi?>?title=${encodeURIComponent(title)}&url=${encodeURIComponent(link)}&surl=${encodeURIComponent(feed.feedUrl)}&stitle=${encodeURIComponent(feed.feedTitle)}" rel="external nofollow">
								<img class="icon-bookmark" src="/images/blank.gif" alt="" />
							</a>
						</div>
					<?} else {?>
                        <a class="cartlink" href="#" data-id="${id}" label="Cartulize Article">
							<img class="icon-bookmark" src="/images/blank.gif" alt="" />
						</a>
					<?}?>
                <?}?>
                <?if(!empty($prefs['linkblog'])) {?>
					<div>
						<a href="<?echo $prefs['linkblog']?>/?description=${encodeURIComponent(title)}&link=${encodeURIComponent(link)}" rel="external nofollow" target="_blank">RT</a>
					</div>
				<?} else {?>
			  		<a class="mblink rtgo action" href="#" data-id="${id}">
						<img class="icon-share" src="/images/blank.gif" alt="" />
					</a>
				<?}?>
            </div>
		</div>

        <div class="footclear"></div>
    </div>


	<!-- Article sub-items -->
	<div class="subitems">
    {{each(i,sub) subitem}}
		<div class="subitem{{if sub.sticky}} sticky{{/if}}" id="${sub.id}">
	        {{if sub.avatarUrl}}<img class="rivermicrotar" src="${sub.avatarUrl}" alt="" />{{/if}}
            {{each(e,enc) sub.enclosure}}
	            {{if freedomController.v1.river.methods.isAvatar(enc.url)}}
                        <img class="rivermicrotar" src="${enc.url}" alt="" title="Enclosure: ${e}."/>
                {{/if}}
            {{/each}}
            {{if sub.permaLink || sub.link}}<a class="articlelink" name="${sub.id}" href="${sub.permaLink || sub.link}" rel="external">{{/if}}
            {{if sub.title}}
					${sub.title}
			{{else}}
                    ${freedomController.v1.river.methods.getBodyText(sub.body)}
            {{/if}}
            {{if sub.permaLink || sub.link}}</a>{{/if}}
            <div class="time">${prettyDate(sub.pubDate)}</div>
            {{if sub.avatarUrl}} {{else}}[from: ${sub.feedTitle}]{{/if}}
		</div>
		<div class="footclear"></div>
	{{/each}}
	</div>
</li>

