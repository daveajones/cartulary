<div class="subitem{{if sticky}} sticky{{/if}}" id="${id}">
	{{if avatarUrl}}<img class="rivermicrotar" src="${avatarUrl}" alt="" />{{/if}}
    {{each(e,enc) enclosure}}
	    {{if freedomController.v1.river.methods.isAvatar(enc.url)}}
                <img class="rivermicrotar" src="${enc.url}" alt="" title="Enclosure: ${e}."/>
        {{/if}}
    {{/each}}
    {{if permaLink || link}}<a class="articlelink" name="${id}" href="${permaLink || link}" rel="external">{{/if}}
    {{if title}}
			${title}
	{{else}}
            ${freedomController.v1.river.methods.getBodyText(body)}
    {{/if}}
    {{if permaLink || link}}</a>{{/if}}
    <div class="time">${prettyDate(pubDate)}</div>
    {{if avatarUrl}} {{else}}[from: ${feedTitle}]{{/if}}
</div>
<div class="footclear"></div>
