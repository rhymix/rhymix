<?php

namespace Rhymix\Modules\Member\Controllers;

class Device extends \member
{
	/**
	 * Automatically recognize device token from header or cookie and register it.
	 * 
	 * If the device is already registered, just update its last active date.
	 * 
	 * @return \BaseObject
	 */
	public function autoRegisterDevice(int $member_srl): \BaseObject
	{
		$device_token = $this->_getDeviceToken();
		if ($device_token)
		{
			$output = executeQuery('member.getMemberDevice', ['device_token' => $device_token]);
			if (!$output->data || $output->data->member_srl != $member_srl)
			{
				$output = $this->procMemberRegisterDevice($member_srl, $device_token);
				if ($output instanceof \BaseObject && !$output->toBool())
				{
					return $output;
				}
				$this->_setDeviceKey();
			}
			else
			{
				executeQuery('member.updateMemberDeviceLastActiveDate', ['device_token' => $device_token]);
			}
		}
		return new \BaseObject;
	}
	
	/**
	 * Register device
	 */
	public function procMemberRegisterDevice($member_srl = null, $device_token = null)
	{
		// Set the response method to JSON, but only if this method was called directly.
		// The response method will remain unchanged, for example, when this method was called by autoRegisterDevice()
		if (\Context::get('act') === 'procMemberRegisterDevice')
		{
			\Context::setResponseMethod('JSON');
		}

		// Check user_id, password, device_token
		$allow_guest_device = config('push.allow_guest_device');
		$user_id = \Context::get('user_id');
		$password = \Context::get('password');
		$device_token = $device_token ?? \Context::get('device_token');
		$device_model = escape(\Context::get('device_model'));

		// Return an error when id and password doesn't exist
		if (!$member_srl && $this->user->member_srl)
		{
			$member_srl = $this->user->member_srl;
		}
		if (!$member_srl && !$user_id && !$allow_guest_device)
		{
			return new \BaseObject(-1, 'NULL_USER_ID');
		}
		if (!$member_srl && !$password && !$allow_guest_device)
		{
			return new \BaseObject(-1, 'NULL_PASSWORD');
		}
		if (!$device_token)
		{
			return new \BaseObject(-1, 'NULL_DEVICE_TOKEN');
		}

		// Get device information
		$browserInfo = \Rhymix\Framework\UA::getBrowserInfo();
		$device_type = escape(strtolower($browserInfo->os));
		$device_version = $browserInfo->os_version;
		if (!$device_model)
		{
			$device_model = escape($browserInfo->device);
		}

		// Detect device token type
		if (preg_match('/^[0-9a-z]{64}$/', $device_token))
		{
			$device_token_type = 'apns';
		}
		elseif (preg_match('/^[0-9a-zA-Z:_-]+$/', $device_token) && strlen($device_token) > 64)
		{
			$device_token_type = 'fcm';
		}
		else
		{
			return new \BaseObject(-1, 'INVALID_DEVICE_TOKEN');
		}
		
		if ($member_srl)
		{
			$member_srl = intval($member_srl);
		}
		elseif ($user_id && $password)
		{
			$output = \memberController::getInstance()->procMemberLogin($user_id, $password);
			if(!$output->toBool())
			{
				return new \BaseObject(-1, 'LOGIN_FAILED');
			}
			$logged_info = \Context::get('logged_info');
			$member_srl = intval($logged_info->member_srl);
		}
		else
		{
			$logged_info = null;
			$member_srl = 0;
		}

		// Generate keys
		$random_key = \Rhymix\Framework\Security::getRandom();
		$device_key = hash_hmac('sha256', $random_key, $member_srl . ':' . config('crypto.authentication_key'));

		// Prepare query arguments
		$args = new \stdClass;
		$args->device_srl = getNextSequence();
		$args->member_srl = $member_srl;
		$args->device_token = $device_token;
		$args->device_token_type = $device_token_type;
		$args->device_key = $device_key;
		$args->device_type = $device_type;
		$args->device_version = $device_version;
		$args->device_model = $device_model;
		
		// Call trigger (before)
		$trigger_output = \ModuleHandler::triggerCall('member.insertMemberDevice', 'before', $args);
		if(!$trigger_output->toBool()) return $trigger_output;

		// Start transaction
		$oDB = \DB::getInstance();
		$oDB->begin();
		
		// Remove duplicated token key
		executeQuery('member.deleteMemberDevice', ['device_token' => $device_token]);
		
		// Create member_device
		$output = executeQuery('member.insertMemberDevice', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		
		// Call trigger (after)
		\ModuleHandler::triggerCall('member.insertMemberDevice', 'after', $args);
		
		$oDB->commit();

		// Set parameters
		$this->add('member_srl', $member_srl);
		$this->add('user_id', $logged_info ? $logged_info->user_id : null);
		$this->add('user_name', $logged_info ? $logged_info->user_name : null);
		$this->add('nick_name', $logged_info ? $logged_info->nick_name : null);
		$this->add('device_key', $random_key);
	}

	/**
	 * Automatically log-in to registered device
	 */
	public function procMemberLoginWithDevice()
	{
		\Context::setResponseMethod('JSON');

		// Check member_srl, device_token, device_key
		$allow_guest_device = config('push.allow_guest_device');
		$member_srl = abs(\Context::get('member_srl'));
		$device_token = strval(\Context::get('device_token'));
		$random_key = strval(\Context::get('device_key'));

		// Return an error when id, password and device_key doesn't exist
		if (!$member_srl && !$allow_guest_device)
		{
			return new \BaseObject(-1, 'NULL_MEMBER_SRL');
		}
		if (!$device_token)
		{
			return new \BaseObject(-1, 'NULL_DEVICE_TOKEN');
		}
		if (!$random_key)
		{
			return new \BaseObject(-1, 'NULL_DEVICE_KEY');
		}

		// Check the device token and key.
		$args = new \stdClass;
		$args->member_srl = $member_srl;
		$args->device_token = $device_token;
		$args->device_key = hash_hmac('sha256', $random_key, $member_srl . ':' . config('crypto.authentication_key'));
		$output = executeQuery('member.getMemberDevice', $args);
		if (!$output->toBool())
		{
			return new \BaseObject(-1, 'DEVICE_RETRIEVE_FAILED');
		}
		if (!$output->data || !is_object($output->data))
		{
			return new \BaseObject(-1, 'UNREGISTERED_DEVICE');
		}

		// Log-in
		if($member_srl)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl);
			$output = \memberController::getInstance()->doLogin($member_info->user_id);
			if(!$output->toBool())
			{
				return new \BaseObject(-1, 'LOGIN_FAILED');
			}
		}
		else
		{
			$member_info = null;
		}
		
