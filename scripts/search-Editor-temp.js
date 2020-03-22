<ul>
 {{each(r, result) data}}
 <li><a href="<?echo $editorpage?>?url=${result.url}">${result.title}</a></li>
 {{/each}}
</ul>

