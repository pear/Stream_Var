<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Stephan Schmidt <schst@php-tools.net>                       |
// |          partly based on an exmple by Wez Furlong                    |
// +----------------------------------------------------------------------+
//
//    $Id$

/**
 * stream is readable
 * This depends on the mode, that is used for opening the stream
 */
define("STREAM_VAR_READABLE", 1);

/**
 * stream is writeable
 * This depends on the mode, that is used for opening the stream
 */
define("STREAM_VAR_WRITEABLE", 2);

/**
 * Stream wrapper to access a variable
 *
 * Stream wrappers allow you to access any datasource using PHP's file manipulation functions
 * like fopen(), fclose(), fseek(), ftell(),.. as well as directory functions like 
 * opendir() readdir() and closedir().
 *
 * This wrapper allows you to access any variable using these functions.
 * You have to specify a scope (GLOBALS, _GET, _POST, etc.) as the host and
 * the variable name as the path. If you want to access a string, that is
 * stored in an array, you can use this array like you would use a directory.
 *
 * Usage:
 * <code>
 *  require_once '../Var.php';
 *  stream_wrapper_register( "var", "Stream_Var" );
 *
 *  $fp = fopen('var://GLOBALS/myArray/index','r');
 *  $data = fread($fp,100);
 *  fclose($fp);
 * </code>
 *
 * This wrapper also has support for dir functions, so it's possible to read any array, like
 * you would read a directory. The following code will list all keys in an array.
 * You could use fopen() to read the values for the keys.
 *
 * <code>
 *  require_once '../Var.php';
 *  stream_wrapper_register( "var", "Stream_Var" );
 *
 *  $dh = opendir('var://_SERVER');
 *  while ($entry = readdir($dh)) {
 *      echo $entry."<br />";
 *  }
 *  closedir($dh);
 * </code>
 *
 * This wrapper allows you to replace files and directories with structures in
 * memory in any application, that relies on filesystem functions. But keep in mind
 * that variables are not persistent during several request, unless you write to
 * var://SESSION.
 * But this can be used to replace temporary files with variables.
 *
 * @category Stream
 * @package  Stream_Var
 * @version  0.2.1
 * @author   Stephan Schmidt <schst@php-tools.net>
 */
class Stream_Var {

   /**
    * pointer to the variable
    * @var mixed   $_pointer
    */
    var $_pointer = null;

   /**
    * flag to indicate whether stream is open
    * @var boolean   $_open
    */
    var $_open = false;

   /**
    * position
    * @var integer $_pos
    */
    var $_pos = 0;

   /**
    * mode of the opened filw
    * @var integer   $_mode
    */
    var $_mode = 0;

