<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  sessionController
 * @author NAVER (developers@xpressengine.com)
 * @brief The controller class of the session module
 */
class SessionController extends Session implements SessionHandlerInterface
{
	public function open($save_path, $session_name): bool
	{
		return true;
	}

	public function close(): bool
	{
		return true;
	}

	#[\ReturnTypeWillChange]
	public function read($id): string
	{
		if (!$id || !$this->session_started)
		{
			return '';
		}

		$output = executeQuery('session.getSession', ['session_key' => $id], ['session_key', 'cur_mid', 'val']);
		return $output->data->val ?? '';
	}

	public function write($id, $data): bool
	{
		if (!$id || !$this->session_started)
		{
			return false;
		}
		if ($data === '')
		{
			return $this->destroy($id);
		}

		$output = executeQuery('session.getSession', ['session_key' => $id], ['session_key']);
		$session_info = $output->data ?? null;

		$add_lifetime = max($this->lifetime, intval(ini_get('session.gc_maxlifetime')));

		$args = new stdClass();
		$args->session_key = $id;
		$args->expired = date('YmdHis', time() + $add_lifetime);
		$args->val = $data;
		$args->member_srl = Context::get('logged_info')->member_srl ?? 0;
		$args->ipaddress = \RX_CLIENT_IP;
		$args->last_update = date('YmdHis');
		$args->cur_mid = Context::get('mid');
		if (!$args->cur_mid)
		{
			$args->cur_mid = Context::get('current_module_info')->mid ?? null;
		}

		if ($session_info)
		{
			$output = executeQuery('session.updateSession', $args);
		}
		else
		{
			$output = executeQuery('session.insertSession', $args);
		}

		return $output->toBool();
	}

	#[\ReturnTypeWillChange]
	public function destroy($id): bool
	{
		if (!$id || !$this->session_started || headers_sent())
		{
			return false;
		}

		register_shutdown_function(function() use ($id) {
			executeQuery('session.deleteSession', ['session_key' => $id]);
		});

		return true;
	}

	#[\ReturnTypeWillChange]
	public function gc($max_lifetime): bool
	{
		if (!$this->session_started)
		{
			return false;
		}

		$output = executeQuery('session.gcSession');
		return $output->toBool();
	}
}
/* End of file session.controller.php */
/* Location: ./modules/session/session.controller.php */
