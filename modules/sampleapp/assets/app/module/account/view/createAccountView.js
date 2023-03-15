
define([
	'marionette',
	'marionette.formview',
	'helper/template',
	'text!module/account/template/createAccountView.html'
], function(Marionette, MarionetteFormView, TemplateHelper, CreateAccountTemplate)
{
	var accountCreated = false;

	var createAccountView = Marionette.FormView.extend(
	{
		template: CreateAccountTemplate,

		templateHelpers: TemplateHelper,

		/**
		 * Initialize stuff like event listeners
		 */
		initialize: function ()
		{
			this.listenTo(this.model, 'account:save:error', this.onModelError);
			this.listenTo(this.model, 'account:save:success', this.onModelSuccess);
		},

		/**
		 * Declare variables to access template content
		 */
		ui: {
			email: '#account-email',
			password: '#account-password',
			password2: '#account-password2',
			firstname: '#account-firstname',
			lastname: '#account-lastname'
		},

		/**
		 * Declare inputs
		 */
		fields: {
			email: {
				el: 'email',
				required: "Please enter a valid Email Address.",
				validations: {
					email: "Please enter a valid Email Address."
				}
			},
			firstname: {
				el: 'firstname'
			},
			lastname: {
				el: 'lastname'
			},
			password: {
				el: 'password',
				required: "Please enter your password.",
				validations: {
					password: "Please enter a valid Password."
				}
			},
			password2: {
				el: 'password2',
				required: "Please confirm your password.",
				validations: {
					password: "Please enter a valid password.",
					confirmPassword: "The confirmed password doesn't match the password."
				}
			}
		},

		/**
		 * Specific validation rules
		 */
		rules: {
			password: function(val)
			{
				return /^['a-zA-Z0-9]{8,}$/.test(val);
			},
			confirmPassword: function(val)
			{
				return val == this.inputVal('password');
			}
		},

		/**
		 * What to do if an error occurs on the model
		 */
		onModelError: function (response, xhr)
		{
			var app = this.options.app;

			app.vent.trigger('notify:error', response.message);
		},

		/**
		 * What to do in success
		 */
		onModelSuccess: function (model, response, options)
		{
			accountCreated = true;
			this.render();
		},

		/**
		 * Save model when submit me
		 */
		onSubmit: function (evt)
		{
			evt.preventDefault();

			this.model.set(this.serializeFormData()).savedata();
		},

		/**
		 * What to do if the submit fails
		 */
		onSubmitFail: function (errors)
		{
			this.showLocalErrors(errors);
		},

		/**
		 * Serialize data
		 */
		serializeData: function ()
		{
			var app = this.options.app;

			return $.extend({}, this.model.toJSON(), {
				created: accountCreated,
				apptitle: app.title
			});
		},

		/**
		 * Highlight input in error
		 */
		showLocalErrors: function (errors)
		{
			var cpt = 0;

			_(errors).each(function (field)
			{
				$('#account-'+field.el+'-control').addClass('error');
				$('#account-'+field.el).tooltip({
					placement: 'right',
					title: field.error[0]}
				);

				if (cpt == 0)
					$('#account-'+field.el).tooltip('show');

				cpt++;
			});
		}

	});

	return createAccountView;
});
