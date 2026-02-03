<?php

namespace Rhymix\Framework\Responses;

use Rhymix\Framework\AbstractResponse;
use Rhymix\Framework\UA;

/**
 * The file response class.
 *
 * This class can be used to send a file to the user.
 */
class FileResponse extends AbstractResponse
{
	/**
	 * Internal state.
	 */
	protected string $_source_path = '';
	protected string $_filename = '';
	protected int $_range_start = 0;
	protected ?int $_range_end = null;
	protected bool $_force_download = true;

	/**
	 * Get the path to the source file.
	 *
	 * @return string
	 */
	public function getSourcePath(): string
	{
		return $this->_source_path;
	}

	/**
	 * Set the path to the source file.
	 *
	 * @param string $source_path
	 * @return self
	 */
	public function setSourcePath(string $source_path): self
	{
		$this->_source_path = $source_path;
		return $this;
	}

	/**
	 * Get the filename to be displayed to the client.
	 *
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->_filename;
	}

	/**
	 * Set the range of bytes to be sent to the client.
	 *
	 * @param int $range_start
	 * @param ?int $range_end
	 * @return self
	 */
	public function setRange(int $range_start, ?int $range_end = null): self
	{
		$this->_range_start = $range_start;
		$this->_range_end = $range_end;
		return $this;
	}

	/**
	 * Get the range of bytes to be sent to the client.
	 *
	 * @return int
	 */
	public function getRangeStart(): int
	{
		return $this->_range_start;
	}

	/**
	 * Get the range of bytes to be sent to the client.
	 *
	 * @return ?int
	 */
	public function getRangeEnd(): ?int
	{
		return $this->_range_end;
	}

	/**
	 * Set the filename to be displayed to the client.
	 *
	 * @param string $filename
	 * @return self
	 */
	public function setFilename(string $filename): self
	{
		$this->_filename = $filename;
		return $this;
	}

	/**
	 * Force download, instead of displaying in a browser.
	 *
	 * This distinction may be significant for certain file types, such as PDFs.
	 *
	 * @param bool $force_download
	 * @return self
	 */
	public function forceDownload(bool $force_download = true): self
	{
		$this->_force_download = $force_download;
		return $this;
	}

	/**
	 * Render the full response.
	 *
	 * @return iterable
	 */
	public function render(): iterable
	{
		// Open the file and seek to the start position.
		$fp = fopen($this->_source_path, 'rb');
		if ($this->_range_start > 0)
		{
			fseek($fp, $this->_range_start);

		}

		// Is there a byte limit?
		$bytes_to_send = null;
		if ($this->_range_end !== null)
		{
			$bytes_to_send = $this->_range_end - $this->_range_start + 1;
		}

		// Read and send the file contents.
		while (!feof($fp) && ($bytes_to_send === null || $bytes_to_send > 0))
		{
			$read_length = 8192;
			if ($bytes_to_send !== null && $bytes_to_send < $read_length)
			{
				$read_length = $bytes_to_send;
			}

			$buffer = fread($fp, $read_length);
			if ($buffer === false)
			{
				break;
			}

			yield $buffer;

			if ($bytes_to_send !== null)
			{
				$bytes_to_send -= strlen($buffer);
			}
		}

		fclose($fp);
	}

	/**
	 * Get headers for this response.
	 *
	 * @return array
	 */
	public function getHeaders(): array
	{
		$headers = parent::getHeaders();

		// Add Content-Disposition header.
		$disposition = $this->_force_download ? 'attachment' : 'inline';
		if ($this->_filename !== '')
		{
			$filename_param = '; ' . UA::encodeFilenameForDownload($this->_filename);
		}
		else
		{
			$filename_param = '';
		}
		$headers[] = 'Content-Disposition: ' . $disposition . $filename_param;

		// Add Content-Length header.
		$filesize = filesize($this->_source_path);
		if ($this->_range_start === 0 && $this->_range_end === null)
		{
			$length = $filesize;
		}
		else
		{
			$length = max(0, $filesize - $this->_range_start);
			if ($this->_range_end !== null)
			{
				$length = max(0, $this->_range_end - $this->_range_start + 1);
			}
			array_unshift($headers, 'HTTP/1.1 206 Partial Content');
			$headers[] = 'Content-Range: bytes ' . vsprintf('%d-%d/%d', [
				$this->_range_start,
				($this->_range_end ? $this->_range_end : ($filesize - 1)),
				$filesize
			]);
		}
		$headers[] = 'Content-Length: ' . $length;
		$headers[] = 'Accept-Ranges: bytes';

		return $headers;
	}
}
