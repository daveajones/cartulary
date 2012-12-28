<ul>
 {{each(f, feed) data}}
 <li data-id="${feed.id}" title="${feed.title}"> 
   <a href="${feed.url}">${feed.title}</a>
   <span class="feed-properties">
      <a class="aFeedRaw" href="${feed.url}"><img title="See the raw xml of this feed." class="icon-feed-raw" src="/images/blank.gif" /></a>
      <button name="sticky" title="Make this feed sticky." type="button" class="{{if feed.sticky == 1}}btnFeedSticky{{else}}btnFeedNotSticky{{/if}} stickybutton"></button>
      <button name="hidden" title="Hide this feed's posts." type="button" class="{{if feed.hidden == 1}}btnFeedHidden{{else}}btnFeedNotHidden{{/if}} hiddenbutton"></button>
   </span>
 </li>
 {{/each}}
</ul>

