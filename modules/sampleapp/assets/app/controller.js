define([
	'require',
	'marionette'
], function(lrequire, Marionette)
{

	// Is this the initialization step ?
	var isModulesInit = false;

	// Number of module to load
	var numberOfModulesToLoad = 0;

	// This is the controller
	var controller = {

		/**
		 * Initialize the controller.
		 * It was called by the main router during its initialization.
		 */
		initialize: function(options)
		{
			// Store options
			this.options = options;

			// Get the app
			var app = this.options.app;

			// Catch module init event
			app.vent.on('module:init', function()
			{
				numberOfModulesToLoad--;

				app.vent.trigger('module:boot');
			});

			// Catch module boot event
			app.vent.on('module:boot', function()
			{
				if (numberOfModulesToLoad > 0)
					return;

				// Never init state
				isModulesInit = false;

				// Launch app:start event
				app.vent.trigger('app:start');
			});

			// Catch page error event
			app.vent.on('page:error', this.doError, this);
		},

		/**
		 * Default action when no route match
		 *
		 * @param {string} route A route
		 */
		doDefault: function(route)
		{
			this.loadModules(route);
		},

		/**
		 * Display error message
		 */
		doError: function()
		{
			var app = this.options.app;

			require(['view/errorView'], function(ErrorView)
			{
				var errorView = new ErrorView({app: app});
				app.root.currentView.page.show(errorView);
			});
		},

		/**
		 * Login action
		 */
		doLogin: function()
		{
			var app = this.options.app;

			require(['view/loginView'], function(LoginView)
			{
				app.root.currentView.page.show(new LoginView({app: app}));
			});
		},

		/**
		 * Logout action
		 */
		doLogout: function()
		{
			var app = this.options.app;

			app.vent.trigger('logout:success');
		},

		/**
		 * Display the welcome page
		 */
		doWelcome: function()
		{
			var app = this.options.app;

			require(['view/welcomeView'], function(WelcomeView)
			{
				app.root.currentView.page.show(new WelcomeView({app: app}));
			});
		},

		/**
		 * Load modules.
		 *
		 * @param {string} route A route
		 */
		loadModules: function(route)
		{
			// This is this controller
			var me = this;

			// This is the application
			var app = this.options.app;

			// Fix a default route to load
			if (typeof route == 'undefined')
				route = 'init';

			// What to do if success
			var onSuccessCb = function (response)
			{
				// Set modules init
				isModulesInit = route == 'init';

				// Set number of modules to load
				numberOfModulesToLoad = response.data.length;

				// Load all found modules
				for (var i=0, m=numberOfModulesToLoad; i < m; i++)
				{
					app.vent.trigger('module:load', {
						loadFragment: !isModulesInit,
						module: response.data[i],
						route: route
					}, true);
				}
			}

			// What to do if error
			var onErrorCb = function (xhr, status)
			{
				me.doError();
			};

			// Request module to load at initialization
			$.ajax(
			{
				url: 'api/v1/app/require_js',
				type: 'POST',
				dataType: 'json',
				data: {'route': route},
				success: onSuccessCb,
				error: onErrorCb
			});
		}

	};

	return controller;
});