   /**
    * method used by fopen
    *
    * @access public
    * @param  string  $path        path to the variable (e.g. var://GLOBALS/myArray/anyIndex)
    * @param  string  $mode        mode to open the stream, like 'r', 'w,',... ({@see fopen()})
    * @param  array   $options     options (not implemented yet)
    * @param  string  $opened_path this will be set to the actual opened path
    * @return boolean $success
    */
    function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);

        $scope   = $url["host"];
        $varpath = substr($url["path"], 1);
        
        $mode = strtolower($mode);
        switch ($mode{0}) {
            case    "r":
                $status = $this->_setPointer($scope, $varpath, false);
                $this->_mode = $this->_mode | STREAM_VAR_READABLE;
                break;
            case    "w":
            case    "a":
                $status = $this->_setPointer($scope, $varpath, true);
                $this->_mode = $this->_mode | STREAM_VAR_WRITEABLE;
                break;
            case    "x":
                $status = $this->_setPointer($scope, $varpath, false);
                if ($status) {
                    return false;
                }
                $status = $this->_setPointer($scope, $varpath, true);
                $this->_mode = $this->_mode | STREAM_VAR_WRITEABLE;
                break;
        }

        if (!$status) {
            return  false;
        }
        
        if (!is_scalar($this->_pointer)) {
            return false;
        }

        // start at zero
        $this->_pos = 0;
        $this->_open = true;
        $opened_path = $path;

        if ($mode{0} == 'a') {
            $this->stream_seek(0, SEEK_END);
        }

        if (strlen($mode) > 1 && $mode{1} == '+') {
            $this->_mode = $this->_mode | STREAM_VAR_READABLE | STREAM_VAR_WRITEABLE;
        }

        return true;
    }

   /**
    * check for end of stream
    *
    * @access public
    * @return boolean $eof  true if at end of stream
    */
    function stream_eof()
    {
        return ($this->_pos >= strlen($this->_pointer));
    }

   /**
    * return the current position
    *
    * @access public
    * @return integer $position current position in stream
    */
    function stream_tell()
    {
        return $this->_pos;
    }

   /**
    * close the stream
    *
    * @access public
    */
    function stream_close()
    {
        $this->_pos  = 0;
        $this->_open = false;
    }

   /**
    * read from the stream
    *
    * @access public
    * @param  integer $count    amount of bytes to read
    * @return string  $data     data that has been read
    */
    function stream_read($count)
    {
        if (!$this->_open) {
            return false;
        }

        if (!($this->_mode & STREAM_VAR_READABLE)) {
            return false;
        }

        $data = substr($this->_pointer, $this->_pos, $count);
        $this->_pos = $this->_pos + strlen($data);
        return $data;
    }

   /**
    * write to the stream
    *
    * @access public
    * @param  mixed   $data  data to write
    * @return integer $bytes number of bytes that were written
    */
    function stream_write($data)
    {
        if (!$this->_open) {
            return false;
        }
        
        if (!($this->_mode & STREAM_VAR_WRITEABLE)) {
            return false;
        }
        
        $datalen = strlen($data);
       
        $this->_pointer = substr($this->_pointer, 0, $this->_pos) . $data . substr($this->_pointer, $this->_pos+$datalen);
        $this->_pos = $this->_pos + $datalen;
        return $datalen;
    }

   /**
    * move the position in the stream
    *
    * @access public
    * @param  integer $offset  offset
    * @return integer $whence  point from which the offset should be calculated
    */
    function stream_seek($offset, $whence)
    {
        switch ($whence) {
            //  from start
            case SEEK_SET:
                if ($offset < strlen($this->_pointer) && $offset >= 0) {
                     $this->_pos = $offset;
                     return true;
                } else {
                     return false;
                }
                break;
            // from current position
            case SEEK_CUR:
                if ($offset >= 0) {
                     $this->_pos += $offset;
                     return true;
                } else {
                     return false;
                }
                break;
            // from the end
            case SEEK_END:
                if (strlen($this->_pointer) + $offset >= 0) {
                     $this->_pos = strlen($this->_pointer) + $offset;
                     return true;
                } else {
                     return false;
                }
                break;
            default:
                return false;
        }
    }

   /**
    * write all data to storage
    *
    * @access public
    */
    function stream_flush()
    {
        return true;
    }

   /**
    * return information about the stream
    *
    * @access public
    * @return array $stat   information about the stream (currently only the length is included)
    */
    function stream_stat()
    {
        $stat = array(
                      'size' => strlen($this->_pointer)
                    );
        return $stat;
    }


   /**
    * open 'directory'
    *
    * @access public
    * @param  string    $path    path to the array (i.e. the directory)
    * @param  array     $options not implemented, yet.
    * @return boolean   $success
    */
    function dir_opendir($path, $options)
    {
        $url = parse_url($path);

        $scope   = $url['host'];
        if (isset($url['path'])) {
            $varpath = substr($url['path'], 1);
        } else {
            $varpath = '';
        }
        
        if (!$status = $this->_setPointer($scope, $varpath))
            return  false;

        if (!is_array($this->_pointer)) {
            return false;
        }
        reset($this->_pointer);
        $this->_open = true;
        return true;
    }


   /**
    * close 'directory'
    *
    * @access public
    * @return boolean $success
    */
    function dir_closedir()
    {
        $this->_open = false;
        return true;
    }

   /**
    * rewind 'directory'
    *
    * @access public
    * @return boolean $success
    */
    function dir_rewinddir()
    {
        if (!$this->_open) {
            return false;
        }
        reset($this->_pointer);
        return true;
    }

   /**
    * read one entry from 'directory'
    *
    * @access public
    * @return mixed  $entry entry that has been read, or false if there are no entries left  
    */
    function dir_readdir()
    {
        if (!$this->_open) {
            return false;
        }
        if (current($this->_pointer) == count($this->_pointer)-1) {
            return false;
        }
        list($key) = each($this->_pointer);
        return $key;
    }

   /**
    * set the internal pointer
    *
    * Basically this method only sets the object property _pointer
    * as a reference to a variable
    *
    * @access private
    * @param  string  $scope    scope of the variable: GLOBAL, GET, POST, COOKIE, SESSION, SERVER, ENV
    * @param  string  $path     path to the variable. Array indices are seperated by a slash
    * @param  boolean $create   create the variable, if it does not exist
    */
    function _setPointer($scope, $path, $create = false )
    {
        $varpath = explode("/", $path);

        switch (strtoupper($scope)) {
            // GET variables
            case    "GET":
            case    "_GET":
                $this->_pointer = &$_GET;
                break;

            // POST variables
            case    "POST":
            case    "_POST":
                $this->_pointer = &$_POST;
                break;

            // SERVER variables
            case    "SERVER":
            case    "_SERVER":
                $this->_pointer = &$_SERVER;
                break;

            // SESSION variables
            case    "SESSION":
            case    "_SESSION":
                $this->_pointer = &$_SESSION;
                break;

            // COOKIE variables
            case    "COOKIE":
            case    "_COOKIE":
                $this->_pointer = &$_COOKIE;
                break;

            // ENV variables
            case    "ENV":
            case    "_ENV":
                $this->_pointer = &$_ENV;
                break;

            // global variables
            case    "GLOBALS":
            default:
                $this->_pointer = &$GLOBALS;
                break;
            
        }
        if (empty($varpath)) {
            return true;
        }

        while ($part = array_shift($varpath)) {
            if (!isset($this->_pointer[$part])) {
                if (!$create) {
                    return false;
                }
                if (!empty($varpath)) {
                    return false;
                }
                $this->_pointer[$part] = '';
            }
            $this->_pointer = &$this->_pointer[$part];
        }        
        return true;
    }
}
?>