  <ul id="ulPostList">   
  {{each(p, post) posts}}
	<li id="${post.id}" data-title="{{if isBlank(post.title)}}${getShortTitle(post.content)}{{else}}${post.title}{{/if}}">
                <span class="spnPostControls">
                  {{if post.shorturl}}<a href="${post.shorturl}"><img class="icon-extlink-small" src="/images/blank.gif" alt="" /></a>{{/if}}
  	          <a class="aDeletePost" data-id="${post.id}" href="#" title="Delete Post"><img class="icon-remove-small" src="/images/blank.gif" alt="" /></a>
		</span>

                {{if isBlank(post.url) == false}}<a href="${post.url}">{{/if}}
		{{if isBlank(post.title) == false}}
		<span class="spnPostTitle">${post.title}</span> <img class="icon-arrow-right" src="/images/blank.gif" alt="" />
		{{/if}}
		${post.content}
                {{if post.url}}</a>{{/if}}
	</li>
  {{/each}}
  </ul>
