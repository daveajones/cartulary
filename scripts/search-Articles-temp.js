<ul>
 {{each(r, result) data}}
 <li>[ <a href="<?echo $showarticlepage?>?aid=${result.id}"><img class="icon-extlink-1" src="/images/blank.gif" /></a> ] <a href="<?echo $showarticlepage?>?aid=${result.id}">${result.title}</a></li>
 {{/each}}
</ul>

