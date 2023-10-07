<?php

namespace Rhymix\Framework;

/**
 * The config class.
 */
class Config
{
	/**
	 * System configuration is stored here.
	 */
	protected static $_config = array();

	/**
	 * Location of configuration files.
	 */
	public const CONFIG_FILENAME = 'files/config/config.php';
	public const OLD_DB_CONFIG_PATH = 'files/config/db.config.php';
	public const OLD_FTP_CONFIG_PATH = 'files/config/ftp.config.php';
	public const OLD_LANG_CONFIG_PATH = 'files/config/lang_selected.info';
	public const DEFAULT_CONFIG_PATH = 'common/defaults/config.php';

	/**
	 * Load system configuration.
	 *
	 * @return array
	 */
	public static function init(): array
	{
		if (file_exists(\RX_BASEDIR . self::CONFIG_FILENAME))
		{
			ob_start();
			self::$_config = (include \RX_BASEDIR . self::CONFIG_FILENAME);
			ob_end_clean();
		}
		else
		{
			if (self::$_config = Parsers\ConfigParser::convert())
			{
				self::save();
			}
		}

		if (isset(self::$_config['namespaces']) && !empty(self::$_config['namespaces']['regexp']))
		{
			$GLOBALS['RX_NAMESPACES'] = self::$_config['namespaces'];
		}

		return self::$_config ?: [];
	}

	/**
	 * Get all system configuration.
	 *
	 * @return array
	 */
	public static function getAll(): array
	{
		return self::$_config ?: [];
	}

	/**
	 * Get default system configuration.
	 *
	 * @return array
	 */
	public static function getDefaults(): array
	{
		return (include \RX_BASEDIR . self::DEFAULT_CONFIG_PATH);
	}

	/**
	 * Get a system configuration value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function get(string $key)
	{
		if (!count(self::$_config))
		{
			self::init();
		}
		$data = self::$_config;
		$key = explode('.', $key);
		foreach ($key as $step)
		{
			if ($key === '' || !isset($data[$step]))
			{
				return null;
			}
			$data = $data[$step];
		}
		return $data;
	}

	/**
	 * Set a system configuration value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function set(string $key, $value): void
	{
		if (!count(self::$_config))
		{
			self::init();
		}
		$data = &self::$_config;
		$key = explode('.', $key);
		foreach ($key as $step)
		{
			$data = &$data[$step];
		}
		$data = $value;
	}

	/**
	 * Set all system configuration.
	 *
	 * @param array $config
	 * @return void
	 */
	public static function setAll(array $config): void
	{
		self::$_config = $config;
	}

	/**
	 * Save the current system configuration.
	 *
	 * @param array $config (optional)
	 * @return bool
	 */
	public static function save(?array $config = null): bool
	{
		if ($config)
		{
			self::setAll($config);
		}

		// Backup the main config file.
		$config_filename = \RX_BASEDIR . self::CONFIG_FILENAME;
		if (Storage::exists($config_filename))
		{
			$backup_filename = \RX_BASEDIR . self::CONFIG_FILENAME . '.backup.' . time() . '.php';
			$result = Storage::copy($config_filename, $backup_filename);
			clearstatcache(true, $backup_filename);
			if (!$result || filesize($config_filename) !== filesize($backup_filename))
			{
				return false;
			}
		}

		// Save the main config file.
		$buff = '<?php' . "\n" . '// Rhymix System Configuration' . "\n" . 'return ' . self::serialize(self::$_config) . ';' . "\n";
		$result = Storage::write($config_filename, $buff) ? true : false;
		clearstatcache(true, $config_filename);
		if (!$result || filesize($config_filename) !== strlen($buff))
		{
			return false;
		}

		// Remove the backup file.
		if (isset($backup_filename))
		{
			Storage::delete($backup_filename);
		}

		// Save XE-compatible config files.
		$warning = '// THIS FILE IS NOT USED IN RHYMIX.' . "\n" . '// TO MODIFY SYSTEM CONFIGURATION, EDIT config.php INSTEAD.';
		$buff = '<?php' . "\n" . $warning . "\n";
		Storage::write(\RX_BASEDIR . self::OLD_DB_CONFIG_PATH, $buff);
		Storage::write(\RX_BASEDIR . self::OLD_FTP_CONFIG_PATH, $buff);
		return true;
	}

	/**
	 * Serialize a value for insertion into a PHP-based configuration file.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function serialize($value): string
	{
		if (is_object($value))
		{
			return '(object)' . self::serialize((array)$value);
		}
		elseif (is_array($value))
		{
			$value = var_export($value, true);
			$value = preg_replace('/array \(\n/', "array(\n", $value);
			$value = preg_replace('/=>\s+array\(\n/', "=> array(\n", $value);
			$value = preg_replace('/array\(\s*\n\s*\)/', 'array()', $value);
			$value = preg_replace_callback('/\n(\x20+)/', function($m) {
				return "\n" . str_repeat("\t", intval(strlen($m[1]) / 2));
			}, $value);
			$value = preg_replace('/\n(\t+)[0-9]+ => /', "\n\$1", $value);
			return $value;
		}
		else
		{
			return var_export($value, true);
		}
	}
}