		// Update last active date
		executeQuery('member.updateMemberDeviceLastActiveDate', ['device_token' => $device_token]);
		
		$this->add('member_srl', $member_srl);
		$this->add('user_id', $member_info ? $member_info->user_id : null);
		$this->add('user_name', $member_info ? $member_info->user_name : null);
		$this->add('nick_name', $member_info ? $member_info->nick_name : null);
	}
	
	/**
	 * Unregister a registered device.
	 * 
	 * This action requires a device token and matching device key.
	 * It is intended to be called by mobile applications.
	 */
	public function procMemberUnregisterDevice()
	{
		\Context::setResponseMethod('JSON');
		
		// Check member_srl, device_token, device_key
		$allow_guest_device = config('push.allow_guest_device');
		$member_srl = abs(\Context::get('member_srl'));
		$device_token = strval(\Context::get('device_token'));
		$random_key = strval(\Context::get('device_key'));

		// Return an error when id, password and device_key doesn't exist
		if (!$member_srl && !$allow_guest_device)
		{
			return new \BaseObject(-1, 'NULL_MEMBER_SRL');
		}
		if (!$device_token)
		{
			return new \BaseObject(-1, 'NULL_DEVICE_TOKEN');
		}
		if (!$random_key)
		{
			return new \BaseObject(-1, 'NULL_DEVICE_KEY');
		}
		
		// Check the device token and key.
		$args = new \stdClass;
		$args->member_srl = $member_srl;
		$args->device_token = $device_token;
		$args->device_key = hash_hmac('sha256', $random_key, $member_srl . ':' . config('crypto.authentication_key'));
		$output = executeQuery('member.getMemberDevice', $args);
		if (!$output->toBool())
		{
			return new \BaseObject(-1, 'DEVICE_RETRIEVE_FAILED');
		}
		if (!$output->data || !is_object($output->data))
		{
			return new \BaseObject(-1, 'UNREGISTERED_DEVICE');
		}
		
		// Delete the device.
		$args = new \stdClass;
		$args->device_token = $device_token;
		$output = executeQuery('member.deleteMemberDevice', $args);
		if (!$output->toBool())
		{
			return new \BaseObject(-1, 'DELETE_FAILED');
		}
	}
	
	/**
	 * Delete a registered device.
	 * 
	 * This action requires only the device_srl, but it must belong to the currently logged in member.
	 * It is intended to be called from the web frontend.
	 */
	public function procMemberDeleteDevice()
	{
		// Check the device_srl and member_srl of the currently logged in member.
		$device_srl = intval(\Context::get('device_srl'));
		if (!$device_srl)
		{
			throw new \Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$member_srl = $this->user->member_srl;
		if (!$member_srl)
		{
			throw new \Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// Check that the device_srl matches the member.
		$args = new \stdClass;
		$args->device_srl = $device_srl;
		$args->member_srl = $member_srl;
		$output = executeQuery('member.getMemberDevice', $args);
		if (!$output->data || !is_object($output->data))
		{
			throw new \Rhymix\Framework\Exceptions\TargetNotFound;
		}
		
		// Delete the device.
		$args = new \stdClass;
		$args->device_token = $output->data->device_token;
		$output = executeQuery('member.deleteMemberDevice', $args);
		return $output;
	}
	
	/**
	 * Get device token from POST parameter, HTTP header or cookie
	 * 
	 * @return string|null
	 */
	protected function _getDeviceToken()
	{
		// POST parameter named device_token
		$device_token = $_POST['device_token'] ?? null;
		if ($device_token && is_string($device_token) && $device_token !== '')
		{
			return $device_token;
		}
		
		// HTTP header named X-Device-Token
		$device_token = $_SERVER['HTTP_X_DEVICE_TOKEN'] ?? null;
		if ($device_token)
		{
			return $device_token;
		}
		
		// Cookie named device_token
		$device_token = $_COOKIE['device_token'] ?? null;
		if ($device_token)
		{
			return $device_token;
		}
	}
	
	/**
	 * Set device key via header or cookie
	 * 
	 * @return void
	 */
	protected function _setDeviceKey()
	{
		$member_srl = $this->get('member_srl');
		$device_key = $this->get('device_key');
		if (!$member_srl || !$device_key)
		{
			return;
		}
		
		// Set header if header was given, or cookie otherwise
		if (isset($_SERVER['HTTP_X_DEVICE_TOKEN']))
		{
			header('X-Device-Key: ' . urlencode($member_srl . ':' . $device_key));
		}
		else
		{
			setcookie('device_key', $member_srl . ':' . $device_key, time() + 60, \RX_BASEURL, null, !!config('session.use_ssl_cookies'), true);
		}
	}
}
