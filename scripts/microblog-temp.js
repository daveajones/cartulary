  <ul id="ulPostList">
  {{if posts.length < 1}}
    <center>You haven't posted anything. Why not?</center>
  {{/if}}
  {{each(p, post) posts}}
	<li id="${post.id}" data-title="{{if isBlank(post.title)}}${getShortTitle(post.content)}{{else}}${post.title}{{/if}}" class="well">
                <span class="spnPostControls">
                  {{if post.shorturl}}<a href="${post.shorturl}"><img class="icon-extlink-small" src="/images/blank.gif" alt="" /></a>{{/if}}
  	          <a class="aDeletePost" data-id="${post.id}" href="#" title="Delete Post"><img class="icon-remove-small" src="/images/blank.gif" alt="" /></a>
		</span>

                {{if isBlank(post.url) == false}}<a href="${post.url}">{{/if}}
		  {{if isBlank(post.title) == false}}
                    <span class="spnPostTitle">
                      ${post.title}
                    </span>
                    {{if post.url}}</a>{{/if}}
		    <div class="divPostContent">
                      ${post.content}
                      <?if( $platform != "mobile" ) {?>
                      {{if enclosure && enclosure.length > 0}}<div class="divPostListEnclosures"><small>Enclosures:</small><br/>{{/if}}
                      {{each(e, enc) enclosure}}
			{{if isImage(enc.url)}}<a href="${enc.url}" title="Hover to embiggen. Click to open."><img class="mbthumbnail" src="${enc.url}" /></a>
			{{else}}<a href="${enc.url}" title="Click to open.">${enc.url}</a>
                        {{/if}}
		      {{/each}}
		      <?}?>
                    </div>
		  {{else}}
		  <span class="spnPostContent">
                    ${post.content}
                    {{if post.url}}</a>{{/if}}
                    <?if($platform != "mobile") {?>
                    {{if enclosure && enclosure.length > 0}}<div class="divPostListEnclosures"><small>Enclosures:</small><br/>{{/if}}
		    {{each(e, enc) enclosure}}
			{{if isImage(enc.url)}}<a href="${enc.url}" title="Hover to embiggen. Click to open."><img class="mbthumbnail" src="${enc.url}" /></a>
			{{else}}<a href="${enc.url}" title="Click to open.">${enc.url}</a>
                        {{/if}}
		    {{/each}}
                    {{if enclosure && enclosure.length > 0}}</div>{{/if}}
                    <?}?>
                  </span>
                {{/if}}
	</li>
  {{/each}}
  </ul>
