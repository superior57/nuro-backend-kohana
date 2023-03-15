<?php defined('SYSPATH') or die('No direct script access.');

/**
 * A service class to manage accounts
 */
class SampleApp_Service_Account extends Service
{

	/**
	 * Mail titles
	 */
	private static $_mail_titles = array (
		'CREATE' => 'Welcome on :title',
		'FORGOT_PASSWORD' => 'How to reset your password on :title',
		'REMOVE' => 'Goodbye from :title'
	);

	/**
	 * Authenticate user
	 *
	 * @param {array} $data
	 * @param {boolean} $trace
	 * @return {Model_App_Account}
	 */
	public function authenticate ( array $data, $trace = TRUE )
	{
		// This will store the account
		$account = NULL;

		// If token found, then authenticate through this token
		if (isset($data['token']))
		{
			$token = Service::factory('Token')->get($data['token']);

			if (!$token->is_valid())
				throw Service_Exception::factory('AuthError', 'Token authentication failed');

			$account = $this->get(array('id' => $token->target_id));
		}

		// Else, check password authentication and generate a permanent token
		else if (isset($data['password']))
		{
			$account = $this->get($data);

			if (!$account->validate_password($data['password']))
				throw Service_Exception::factory('AuthError', 'Standard authentication failed');
		}

		// Missing authentication parameter
		else
			throw Service_Exception::factory('AuthError', 'Invalid authentication scheme');

		// Check if the email have been confirmed
		if (!$account->email_verified)
			throw Service_Exception::factory('AuthError', 'Email has not been verified');

		// Trace the authentication if necessary
		if ($trace)
			$account = $this->update($account, array('date_lastvisit' => time()));

		return $account;
	}

	/**
	 * Confirm account
	 *
	 * @param {array} $data
	 * @return {Model_App_Account}
	 */
	public function confirm ( array $data )
	{
		// Try to get the token
		$token = Service::factory('Token')->get($data['token']);

		// Validate it
		if (!$token->is_valid())
			throw Service_Exception::factory('InvalidData', 'Token is not valid');

		// Get the target identifier
		$target_id = $token->target_id;

		// Delete the token
		$token->remove();

		// Get the account
		$account = $this->get(array('id' => $target_id));

		// Check if account has already been confirmed
		if ($account->email_verified === TRUE)
			throw Service_Exception::factory('InvalidData', 'Account is already confirmed');

		// Confirm the account
		return $this->update($account, array('email_verified' => TRUE));
	}

	/**
	 * Create a user account
	 *
	 * @param {array} $data
	 * @param {boolean} $email Send a email or not
	 * @return {Model_App_Account}
	 */
	public function create ( array $data, $email = TRUE )
	{
		// This will store the account model
		$account = Model::factory('App_Account');

		// Check if the account already exists
		try
		{
			$account = $this->get($data);

			throw Service_Exception::factory('AlreadyExists', 'Account :email already exists',
				array(':email' => $data['email']));
		}
		// Catch NotFound exception
		catch (Service_Exception_NotFound $e) {}

		// If nothing wrong, save account data
		$account->set_data($data)->save();

		// Temporary token is only necessary if email have to be sent
		if ($email)
		{
			// Create a temporary token
			$token = Service::factory('Token')->create($account, array('type' => 'mail'));

			// Could not create account if mail is not sent
			if (!$this->_send_email($account, 'CREATE', $token))
			{
				$account->remove();
				$token->remove();

				throw Service_Exception::factory('UnknownError', 'Unable to send email to :email',
					array(':email' => $data['email']));
			}
		}

		return $account;
	}

	/**
	 * Send reset password instruction by mail if account exists
	 *
	 * @param {array} $data
	 * @return {Model_App_Account}
	 */
	public function forgot_password ( array $data )
	{
		// Get the account
		$account = $this->get($data);

		// Create a temporary token
		$token = Service::factory('Token')->create($account, array('type' => 'mail'));

		// Could not create account if mail is not sent
		if (!$this->_send_email($account, 'FORGOT_PASSWORD', $token))
		{
			$token->remove();

			throw Service_Exception::factory('UnknownError', 'Unable to send email to :email',
				array(':email' => $account->email));
		}

		return $account;
	}

