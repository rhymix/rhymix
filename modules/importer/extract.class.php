<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * extract class
 * Class to save each file by using tags in the large xml
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/importer
 * @version 0.1
 */
class extract
{
	/**
	 * Temp file's key. made by md5 with filename
	 * @var string
	 */
	var $key = '';
	/**
	 * Temp cache file path
	 * @var string
	 */
	var $cache_path = './files/cache/importer';
	/**
	 * Temp index cache file path
	 * @var string
	 */
	var $cache_index_file = './files/cache/importer';
	/**
	 * File name
	 * @var string
	 */
	var $filename = null;
	/**
	 * Start tag
	 * @var string
	 */
	var $startTag = '';
	/**
	 * End tag
	 * @var string
	 */
	var $endTag = '';
	/**
	 * Item start tag
	 * @var string
	 */
	var $itemStartTag = '';
	/**
	 * Item end tag
	 * @var string
	 */
	var $itemEndTag = '';

	/**
	 * File resource
	 * @var string
	 */
	var $fd = null;
	/**
	 * Index file resource
	 * @var string
	 */
	var $index_fd = null;

	/**
	 * Start tag open status
	 * @var bool
	 */
	var $isStarted = false;
	/**
	 * End tag close status
	 * @var bool
	 */
	var $isFinished = true;

	/**
	 * Buffer
	 * @var string
	 */
	var $buff = 0;

	/**
	 * File count
	 * @var int
	 */
	var $index = 0;

	/**
	 * Get arguments for constructor, file name, start tag, end tag, tag name for each item
	 * @param string $filename
	 * @param string $startTag
	 * @param string $endTag
	 * @param string $itemTag
	 * @param string $itemEndTag
	 * @return Object
	 */
	function set($filename, $startTag, $endTag, $itemTag, $itemEndTag)
	{
		$this->filename = $filename;

		$this->startTag = $startTag;
		if($endTag) $this->endTag = $endTag;
		$this->itemStartTag = $itemTag;
		$this->itemEndTag = $itemEndTag;

		$this->key = md5($filename);

		$this->cache_path = './files/cache/importer/'.$this->key;
		$this->cache_index_file = $this->cache_path.'/index';

		if(!is_dir($this->cache_path)) FileHandler::makeDir($this->cache_path);

		return $this->openFile();
	}

	/**
	 * Open an indicator of the file
	 * @return Object
	 */
	function openFile()
	{
		FileHandler::removeFile($this->cache_index_file);
		$this->index_fd = fopen($this->cache_index_file,"a");
		// If local file
		if(strncasecmp('http://', $this->filename, 7) !== 0)
		{
			if(!file_exists($this->filename)) return new Object(-1,'msg_no_xml_file');
			$this->fd = fopen($this->filename,"r");
			// If remote file
		}
		else
		{
			$url_info = parse_url($this->filename);
			if(!$url_info['port']) $url_info['port'] = 80;
			if(!$url_info['path']) $url_info['path'] = '/';

			$this->fd = @fsockopen($url_info['host'], $url_info['port']);
			if(!$this->fd) return new Object(-1,'msg_no_xml_file');
			// If the file name contains Korean, do urlencode(iconv required)
			$path = $url_info['path'];
			if(preg_match('/[\xEA-\xED][\x80-\xFF]{2}/', $path)&&function_exists('iconv'))
			{
				$path_list = explode('/',$path);
				$cnt = count($path_list);
				$filename = $path_list[$cnt-1];
				$filename = urlencode(iconv("UTF-8","EUC-KR",$filename));
				$path_list[$cnt-1] = $filename;
				$path = implode('/',$path_list);
				$url_info['path'] = $path;
			}

			$header = sprintf("GET %s?%s HTTP/1.0\r\nHost: %s\r\nReferer: %s://%s\r\nConnection: Close\r\n\r\n", $url_info['path'], $url_info['query'], $url_info['host'], $url_info['scheme'], $url_info['host']);
			@fwrite($this->fd, $header);
			$buff = '';
			while(!feof($this->fd))
			{
				$buff .= $str = fgets($this->fd, 1024);
				if(!trim($str)) break;
			}
			if(preg_match('/404 Not Found/i',$buff)) return new Object(-1,'msg_no_xml_file');
		}

		if($this->startTag)
		{
			while(!feof($this->fd))
			{
				$str = fgets($this->fd, 1024);
				$pos = strpos($str, $this->startTag);
				if($pos !== false)
				{
					$this->buff = substr($this->buff, $pos+strlen($this->startTag));
					$this->isStarted = true;
					$this->isFinished = false;
					break;
				}
			}
		}
		else
		{
			$this->isStarted = true;
			$this->isFinished = false;
		}

		return new Object();
	}

