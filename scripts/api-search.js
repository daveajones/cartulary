//-----------------------------------------------------------------------------------
// ----- Search API -----
freedomController.v1.search = {};

//Settings --------------------------------------------------------------------------
freedomController.v1.search.settings = {
  cgiSearch      : "/cgi/out/search",
  cgiListPeople  : "/cgi/out/list.people",
  cgiListServers : "/cgi/out/list.servers"
};

//Methods ---------------------------------------------------------------------------
freedomController.v1.search.methods = (function() {

//Takes a section reference and calls the appropriate callback function for that
//section.  This allows the section specific api to modify the search results with
//things like click bindings and such.
function _postLoad( query, section, el ) {
  if( section == 'subscribe' ) {  freedomController.v1.subscribe.methods.searchPostLoad();  }
  if( section == 'people' ) {  freedomController.v1.people.methods.searchPostLoad(query, el);  }

  return true;
}


//Take a search query and parse out a section reference from it
function _parseQuerySection( query ) {
  var regQuotedRemove = /["'][^"']*["']/g;

  //Remove quoted strings from the query first
  var newQuery = query.replace(regQuotedRemove, "");
  
  //Now look for known section specifiers
  if( newQuery.indexOf("people:") === 0 )    {  return("people");     }
  if( newQuery.indexOf("river:") === 0 )     {  return("river");      }
  if( newQuery.indexOf("microblog:") === 0 ) {  return("microblog");  }
  if( newQuery.indexOf("articles:") === 0 )  {  return("articles");   }
  if( newQuery.indexOf("subscribe:") === 0 ) {  return("subscribe");  }

  //Return blank if no recognizable section was found
  return("");
}


//-----------------------------------------------------------------------------------
return {
        postLoad      	    : _postLoad,
        parseQuerySection   : _parseQuerySection
};
//-----------------------------------------------------------------------------------
}());

