<p class="search-pre"></p>
<ul class="search-people">
 {{each(r, result) data}}
 <li>
   <img src="{{if isBlank(result.avatarurl)}}/images/noavatar.png{{else}}${result.avatarurl}{{/if}}" alt="" />
   <a class="sopmllink" href="#" data-href="${result.url}">${result.name}</a><br/>
   <a class="aSubscribe" data-sourceurl="${encodeURIComponent(result.url)}" href="#">Subscribe</a>
 </li>
 {{/each}}
</ul>
<div class="search-more"><img class="spinner" src="/images/spinner.gif" alt="" /></div>
