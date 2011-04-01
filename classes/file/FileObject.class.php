<?php
    /**
    * @class FileObject
    * @author NHN (developers@xpressengine.com)
    * @brief file abstraction class 
    **/

    class FileObject extends Object
    {
        var $fp = null; ///< file descriptor
        var $path = null; ///< file path
        var $mode = "r"; ///< file open mode

        /**
         * @brief constructor 
         * @param[in] $path path of target file
         * @param[in] $mode file open mode 
         * @return file object 
         **/
        function FileObject($path, $mode)
        {
            if($path != null) $this->Open($path, $mode);
        }

        /**
         * @brief append target file's content to current file 
         * @param[in] $file_name path of target file
         * @return none 
         **/
        function append($file_name)
        {
            $target = new FileObject($file_name, "r");
            while(!$target->feof())
            {
                $readstr = $target->read();
                $this->write($readstr);
            }
            $target->close();
        }

        /**
         * @brief check current file meets eof
         * @return true: if eof. false: otherwise 
         **/
        function feof()
        {
            return feof($this->fp);
        }

        /**
         * @brief read from current file 
         * @param[in] $size size to read
         * @return read bytes 
         **/
        function read($size = 1024)
        {
            return fread($this->fp, $size);
        }


        /**
         * @brief write string to current file 
         * @param[in] $str string to write
         * @return written bytes. if failed, it returns false 
         **/
        function write($str)
        {
            $len = strlen($str);
            if(!$str || $len <= 0) return false;
            if(!$this->fp) return false;
            $written = fwrite($this->fp, $str);
            return $written;
        }

        /**
         * @brief open a file
         * @param[in] $path path of target file
         * @param[in] $mode file open mode 
         * @remarks if file is opened, close it and open the new path
         * @return true if succeed, false otherwise.
         */
        function open($path, $mode)
        {
            if($this->fp != null)
            {   
                $this->close();
            }
            $this->fp = fopen($path, $mode);
            if(! is_resource($this->fp) )
            {
                $this->fp = null; 
                return false;
            }
            $this->path = $path;
            return true;
        }

        /**
         * @brief return current file's path 
         * @return file path 
         **/
        function getPath()
        {
            if($this->fp != null)
            {
                return $this->path;
            }
            else
            {
                return null; 
            }
        }

        /**
         * @brief close file 
         * @return none 
         **/
        function close()
        {
            if($this->fp != null)
            {
                fclose($this->fp);
                $this->fp = null;
            }
        }

    }
?>
