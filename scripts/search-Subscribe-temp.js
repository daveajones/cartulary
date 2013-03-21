<ul>
 {{each(f, feed) data}}
 <li data-id="${feed.id}" title="${feed.title}"> 
   <a href="${feed.url}">${feed.title}</a>
   <span class="feed-properties">
      <a class="aFeedRaw" href="${feed.url}"><img title="See the raw xml of this feed." class="icon-feed-raw" src="/images/blank.gif" /></a>
      <button name="sticky" title="{{if feed.sticky}}Un-sticky this feed's posts.{{else}}Make this feed's posts sticky.{{/if}}" type="button" class="{{if feed.sticky == 1}}btnFeedSticky{{else}}btnFeedNotSticky{{/if}} stickybutton"></button>
      <button name="hidden" title="{{if feed.hidden}}Un-hide this feed's posts.{{else}}Hide this feed's posts.{{/if}}" type="button" class="{{if feed.hidden == 1}}btnFeedHidden{{else}}btnFeedNotHidden{{/if}} hiddenbutton"></button>
      <button name="fulltext" title="{{if feed.fulltext}}Only show a portion of each post.{{else}}Show the full text of each post.{{/if}}" type="button" class="{{if feed.fulltext == 1}}btnFeedFullText{{else}}btnFeedNotFullText{{/if}} fulltextbutton"></button>
   </span>
 </li>
 {{/each}}
</ul>