	/**
	 * Get a user account
	 *
	 * @param {array} $data
	 * @return {Model_App_Account}
	 */
	public function get ( array $data )
	{
		// Get the account model
		$account = Model::factory('App_Account');

		// Validate data according to account model rules
		$validation = $account->validate_data($data);

		// If validation failed, return the appropriate errors
		if (!$validation['status'])
			throw Service_Exception::factory('InvalidData', 'Account data validation failed')->data($validation['errors']);

		// Try to load by resource identifier
		if (isset($data['id']))
			$account->load($data['id']);

		// Try to load the account by email
		else if (isset($data['email']))
			$account->load_by_email($data['email']);

		// Raise an exception if account could not be loaded
		if (!$account->loaded())
			throw Service_Exception::factory('NotFound', 'Account not found')->data($data);

		return $account;
	}

	/**
	 * Get an authentication token of a given account
	 *
	 * @param {Model_App_Account} $account
	 * @param {boolean} $renew
	 * @return {Model_App_Token}
	 */
	public function get_authentication_token ( $account, $renew = TRUE )
	{
		if (!$account->loaded())
			return FALSE;

		// This will store the valid token
		$token = NULL;

		// Get all auth tokens
		$tokens = Service::factory('Token')->get_all($account, array('type' => 'auth'));

		// If the action need to renew token, remove them all,
		// and create a fresh one
		if ($renew)
		{
			foreach ($tokens as $id => $token)
			{
				$token->remove();
				unset($tokens[$id]);
			}
		}

		// If tokens found, get the first one
		if (isset($tokens[0]))
			$token = $tokens[0];

		// Else create a fresh one
		else
			$token = Service::factory('Token')->create($account, array('type' => 'auth'));

		return $token;
	}

	/**
	 * Delete a user account
	 *
	 * @param {Model_App_Account}
	 * @return {boolean}
	 */
	public function remove ( $account )
	{
		if (!$account->loaded())
			return FALSE;

		// Clone the account to have information for mail
		$account_tmp = clone $account;

		if (!$account->remove())
			throw Service_Exception::factory('UnknownError', $account->last_error());

		// Remove all tokens associated to this user
		Service::factory('Token')->remove_all($account_tmp);

		// Could not create account if mail is not sent
		if (!$this->_send_email($account_tmp, 'REMOVE'))
		{
			throw Service_Exception::factory('UnknownError', 'Unable to send email to :email',
				array(':email' => $account_tmp->email));
		}

		return TRUE;
	}

	/**
	 * Reset password
	 *
	 * @param {array} $data
	 * @return {Model_App_Account}
	 */
	public function reset_password ( array $data )
	{
		// Try to get the token
		$token = Service::factory('Token')->get($data['token']);

		// Validate it
		if (!$token->is_valid())
			throw Service_Exception::factory('InvalidData', 'Token is not valid');

		// Get the target identifier
		$target_id = $token->target_id;

		// Delete the token
		$token->remove();

		// Get the account
		$account = $this->get(array('id' => $target_id));

		// Update the password
		return $this->update($account, array(
			'email_verified' => TRUE,
			'password' => $data['password']
		));
	}

	/**
	 * Update a user account
	 *
	 * @param {App_Model_Account} $account
	 * @param {array} $data
	 * @return {Model_App_Account}
	 */
	public function update ( $account, array $data )
	{
		unset($data['id']);

		if (!$account->set_data($data)->save())
			throw Service_Exception::factory('InvalidData', $account->last_error());

		return $account;
	}

	/**
	 * Send a mail to an account
	 *
	 * @param {Model_App_Account} $account
	 * @param {string} $type
	 * @param {Model_App_Token} $token An optional token
	 * @return {boolean}
	 */
	private function _send_email ( $account, $type, $token = null )
	{
		// Get the application name
		$app_name = Kohana::$config->load('app.name');

		// Get the translated the title
		$title = Service::factory('I18n')->get_string(self::$_mail_titles[$type], array(':title' => $app_name));

		// Get the email service
		$email = Service::factory('Email');

		// Build headers
		$headers = $email->build_headers($account->email, $title);

		// Build mail template name
		$template = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($type))));

		// Build data
		$data = array(
			'id' => $account->id(),
			'email' => $account->email,
			'firstname' => $account->firstname,
			'lastname' => $account->lastname
		);

		// Add token id if not null
		if (!is_null($token))
		{
			$data['token'] = $token->id();
			$data['token_timeout'] = $token->timeout;
		}

		// Build content
		$content = $email->build_content('Account.'.$template, $data);

		return $email->send($headers, $content);
	}

} // End SampleApp_Service_Account

