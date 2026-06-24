<?php

namespace Rhymix\Modules\Autoinstall\Models;

use Rhymix\Framework\HTTP;
use Rhymix\Framework\Storage;
use Context;
use BaseObject;

class Installer
{
	/**
	 * Download a package.
	 *
	 * @param Package $package
	 * @return BaseObject
	 */
	public static function downloadPackage(Package $package): BaseObject
	{
		// Check if the package has a download URL.
		$download_url = $package->extra_vars->install_url ?? '';
		if (!$download_url)
		{
			return new BaseObject(-1, 'msg_autoinstall_package_has_no_download_url');
		}

		// Generate the temp file name.
		$hash = hash_hmac('sha256', $download_url, config('crypto.authentication_key'));
		$temp_filename = \RX_BASEDIR . 'files/cache/autoinstall/' . $hash . '.zip';
		if (Storage::exists($temp_filename))
		{
			Storage::delete($temp_filename);
		}

		// Make the download request.
		$request = HTTP::download($download_url, $temp_filename, 'GET', null, [], [], ['timeout' => 30]);
		if ($request->getStatusCode() !== 200)
		{
			return new BaseObject(-1, 'msg_autoinstall_download_failed');
		}
		if (!Storage::exists($temp_filename) || filesize($temp_filename) === 0)
		{
			return new BaseObject(-1, 'msg_autoinstall_download_failed');
		}

		return new BaseObject();
	}

	/**
	 * Check if a package has been successfully downloaded.
	 *
	 * @param Package $package
	 * @return BaseObject
	 */
	public static function isPackageDownloaded(Package $package): BaseObject
	{
		// Check if the package has a download URL.
		$download_url = $package->extra_vars->install_url ?? '';
		if (!$download_url)
		{
			return new BaseObject(-1, 'msg_autoinstall_package_has_no_download_url');
		}

		// Check if the temp file exists and is not empty.
		$hash = hash_hmac('sha256', $download_url, config('crypto.authentication_key'));
		$temp_filename = \RX_BASEDIR . 'files/cache/autoinstall/' . $hash . '.zip';
		if (!Storage::exists($temp_filename) || filesize($temp_filename) === 0)
		{
			return new BaseObject(-1, 'msg_autoinstall_download_failed');
		}

		$output = new BaseObject();
		$output->add('temp_filename', $temp_filename);
		return $output;
	}

	/**
	 * Install a package.
	 *
	 * @param Package $package
	 * @param string $mode
	 * @return BaseObject
	 */
	public static function installPackage(Package $package, string $mode = 'install'): BaseObject
	{
		// Check if the package has been downloaded.
		$output = self::isPackageDownloaded($package);
		if (!$output->toBool())
		{
			return $output;
		}

		// Open the zip file.
		$temp_filename = $output->get('temp_filename');
		$zip = new \ZipArchive();
		if ($zip->open($temp_filename) !== true)
		{
			return new BaseObject(-1, 'msg_autoinstall_extraction_failed');
		}

		// Determine the top-level folder within the zip file.
		$install_basedir = \RX_BASEDIR . rtrim(ltrim($package->install_path, './'), '/') . '/';
		$package_name = basename($package->install_path);
		$info_filename = Package::INFO_FILES[$package->type] ?? 'conf/info.xml';
		if ($zip->statName($info_filename))
		{
			$top_level_folder = '';
		}
		elseif ($zip->statName($package_name . '/' . $info_filename))
		{
			$top_level_folder = $package_name . '/';
		}
		else
		{
			$zip->close();
			Storage::delete($temp_filename);
			return new BaseObject(-1, 'msg_autoinstall_extraction_failed');
		}

		// Create a map of files and directories to be extracted.
		$map = [];

		// Pre-check all entries for validity and permissions, before actually extracting anything.
		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			// Validate the file name and size.
			$info = $zip->statIndex($i);
			$info['name'] = preg_replace('![\\\\/]+!', '/', $info['name']);
			if (preg_match('/\/\.\.\/|\/\.\.$|^\.\.\/|^\.\.$/', $info['name']))
			{
				continue;
			}
			if ($info['size'] > 1024 * 1024 * 100) // 100MB
			{
				continue;
			}

			// Skip entries that are outside of the top-level folder.
			if ($top_level_folder !== '' && !str_starts_with($info['name'], $top_level_folder))
			{
				continue;
			}

			// Determine the path to extract to.
			$relative_path = substr($info['name'], strlen($top_level_folder));
			$absolute_path = $install_basedir . $relative_path;

			// Check permissions on the path.
			$permissions = false;
			$last_checked_path = \RX_BASEDIR;
			if (Storage::exists($absolute_path))
			{
				$permissions = Storage::isWritable($absolute_path);
				$last_checked_path = $absolute_path;
			}
			else
			{
				$parent_path = dirname($absolute_path);
				while (strlen($parent_path) > strlen(\RX_BASEDIR))
				{
					if (Storage::exists($parent_path))
					{
						$permissions = Storage::isWritable($parent_path);
						$last_checked_path = $parent_path;
						break;
					}
					$parent_path = dirname($parent_path);
				}
			}
			if ($permissions === false)
			{
				$zip->close();
				Storage::delete($temp_filename);
				$failed_path = './' . substr($last_checked_path, strlen(\RX_BASEDIR));
				return new BaseObject(-1, lang('autoinstall.msg_autoinstall_cannot_write') . "\n" . escape($failed_path));
			}

			// Add to the map.
			$map[$i] = $absolute_path;
		}

