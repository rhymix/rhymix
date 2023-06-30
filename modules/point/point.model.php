<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pointModel
 * @author NAVER (developers@xpressengine.com)
 * @brief The model class fo the point module
 */
class PointModel extends Point
{
	/**
	 * Cache for module configuration.
	 */
	protected static $_module_config_cache = array();

	/**
	 * Cache for member points.
	 */
	protected static $_member_point_cache = array();

	/**
	 * @brief Check if there is points information
	 */
	public static function isExistsPoint($member_srl)
	{
		$args = new stdClass;
		$args->member_srl = abs($member_srl);
		$output = executeQuery('point.getPoint', $args, array('member_srl'));

		return isset($output->data->member_srl);
	}

	/**
	 * @brief Get the points
	 */
	public static function getPoint($member_srl, $from_db = false, &$exists = null)
	{
		$member_srl = abs($member_srl);

		// Get from instance memory
		if (!$from_db && isset(self::$_member_point_cache[$member_srl]))
		{
			$exists = true;
			return self::$_member_point_cache[$member_srl];
		}

		// Get from object cache
		$cache_key = sprintf('member:point:%d', $member_srl);
		if (!$from_db)
		{
			$point = Rhymix\Framework\Cache::get($cache_key);
			if ($point !== null)
			{
				$exists = true;
				return self::$_member_point_cache[$member_srl] = $point;
			}
		}

		// Get from file cache
		$cache_path = sprintf(RX_BASEDIR . 'files/member_extra_info/point/%s', getNumberingPath($member_srl));
		$cache_filename = sprintf('%s/%d.cache.txt', $cache_path, $member_srl);
		if (!$from_db && file_exists($cache_filename))
		{
			$point = trim(Rhymix\Framework\Storage::read($cache_filename));
			if ($point !== '')
			{
				$exists = true;
				return self::$_member_point_cache[$member_srl] = intval($point);
			}
		}

		// Get from the DB
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$output = executeQuery('point.getPoint', $args);
		if (isset($output->data->member_srl))
		{
			$exists = true;
			$point = intval($output->data->point);
		}
		else
		{
			$exists = false;
			return 0;
		}

		// Save to cache
		self::$_member_point_cache[$member_srl] = $point;
		if (Rhymix\Framework\Cache::getDriverName() !== 'dummy')
		{
			Rhymix\Framework\Cache::set($cache_key, $point);
		}
		else
		{
			Rhymix\Framework\Storage::write($cache_filename, $point);
		}

		return $point;
	}

	/**
	 * @brief Get the level
	 */
	public static function getLevel($point, $level_step)
	{
		$level_count = count($level_step ?: []);
		for ($level = 0; $level <= $level_count; $level++)
		{
			if (isset($level_step[$level]) && $point < $level_step[$level])
			{
				break;
			}
		}
		return $level - 1;
	}

	/**
	 * @deprecated
	 */
	public function getMembersPointInfo()
	{
		$member_srls = Context::get('member_srls');
		$member_srls = array_unique(explode(',', $member_srls));
		if (!count($member_srls))
		{
			return;
		}

		$logged_info = Context::get('logged_info');
		if (!$logged_info->member_srl)
		{
			return;
		}
		if (!ModuleModel::isSiteAdmin($logged_info))
		{
			$member_srls = array_filter($member_srls, function($member_srl) use($logged_info) { return $member_srl == $logged_info->member_srl; });
			if (!count($member_srls))
			{
				return;
			}
		}

		$config = ModuleModel::getModuleConfig('point');

		$info = array();
		foreach($member_srls as $v)
		{
			$obj = new stdClass;
			$obj->point = self::getPoint($v);
			$obj->level = self::getLevel($obj->point, $config->level_step);
			$obj->member_srl = $v;
			$info[] = $obj;
		}

		$this->add('point_info',$info);
	}

	/**
	 * @brief Get a list of points members list
	 */
	public static function getMemberList($args = null, $columnList = array())
	{
		// Arrange the search options
		$args->is_admin = Context::get('is_admin')=='Y'?'Y':'';
		$args->is_denied = Context::get('is_denied')=='Y'?'Y':'';
		$args->selected_group_srl = Context::get('selected_group_srl');

		$search_target = trim(Context::get('search_target'));
		$search_keyword = trim(Context::get('search_keyword'));

		// if search keyword is emtpy, show all list
		if(!$search_keyword)
		{
			unset($args->is_admin, $args->is_denied, $args->selected_group_srl, $search_target);
		}

		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'user_id' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_id = $search_keyword;
					break;
				case 'user_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_name = $search_keyword;
					break;
				case 'nick_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_nick_name = $search_keyword;
					break;
				case 'email_address' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_email_address = $search_keyword;
					break;
				case 'regdate' :
					$args->s_regdate = $search_keyword;
					break;
				case 'last_login' :
					$args->s_last_login = $search_keyword;
					break;
				case 'extra_vars' :
					$args->s_extra_vars = $search_keyword;
					break;
			}
		}
		// If there is a selected_group_srl, change the "query id" (for table join)
		if($args->selected_group_srl)
		{
			$query_id = 'point.getMemberListWithinGroup';
		}
		else
		{
			$query_id = 'point.getMemberList';
		}

		$output = executeQuery($query_id, $args, $columnList);

		if($output->total_count)
		{
			$config = ModuleModel::getModuleConfig('point');

			foreach($output->data as $key => $val)
			{
				$output->data[$key]->level = self::getLevel($val->point, $config->level_step);
			}
		}

		return $output;
	}

	/**
	 * Get point configuration for module, falling back to defaults if not set.
	 *
	 * @param int $module_srl
	 * @param string $config_key
	 * @return int
	 */
	public static function getModulePointConfig($module_srl, $config_key)
	{
		$module_srl = intval($module_srl);
		$config_key = strval($config_key);
		if (!$module_srl || !$config_key)
		{
			return 0;
		}

		if ($module_srl)
		{
			if (!isset(self::$_module_config_cache[$module_srl]))
			{
				self::$_module_config_cache[$module_srl] = ModuleModel::getModulePartConfig('point', $module_srl);
				if (is_object(self::$_module_config_cache[$module_srl]))
				{
					self::$_module_config_cache[$module_srl] = get_object_vars(self::$_module_config_cache[$module_srl]);
				}
			}
			$module_config = self::$_module_config_cache[$module_srl];
		}
		else
		{
			$module_config = array();
		}

		if (isset($module_config[$config_key]) && $module_config[$config_key] !== '')
		{
			$point = $module_config[$config_key];
		}
		else
		{
			$default_config = self::getConfig();
			$point = $default_config->{$config_key};
		}

		return intval($point);
	}
}
/* End of file point.model.php */
/* Location: ./modules/point/point.model.php */
