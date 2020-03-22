<ul>
 {{each(r, result) data}}
 <li>[ <a class="mblink" href="<?echo $microblogpage?>?description=${encodeURIComponent(result.title)}&link=${encodeURIComponent(result.url)}"><img class="icon-retweet" src="/images/blank.gif" /></a> <a class"cartlink" href="<?echo $cartulizecgi?>?title=${encodeURIComponent(result.title)}&url=${encodeURIComponent(result.url)}"><img class="icon-book-small" src="/images/blank.gif" /></a>] <a href="${result.url}">${result.title}</a></li>
 {{/each}}
</ul>
