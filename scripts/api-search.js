//-----------------------------------------------------------------------------------
// ----- Search API -----
freedomController.v1.search = {};
freedomController.v1.search.methods = (function() {
//-----------------------------------------------------------------------------------


function _postLoad( section ) {
  if( section == 'subscribe' ) {
    freedomController.v1.subscribe.methods.searchPostLoad();
  }

  return true;
}




//-----------------------------------------------------------------------------------
return {
        postLoad      	    : _postLoad
};
//-----------------------------------------------------------------------------------
}());

