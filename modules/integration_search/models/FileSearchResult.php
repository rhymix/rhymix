<?php

namespace Rhymix\Modules\Integration_Search\Models;

use Context;
use FileHandler;

#[\AllowDynamicProperties]
class FileSearchResult
{
	/**
	 * Properties of the file.
	 */
	public $file_srl;
	public $file_size;
	public $filename;
	public $uploaded_filename;
	public $download_count;
	public $download_url;
	public $video_thumbnail_url;
	public $target_srl;
	public $type;

	/**
	 * Properties of the upload target.
	 */
	public $url;
	public $regdate;
	public $nick_name;

	/**
	 * Get a thumbnail.
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $type
	 * @return string
	 */
	public function getThumbnail(int $width = 120, int $height = 0, string $type = 'crop'): string
	{
		if ($this->type !== 'image')
		{
			return '';
		}

		$thumbnail_path = sprintf('files/thumbnails/%s', getNumberingPath($this->file_srl, 3));
		if(!is_dir($thumbnail_path))
		{
			FileHandler::makeDir($thumbnail_path);
		}
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height ?: $width, $type);
		$thumbnail_url = \RX_BASEURL . $thumbnail_file;
		if (!file_exists($thumbnail_file))
		{
			FileHandler::createImageFile($this->uploaded_filename, $thumbnail_file, $width, $height ?: $width, 'jpg', $type, 50);
		}
		return $thumbnail_url;
	}

	/**
	 * Display video.
	 *
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public function displayVideo(int $width = 120, int $height = 0): string
	{
		if ($this->type !== 'multimedia')
		{
			return '';
		}

		$options = new \stdClass;
		if ($this->video_thumbnail_url)
		{
			$options->thumbnail = $this->video_thumbnail_url;
		}

		return vsprintf('<script>displayMultimedia(%s, %d, %d, %s);</script>', [
			json_encode(\RX_BASEURL . preg_replace('!^\.\/!', '', $this->uploaded_filename)),
			$width,
			$height ?: $width,
			json_encode($options),
		]);
	}

	/**
	 * Magic method to generate the 'src' attribute for backward compatibility.
	 *
	 * For images, it returns a 120x120 thumbnail.
	 * For videos, it returns a 80x80 preview.
	 * For other types of files, this method returns an empty string.
	 */
	public function __get(string $key)
	{
		if ($key === 'src')
		{
			if ($this->type === 'image')
			{
				return vsprintf('<img src="%s" alt="%s" width="120" height="120" class="thumb" />', [
					$this->getThumbnail(120, 120),
					escape($this->filename, false),
				]);
			}
			elseif ($this->type === 'multimedia')
			{
				return $this->displayVideo(80, 80);
			}
			else
			{
				return '';
			}
		}
		else
		{
			return null;
		}
	}
}
