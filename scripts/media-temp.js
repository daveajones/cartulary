<div class="pinboard">
{{if items.length < 1}}
  <center class="noposts">You haven't posted anything. Why not?</center>
{{/if}}
{{each(p, item) items}}
  {{each(e, enc) enclosure}}
    {{if isImage(enc.url, enc.type) || isAudio(enc.url, enc.type) || isVideo(enc.url, enc.type)}} 
      <div id="${item.id}" class="pin well">
        <div class="pinenclosure">
          {{if isImage(enc.url, enc.type)}}<a class="pinmedia" href="${enc.url}"><img class="pinmedia" src="${enc.url}" title="{{if isBlank(item.title)}}Untitled{{else}}${item.title}{{/if}}" /></a>
          {{else isAudio(enc.url, enc.type)}}<audio class="pinmedia" src="${enc.url}" preload="metadata" controls></audio>
          {{else isVideo(enc.url, enc.type)}}<video class="pinmedia" src="${enc.url}" preload="metadata" controls></video>
	  {{else}}
      	  {{/if}}
        </div>
        <div class="pinfooter">
          <span class="pindate pull-right">${enc.length} | ${prettyDate(item.timeadded * 1000)}</span>
        </div>
      </div>
    {{/if}}
  {{/each}}
{{/each}}
</div>
