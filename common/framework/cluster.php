<?php

namespace Rhymix\Framework;

/**
 * The cluster class.
 */
class Cluster
{
	/**
	 * Send a request to invalidate the opcache of target files across the cluster.
	 * 
	 * @param array $targets
	 * @param array $options
	 * @return bool
	 */
	public static function sendOpcacheInvalidate(array $targets, array $options = [])
	{
		return self::broadcast('opcache_invalidate', $targets, $options);
	}
	
	/**
	 * Receive a request to invalidate the opcache of target files across the cluster.
	 * 
	 * @param array $targets
	 * @param array $options
	 * @return void
	 */
	public static function receiveOpcacheInvalidate(array $targets, array $options = [])
	{
		if (!function_exists('opcache_invalidate'))
		{
			return;
		}
		
		$force = isset($options['force']) ? $options['force'] : true;
		foreach ($targets as $target)
		{
			opcache_invalidate(\FileHandler::getRealPath($target), $force);
		}
	}
	
	/**
	 * General broadcast method.
	 * 
	 * @param string $action
	 * @param array $targets
	 * @param array $options
	 * @return bool
	 */
	public static function broadcast($action, array $targets, array $options = [])
	{
		// Get the list of recipients.
		$recipients = config('cluster.targets');
		if (!$recipients)
		{
			return false;
		}
		
		// Use curl_multi to broadcast a message to all servers in the cluster.
		$handle = curl_multi_init();
		$curls = array();
		$running = 0;
		$result = true;
		
		foreach($recipients as $recipient)
		{
			$curls[$recipient] = curl_init($recipient);
			curl_setopt($curls[$recipient], \CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curls[$recipient], \CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curls[$recipient], \CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curls[$recipient], \CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($curls[$recipient], \CURLOPT_TIMEOUT, 5);
			curl_setopt($curls[$recipient], \CURLOPT_POST, 1);
			curl_setopt($curls[$recipient], \CURLOPT_POSTFIELDS, http_build_query(array(
				'action' => 'rhymix:cluster:' . $action,
				'targets' => $targets,
				'options' => $options,
			)));
			curl_multi_add_handle($handle, $curls[$recipient]);
		}
		
		do {
			curl_multi_exec($handle, $running);
			curl_multi_select($handle);
		} while ($running > 0);
		
		// Check if any recipients returned an error.
		foreach ($recipients as $recipient)
		{
			$content = trim(curl_multi_getcontent($curls[$recipient]));
			curl_multi_remove_handle($handle, $curls[$recipient]);
			if ($content !== 'OK')
			{
				trigger_error('Cannot send cluster action \'' . $action . '\' to ' . $recipient, \E_USER_WARNING);
				$result = false;
			}
		}
		
		curl_multi_close($handle);
		return $result;
	}
	
	/**
	 * General receiver method.
	 * 
	 * @return void
	 */
	public static function receive()
	{
		// Check the name of the cluster action.
		$action = substr($_POST['action'], 15);
		
		// Check if the request came from a server in the same cluster.
		if (!Filters\IpFilter::inRanges(\RX_CLIENT_IP, config('cluster.sources')))
		{
			trigger_error('Received invalid cluster action \'' . $action . '\' from ' . \RX_CLIENT_IP, \E_USER_WARNING);
			header('HTTP/1.0 403 Forbidden');
			exit;
		}
		
		// Check the name of the action, the targets, and any options.
		$targets = $_POST['targets'];
		$options = $_POST['options'] ?: [];
		if (!$action || !is_array($targets) || !is_array($options))
		{
			trigger_error('Received invalid cluster action \'' . $action . '\' from ' . \RX_CLIENT_IP, \E_USER_WARNING);
			header('HTTP/1.0 403 Forbidden');
			exit;
		}
		
		// Call the appropriate method.
		switch ($action)
		{
			case 'opcache_invalidate':
				self::receiveOpcacheInvalidate($targets, $options);
				break;
			default:
				trigger_error('Received invalid cluster action \'' . $action . '\' from ' . \RX_CLIENT_IP, \E_USER_WARNING);
				header('HTTP/1.0 403 Forbidden');
				exit;
		}
		
		echo 'OK';
		exit;
	}
}
