  <div id="ulPostList" class="postList">
  {{if posts.length < 1}}
    <center>You haven't posted anything. Why not?</center>
  {{/if}}
  {{each(p, post) posts}}
	<div id="${post.id}" data-title="{{if isBlank(post.title)}}${getShortTitle(post.content)}{{else}}${post.title}{{/if}}" class="postItem well {{if isBlank(post.title) == false}}postItemComplex{{/if}}">
   	      {{if isBlank(post.title) == false}}
        	<span class="spnPostTitle">${post.title}</span>
                <div class="divPostContent">
                  ${post.content}
                  <?if( $platform != "mobile" ) {?>
                  {{if enclosure && enclosure.length > 0}}<div class="divPostListEnclosures">{{/if}}
                  {{each(e, enc) enclosure}}
		    {{if isImage(enc.url)}}<a href="${enc.url}" title="Hover to embiggen. Click to open."><img class="mbthumbnail" src="${enc.url}" /></a>
		    {{else}}<a href="${enc.url}" title="Click to open.">Download Enclosure</a>
                    {{/if}}
		  {{/each}}
                  {{if enclosure && enclosure.length > 0}}</div>{{/if}}
		  <?}?>
                </div>

                {{else}}
		<span class="spnPostContent">
                  ${post.content}
                  <?if($platform != "mobile") {?>
                  {{if enclosure && enclosure.length > 0}}<div class="divPostListEnclosures">{{/if}}
		  {{each(e, enc) enclosure}}
		    {{if isImage(enc.url)}}<a href="${enc.url}" title="Hover to embiggen. Click to open."><img class="mbthumbnail" src="${enc.url}" /></a>
		     {{else}}<a href="${enc.url}" title="Click to open.">Download Enclosure</a>
                     {{/if}}
		  {{/each}}
                  {{if enclosure && enclosure.length > 0}}</div>{{/if}}
                  <?}?>
                </span>
              {{/if}}

              <div class="postFooter">
	      <span class="postDate">${prettyDate(post.createdon * 1000)}</span>
              <span class="postControls">
  	          <a class="aDeletePost" data-id="${post.id}" href="#" title="Delete Post"><img class="icon-remove-1" src="/images/blank.gif" alt="" /></a>
                  {{if post.shorturl}}<a href="${post.shorturl}"><img class="icon-extlink-1" src="/images/blank.gif" alt="" /></a>
                  {{else}}
		    {{if isBlank(post.url) == false}}<a href="${post.url}"><img class="icon-extlink-1" src="/images/blank.gif" alt="" /></a>{{/if}}
		  {{/if}}
              </span>
	      </div>
	</div>
  {{/each}}
  </div>
