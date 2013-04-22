//-----------------------------------------------------------------------------------
// ----- Feed Subscription API -----
freedomController.v1.river = {};
freedomController.v1.river.methods = (function() {
//-----------------------------------------------------------------------------------


function _searchPostLoad() {
	_rebindEverything();
}

function _rebindEverything() {
	_bindUnsubLinks();
        _bindStickyButtons();
        _bindHiddenButtons();
	_bindFullTextButtons();
}


//-----------------------------------------------------------------------------------

return {
        searchPostLoad      : _searchPostLoad,
};

//-----------------------------------------------------------------------------------
}());
