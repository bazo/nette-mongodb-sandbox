/**
 * AJAX Nette Framwork plugin for jQuery
 *
 * @copyright   Copyright (c) 2009 Jan Marek
 * @license     MIT
 * @link        http://nettephp.com/cs/extras/jquery-ajax
 * @version     0.2
 */

jQuery.extend({
	nette: {
		updateSnippet: function (id, html) {
                        var snippet = $("#" + id);
                        snippet.html(html);
			
			snippet.find('form').each(function(index, element){
			   Nette.initForm(element); 
			});
			
		},

		success: function (payload) {
			// redirect
			if(payload){
			    if (payload.redirect) {
					window.location.href = payload.redirect;
					return;
			    }

			    // snippets
			    if (payload.snippets) {
					for (var i in payload.snippets) {
						jQuery.nette.updateSnippet(i, payload.snippets[i]);
					}
			    }
			}
		}
	}
});

jQuery.ajaxSetup({
	success: jQuery.nette.success,
	dataType: "json"
});