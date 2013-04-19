<div id="divFeedAdmin">
<table id="feedlist" class="table table-hover">
  <thead>
    <tr>
      <th>&nbsp;</th>
      <th>&nbsp;</th>
      <th title="The title of the feed.">Title</th>
      <th title="Subscriber count.">Sub</th>
      <?if($platform != "mobile") {?>
      <th title="Consecutive error count.">Err</th>
      <th title="The date this feed first entered the system.">Born</th>
      <th title="The last time this feed changed.">Modified</th>
      <th title="Average number of new items per scan.">Avg.New</th>
      <th title="Average time it takes to scan this feed.">Avg.Time</th>
      <th title="Average checks between new items.">Av.Intvl</th>
      <?}?>
    </tr>
  </thead>
  <tbody>
    {{each(f, feed) feeds}}
      <tr id="tr_feed_${feed.id}" data-name="{{if isBlank(feed.title)}}Untitled Feed{{else}}${feed.title}{{/if}}">
        <td><a id="delete_${feed.id}" class="aDeleteFeed" title="Delete feed from the system." href="#"><img class="icon-delete" src="/images/blank.gif" /></a></td>
        <td><a id="reset_${feed.id}" class="aResetFeed" title="Force a scan of this feed." href="#"><img class="icon-reset" src="/images/blank.gif" /></a></td>
        <td><a href="${feed.url}">{{if isBlank(feed.title)}}Untitled Feed{{else}}${feed.title}{{/if}}</a></td>
        <td>${feed.subscribers}</td>
        <?if($platform != "mobile") {?>
        <td>${feed.errors}</td>
        <td>${prettyDate(feed.createdon * 1000)}</td>
        <td>${prettyDate(feed.lastupdate * 1000)}</td>
        <td>${feed.avgnewitems}</td>
        <td>${feed.avgchecktime}</td>
        <td>${feed.avgnewinterval}</td>
        <?}?>
      </tr>
    {{/each}}
  </tbody>
</table>
</div>