	/**
	 * Close an indicator of the file
	 * @return void
	 */
	function closeFile()
	{
		$this->isFinished = true;
		fclose($this->fd);
		fclose($this->index_fd);
	}

	function isFinished()
	{
		return $this->isFinished || !$this->fd || feof($this->fd);
	}

	/**
	 * Save item
	 * @return void
	 */
	function saveItems()
	{
		FileHandler::removeDir($this->cache_path.$this->key);
		$this->index = 0;
		while(!$this->isFinished())
		{
			$this->getItem();
		}
	}

	/**
	 * Merge item
	 * @return void
	 */
	function mergeItems($filename)
	{
		$this->saveItems();

		$filename = sprintf('%s/%s', $this->cache_path, $filename);

		$index_fd = fopen($this->cache_index_file,"r");
		$fd = fopen($filename,'w');

		fwrite($fd, '<items>');
		while(!feof($index_fd))
		{
			$target_file = trim(fgets($index_fd,1024));
			if(!file_exists($target_file)) continue;
			$buff = FileHandler::readFile($target_file);
			fwrite($fd, FileHandler::readFile($target_file));

			FileHandler::removeFile($target_file);
		}
		fwrite($fd, '</items>');
		fclose($fd);
	}

	/**
	 * Get item. Put data to buff
	 * @return void
	 */
	function getItem()
	{
		if($this->isFinished()) return;

		while(!feof($this->fd))
		{
			$startPos = strpos($this->buff, $this->itemStartTag);
			if($startPos !== false)
			{
				$this->buff = substr($this->buff, $startPos);
				$this->buff = preg_replace("/\>/",">\r\n",$this->buff,1);
				break;
			}
			elseif($this->endTag)
			{
				$endPos = strpos($this->buff, $this->endTag);
				if($endPos !== false)
				{
					$this->closeFile();
					return;
				}
			}
			$this->buff .= fgets($this->fd, 1024); 
		}

		$startPos = strpos($this->buff, $this->itemStartTag);
		if($startPos === false)
		{
			$this->closeFile();
			return;
		}

		$filename = sprintf('%s/%s.xml',$this->cache_path, $this->index++);
		fwrite($this->index_fd, $filename."\r\n");

		$fd = fopen($filename,'w');

		while(!feof($this->fd))
		{
			$endPos = strpos($this->buff, $this->itemEndTag);
			if($endPos !== false)
			{
				$endPos += strlen($this->itemEndTag);
				$buff = substr($this->buff, 0, $endPos);
				fwrite($fd, $this->_addTagCRTail($buff));
				fclose($fd);
				$this->buff = substr($this->buff, $endPos);
				break;
			}

			fwrite($fd, $this->_addTagCRTail($this->buff));
			$this->buff = fgets($this->fd, 1024);
		}
	}

	function getTotalCount()
	{
		return $this->index;
	}

	function getKey()
	{
		return $this->key;
	}

	function _addTagCRTail($str) {
		$str = preg_replace('/<\/([^>]*)></i', "</$1>\r\n<", $str);
		return $str;
	}
}
/* End of file extract.class.php */
/* Location: ./modules/importer/extract.class.php */
