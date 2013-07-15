  <div id="ulPostList" class="postList">
    <div class="{{if posts.length > 0}}postItem well {{/if}}toolPanel">
      Microblog Shortcuts:
      <br/><br/>
      <center>
        <a class="rss" href="<?if(!empty($s3blogfeed)) { echo $s3blogfeed; } else { echo $microblogpage."-rss"; }?>" label="RSS"><img class="icon-rss" src="/images/blank.gif" alt="" /></a>
        <a class="html" href="<?if(!empty($s3bloghtml)) { echo $s3bloghtml; } else { echo $microblogpage."-html"; }?>" label="HTML"><img class="icon-html" src="/images/blank.gif" alt="" /></a>
        <a class="opml" href="<?if(!empty($s3blogopml)) { echo $s3blogopml; } else { echo $microblogpage."-opml"; }?>" label="OPML"><img class="icon-opml" src="/images/blank.gif" alt="" /></a>
        <a class="import" href="#mdlFeedImport" data-toggle="modal" label="Import Feed"><img class="icon-import" src="/images/blank.gif" alt="" /></a>
      </center>
    </div>
  {{if posts.length < 1}}
    <center class="noposts">You haven't posted anything. Use the <a href="<?echo sprintf($bookmarklet_blog_url, $system_url)?>">Microblog</a> bookmarklet.</center>
  {{/if}}
  {{each(p, post) posts}}
	<div id="${post.id}" data-title="{{if isBlank(post.title)}}${getShortTitle(post.content)}{{else}}${post.title}{{/if}}" class="postItem well {{if isBlank(post.title) == false}}postItemComplex{{/if}}">
   	      {{if isBlank(post.title) == false}}
        	<span class="spnPostTitle">${post.title}</span>
                <div class="divPostContent">
                  ${post.content}
                  {{if enclosure && enclosure.length > 0}}<div class="divPostListEnclosures">{{/if}}
                  {{each(e, enc) enclosure}}
		    {{if isImage(enc.url)}}<a href="${enc.url}" title="Hover to embiggen. Click to open."><img class="mbthumbnail" src="${enc.url}" /></a>
		    {{else}}<div class="download"> - <a href="${enc.url}" title="Click to open.">Download Enclosure</a></div>
                    {{/if}}
		  {{/each}}
                  {{if enclosure && enclosure.length > 0}}</div>{{/if}}
                </div>

                {{else}}
		<span class="spnPostContent">
                  ${post.content}
                  {{if enclosure && enclosure.length > 0}}<div class="divPostListEnclosures">{{/if}}
		  {{each(e, enc) enclosure}}
		    {{if isImage(enc.url)}}<a href="${enc.url}" title="Hover to embiggen. Click to open."><img class="mbthumbnail" src="${enc.url}" /></a>
		     {{else}}<div class="download"> - <a href="${enc.url}" title="Click to open.">Download Enclosure</a></div>
                     {{/if}}
		  {{/each}}
                  {{if enclosure && enclosure.length > 0}}</div>{{/if}}
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
