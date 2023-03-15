(function ()
{
	var root = this;

	// To not reload jquery dependencies
	define('jquery', [], function()
	{
		return jQuery;
	});
 
 	// Configure Requirement
	require.config({
		paths: {
			// CDN
			'backbone': '//cdnjs.cloudflare.com/ajax/libs/backbone.js/1.0.0/backbone-min',
			'i18n': '//cdnjs.cloudflare.com/ajax/libs/require-i18n/2.0.1/i18n',
			'marionette': '//cdnjs.cloudflare.com/ajax/libs/backbone.marionette/1.0.4-bundled/backbone.marionette.min',
			'text': '//cdnjs.cloudflare.com/ajax/libs/require-text/2.0.10/text',
			'underscore': '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.4.4/underscore-min',

			// Local
			'backbone.session': '../lib/backbone/backbone.session',
			'marionette.formview': '../lib/marionette/backbone.marionette.formview'
		},
		shim: {
			'backbone': {
				deps: ['underscore', 'jquery'],
				exports: 'Backbone'
			},
			'backbone.session': {
				deps: ['backbone']
			},
			'jquery': {
				exports: '$'
			},
			'marionette': {
				deps: ['backbone', 'backbone.session'],
				exports: 'Backbone.Marionette'
			},
			'marionette.formview': {
				deps: ['marionette']
			},
			'underscore': {
				exports: '_'
			},
		}
	});

	// Update the loading status
	require(['jquery'], function($)
	{
		var progress = $("#appprogress");

		window.require = (function(){
			var orig_require = window.require;
			return function(_list, _callback) {
				var callback_fn = function(_args){ _callback.apply(null, _args); }
				progress.show(0, function(){
					orig_require.call(null, _list, function(){
						progress.hide(0, callback_fn(arguments));
					});
				});
			}
		})();
	});

 	// Run the application
	require(['app'], function (App)
	{
		App.start({
			title: CONFIG.name,
			url: CONFIG.url
		});
	});
 
})();
