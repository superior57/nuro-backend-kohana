<?php defined('SYSPATH') or die('No direct script access.');

/**
 * REST API to manage account
 */
class SampleApp_Controller_Api_V1_Account extends Controller_Api_Rest
{

	/**
	 * Actions that need restriction by token authentication
	 */
	protected $_action_restricted = array(
		HTTP_Request::GET,
		HTTP_Request::PUT,
		HTTP_Request::DELETE
	);

	/**
	 * Return an account
	 * The returned account could be the authenticated one, or given by its id
	 */
	public function action_get ()
	{
		// Get account id
		$id = $this->request->param('id');

		// Get the account service
		$account_service = Service::factory('Account');

		// Get api service
		$api_service = Service::factory('Api');

		try
		{
			// Get current logged in user account
			$user = $api_service->get_account();

			// If id is null, then try to use the one found into token
			if (is_null($id))
				$id = $user->id();

			// Get the account
			$account = $account_service->get(array('id' => $id));

			// Check if we could get the account
			$api_service->check_access($account, $user);

			// Return appropriate HTTP code
			$this->response($api_service->build_response_succeed(
				'Account found',
				$account->get_data()
			), 200);
		}
		catch (Service_Exception_NotFound $e)
		{
			$this->response($api_service->build_response_failed($e->getMEssage()), 404);
		}
		catch (Service_Exception_PermissionDenied $e)
		{
			$this->response($api_service->build_response_failed($e->getMEssage()), 403);
		}
		catch (Exception $e)
		{
			Kohana_Exception::log($e, Log::ERROR);
			$this->response($api_service->build_response_failed($e->getMessage()), 400);
		}
	}

	/**
	 * Modify an account
	 * The one which is connected or given by its ID
	 */
	public function action_update ()
	{
		// Get data from the body sent by backbone
		$body = $this->request->body();

		// Build request data
		$data = array();
		if (isset($body->email)) $data['email'] = $body->email;
		if (isset($body->firstname)) $data['firstname'] = $body->firstname;
		if (isset($body->gravatar_email)) $data['gravatar_email'] = $body->gravatar_email;
		if (isset($body->lastname)) $data['lastname'] = $body->lastname;
		if (isset($body->password)) $data['password'] = $body->password;

		// Get the account service
		$account_service = Service::factory('Account');

		// Get the api service
		$api_service = Service::factory('Api');

		try
		{
			// Get current logged in user account
			$user = $api_service->get_account();

			// Update the account
			$account = $account_service->get(array('id' => $body->id));

			// Check if we could get the account
			$api_service->check_access($account, $user);

			// Update data
			$account_service->update($account, $data);

			// Return appropriate HTTP code
			$this->response($api_service->build_response_succeed(
				'Account updated',
				$account->get_data()
			), 200);
		}
		catch (Service_Exception_InvalidData $e)
		{
			$this->response($api_service->build_response_failed(
				$e->getMessage(),
				array(),
				array(
					'error' => $e->data()
				)
			), 400);
		}
		catch (Service_Exception_NotFound $e)
		{
			$this->response($api_service->build_response_failed($e->getMEssage()), 404);
		}
		catch (Service_Exception_PermissionDenied $e)
		{
			$this->response($api_service->build_response_failed($e->getMEssage()), 403);
		}
		catch (Exception $e)
		{
			Kohana_Exception::log($e, Log::ERROR);
			$this->response($api_service->build_response_failed($e->getMessage()), 400);
		}
	}

	/**
	 * Create an account
	 */
	public function action_create ()
	{
		// Get data from the body sent by backbone
		$body = $this->request->body();

		// Build request data
		$data = array(
			'email' => $body->email,
			'password' => $body->password
		);

		// Get the account service
		$account_service = Service::factory('Account');

		// Get the api service
		$api_service = Service::factory('Api');

		try
		{
			// Create the account
			$account = $account_service->create((array) $data);

			// Return appropriate HTTP code
			$this->response($api_service->build_response_succeed(
				'Account created',
				$account->get_data()
			), 201);
		}
		catch (Service_Exception_AlreadyExists $e)
		{
			$this->response($api_service->build_response_failed($e->getMessage()), 409);
		}
		catch (Service_Exception_InvalidData $e)
		{
			$this->response($api_service->build_response_failed(
				$e->getMessage(),
				array(),
				array(
					'error' => $e->data()
				)
			), 400);
		}
		catch (Exception $e)
		{
			Kohana_Exception::log($e, Log::ERROR);
			$this->response($api_service->build_response_failed($e->getMessage()), 400);
		}
	}

	/**
	 * Delete an account
	 */
	public function action_delete ()
	{
		// Get account id
		$id = $this->request->param('id');

		// Get the account service
		$account_service = Service::factory('Account');

		// Get the api service
		$api_service = Service::factory('Api');

		try
		{
			// Get current logged in user account
			$user = $api_service->get_account();

			// Update the account
			$account = $account_service->get(array('id' => $id));

			// Check if we could get the account
			$api_service->check_access($account, $user);

			// Update data
			$account_service->remove($account);

			// Return appropriate HTTP code
			$this->response($api_service->build_response_succeed(
				'Account deleted'
			), 200);
		}
		catch (Service_Exception_NotFound $e)
		{
			$this->response($api_service->build_response_failed($e->getMEssage()), 404);
		}
		catch (Service_Exception_PermissionDenied $e)
		{
			$this->response($api_service->build_response_failed($e->getMEssage()), 403);
		}
		catch (Exception $e)
		{
			Kohana_Exception::log($e, Log::ERROR);
			$this->response($api_service->build_response_failed($e->getMessage()), 400);
		}
	}

} // End SampleApp_Controller_Api_V1_Account

