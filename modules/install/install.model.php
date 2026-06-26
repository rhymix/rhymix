<?php

class installModel extends install
{
	function getSFTPList()
	{
		$this->add('list', []);
	}

	function getInstallFTPList()
	{
		$this->add('list', []);
	}
}
