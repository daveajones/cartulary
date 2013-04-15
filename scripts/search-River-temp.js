<ul>
 {{each(r, result) data}}
 <li>[ <a class="mblink" href="${result.url}"><img class="icon-retweet" src="/images/blank.gif" /></a> <a class"cartlink" href="${result.url}"><img class="icon-book-small" src="/images/blank.gif" /></a>] <a href="<?echo $showarticlepage?>?aid=${result.id}">${result.title}</a></li>
 {{/each}}
</ul>