		// Extract each file.
		foreach ($map as $i => $absolute_path)
		{
			// Create the parent directory if it doesn't exist.
			$is_directory = substr($absolute_path, -1) === '/';
			if ($is_directory)
			{
				$dir = rtrim($absolute_path, '/');
			}
			else
			{
				$dir = dirname($absolute_path);
			}
			if (!Storage::exists($dir))
			{
				$result = Storage::createDirectory($dir);
				if (!$result)
				{
					$zip->close();
					Storage::delete($temp_filename);
					$failed_path = './' . substr($dir, strlen(\RX_BASEDIR));
					return new BaseObject(-1, lang('autoinstall.msg_autoinstall_cannot_write') . "\n" . escape($failed_path));
				}
			}

			// Extract the file.
			if (!$is_directory)
			{
				$stream = $zip->getStreamIndex($i);
				if (!$stream)
				{
					$zip->close();
					Storage::delete($temp_filename);
					$failed_path = './' . substr($absolute_path, strlen(\RX_BASEDIR));
					return new BaseObject(-1, lang('autoinstall.msg_autoinstall_extraction_failed') . "\n" . escape($failed_path));
				}
				$fp = fopen($absolute_path, 'wb');
				if (!$fp)
				{
					$zip->close();
					Storage::delete($temp_filename);
					$failed_path = './' . substr($absolute_path, strlen(\RX_BASEDIR));
					return new BaseObject(-1, lang('autoinstall.msg_autoinstall_extraction_failed') . "\n" . escape($failed_path));
				}
				stream_copy_to_stream($stream, $fp);
				fclose($stream);
				fclose($fp);
			}
		}

		// Remove the temp file.
		Storage::delete($temp_filename);
		return new BaseObject();
	}

	/**
	 * Uninstall a package.
	 *
	 * @param Package $package
	 * @return BaseObject
	 */
	public static function uninstallPackage(Package $package): BaseObject
	{
		// Determine the installation path and package name.
		$install_basedir = \RX_BASEDIR . rtrim(ltrim($package->install_path, './'), '/') . '/';
		$package_name = basename($package->install_path);
		$package_type = $package->type;
		if (Context::isDefaultPlugin($package_name, $package_type))
		{
			return new BaseObject(-1, 'msg_autoinstall_cannot_uninstall_default_plugin');
		}

		// Remove the installation path.
		if (!Storage::deleteDirectory($install_basedir))
		{
			return new BaseObject(-1, 'msg_autoinstall_uninstall_failed');
		}

		return new BaseObject();
	}
}
