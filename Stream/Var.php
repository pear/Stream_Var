<?php
/**
 * Stream wrapper to access a variable.
 *
 * PHP version 5+
 *
 * @category Stream
 * @package  Stream_Var
 * @author   Stephan Schmidt <schst@php-tools.net>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @link     http://pear.php.net/package/Stream_Var
 */

/**
 * Stream is readable.
 * This depends on the mode, that is used for opening the stream
 */
define("STREAM_VAR_READABLE", 1);

/**
 * Stream is writeable.
 * This depends on the mode, that is used for opening the stream
 */
define("STREAM_VAR_WRITEABLE", 2);

/**
 * Stream wrapper to access a variable
 *
 * Stream wrappers allow you to access any datasource using PHP's file manipulation
 * functions like fopen(), fclose(), fseek(), ftell(),.. as well as
 * directory functions like opendir() readdir() and closedir().
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
 * This wrapper also has support for dir functions, so it's possible to read
 * any array, like you would read a directory.
 * The following code will list all keys in an array.
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
 * Note that glob() does not work with stream wrappers.
 *
 * @category Stream
 * @package  Stream_Var
 * @author   Stephan Schmidt <schst@php-tools.net>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Stream_Var
 */
class Stream_Var
{

    /**
     * Pointer to the current variable
     *
     * @var mixed
     */
    protected $_pointer = null;

    /**
     * Flag to indicate whether stream is open
     *
     * @var boolean
     */
    protected $_open = false;

    /**
     * Position
     *
     * @var integer
     */
    protected $_pos = 0;

    /**
     * Mode of the opened file
     *
     * @var integer
     */
    protected $_mode = 0;

    public $context;

    /**
     * Method used by fopen.
     *
     * @param string $path        Path to the variable
     *                            (e.g. var://GLOBALS/myArray/anyIndex)
     * @param string $mode        Mode to open the stream,
     *                            like 'r', 'w,',... ({@see fopen()})
     * @param array  $options     Options (not implemented yet)
     * @param string $opened_path This will be set to the actual opened path
     *
     * @return boolean $success
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $mode = strtolower($mode);
        switch ($mode[0]) {
        case    "r":
            $status = $this->setPointerFromPath($path, false);
            $this->_mode = $this->_mode | STREAM_VAR_READABLE;
            break;
        case    "w":
        case    "a":
            $status = $this->setPointerFromPath($path, true);
            $this->_mode = $this->_mode | STREAM_VAR_WRITEABLE;
            break;
        case    "x":
            $status = $this->setPointerFromPath($path, false);
            if ($status) {
                return false;
            }
            $status = $this->setPointerFromPath($path, true);
            $this->_mode = $this->_mode | STREAM_VAR_WRITEABLE;
            break;
        }

        if (!$status) {
            return false;
        }

        if (!is_scalar($this->_pointer)) {
            return false;
        }

        // start at zero
        $this->_pos = 0;
        $this->_open = true;
        $opened_path = $path;

        if ($mode[0] == 'a') {
            $this->stream_seek(0, SEEK_END);
        }

        if (strlen($mode) > 1 && $mode[1] == '+') {
            $this->_mode = $this->_mode | STREAM_VAR_READABLE | STREAM_VAR_WRITEABLE;
        }

        return true;
    }

    /**
     * Check for end of stream.
     *
     * @return boolean True if at end of stream
     */
    public function stream_eof()
    {
        return ($this->_pos >= strlen($this->_pointer));
    }

    /**
     * Return the current position.
     *
     * @return integer Current position in stream
     */
    public function stream_tell()
    {
        return $this->_pos;
    }

    /**
     * Close the stream.
     *
     * @return void
     */
    public function stream_close()
    {
        $this->_pos  = 0;
        $this->_open = false;
    }

    /**
     * Read from the stream.
     *
     * @param integer $count Amount of bytes to read
     *
     * @return string $data Data that has been read
     */
    public function stream_read($count)
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
     * Write to the stream.
     *
     * @param mixed $data Data to write
     *
     * @return integer Number of bytes that were written
     */
    public function stream_write($data)
    {
        if (!$this->_open) {
            return false;
        }

        if (!($this->_mode & STREAM_VAR_WRITEABLE)) {
            return false;
        }

        $datalen = strlen($data);

        $this->_pointer = substr($this->_pointer, 0, $this->_pos)
            . $data
            . substr($this->_pointer, $this->_pos+$datalen);

        $this->_pos = $this->_pos + $datalen;
        return $datalen;
    }

    /**
     * Move the position in the stream.
     *
     * @param integer $offset Offset
     * @param integer $whence Point from which the offset
     *                        should be calculated
     *
     * @return boolean True if the position could be reached
     */
    public function stream_seek($offset, $whence)
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
     * Write all data to storage.
     *
     * @return boolean Always true
     */
    public function stream_flush()
    {
        return true;
    }

    /**
     * Return information about the stream.
     *
     * @return array $stat Information about the stream
     *                     (currently only the length is included)
     */
    public function stream_stat()
    {
        $stat = array(
            'size' => strlen($this->_pointer)
        );
        return $stat;
    }

    /**
     * This method is called in response to stat() calls on the URL paths
     *
     * As taken from the PHP Manual:
     *
     * "This method is called in response to stat()  calls on the URL paths
     * associated with the wrapper and should return as many elements in
     * common with the system function as possible. Unknown or unavailable
     * values should be set to a rational value (usually 0)."
     *
     * With regards to the implementation that is Stream_Var we can actually fake
     * some of the data. For instance, the uid and gid can be that of the corrent
     * posix_getuid and posix_getgid()
     *
     * The following outlines the information that we essentially fake:
     *
     * - 'dev': is unknown and set to 0
     * - 'ino': is unknown and set to 0
     * - 'mode': set to 33216 (chmod 700 means user has read,
     *    write and execute on the file)
     * - 'nlink': is unknown and set to 0
     * - 'uid': if the method posix_getuid exist, this is called,
     *    otherwise 0 is returned
     * - 'gid': if the method posix_getgid exist, this is called,
     *    otherwise 0 is returned
     * - 'rdev' unknown and set to 0
     * - 'size': is set to the strlen of the pointer.
     * - 'atime': set to current value returned by time()
     * - 'mtime': set to current value returned by time()
     * - 'ctime': set to current value returned by time()
     * - 'blksize': is unknown and set to 0
     * - 'blocks': is unknown and set to 0
     *
     * @param string  $path  The path to stat.
     * @param integer $flags Holds additional flags set by the streams API.
     *                       It can hold one or more of the following values
     *                       OR'd together.
     *                       - STREAM_URL_STAT_LINK - currently this is
     *                         ignored.
     *                       - STREAM_URL_STAT_QUIET - makes call to
     *                         strlen quiet
     *
     * @return array
     *
     * @see    http://au.php.net/stream_wrapper_register
     * @author Alex Hayes <ahayes@wcg.net.au>
     */
    public function url_stat($path, $flags)
    {
        if (!$this->setPointerFromPath($path, false)) {
            //does not exist
            return false;
        }

        $time = time();
        $keys = array(
            'dev'     => 0,
            'ino'     => 0,
            // chmod 700 means user has read, write and execute on the file
            'mode'    => 33216,
            'nlink'   => 0,
            //this processes uid
            'uid'     => function_exists('posix_getuid') ? posix_getuid() : 0,
            //this processes gid
            'gid'     => function_exists('posix_getgid') ? posix_getgid() : 0,
            'rdev'    => 0,
            'size'    => $flags & STREAM_URL_STAT_QUIET
                ? @strlen($this->_pointer) : strlen($this->_pointer),
            'atime'   => $time,
            'mtime'   => $time,
            'ctime'   => $time,
            'blksize' => 0,
            'blocks'  => 0
        );

        return array_merge(array_values($keys), $keys);
    }

    /**
     * Open 'directory'
     *
     * @param string $path    Path to the array (i.e. the directory)
     * @param array  $options Not implemented, yet.
     *
     * @return boolean $success
     */
    public function dir_opendir($path, $options)
    {
        if (!$this->setPointerFromPath($path, false)) {
            return false;
        }

        if (!is_array($this->_pointer)) {
            return false;
        }
        reset($this->_pointer);
        $this->_open = true;
        return true;
    }


    /**
     * Close 'directory'
     *
     * @return boolean $success
     */
    public function dir_closedir()
    {
        $this->_open = false;
        return true;
    }

    /**
     * Rewind 'directory'
     *
     * @return boolean $success
     */
    public function dir_rewinddir()
    {
        if (!$this->_open) {
            return false;
        }
        reset($this->_pointer);
        return true;
    }

    /**
     * Read one entry from 'directory'
     *
     * @return mixed $entry Entry that has been read, or
     *                      false if there are no entries left
     */
    public function dir_readdir()
    {
        if (!$this->_open) {
            return false;
        }
        if (key($this->_pointer) == count($this->_pointer) - 1) {
            return false;
        }
        $key = key($this->_pointer);
        next($this->_pointer);

        return $key;
    }

    /**
     * Set the internal pointer
     *
     * Basically this method only sets the object property _pointer
     * as a reference to a variable
     *
     * @param string  $scope  Scope of the variable: GLOBAL, GET,
     *                        POST, COOKIE, SESSION, SERVER, ENV
     * @param string  $path   Path to the variable. Array indices
     *                        are seperated by a slash
     * @param boolean $create Create the variable, if it does not exist
     *
     * @return boolean true if the pointer was set, false if not found
    */
    protected function _setPointer($scope, $path, $create = false)
    {
        $varpath = explode('/', $path);

        switch (strtoupper($scope)) {
        // GET variables
        case 'GET':
        case '_GET':
            $this->_pointer = &$_GET;
            break;

        // POST variables
        case 'POST':
        case '_POST':
            $this->_pointer = &$_POST;
            break;

        // SERVER variables
        case 'SERVER':
        case '_SERVER':
            $this->_pointer = &$_SERVER;
            break;

        // SESSION variables
        case 'SESSION':
        case '_SESSION':
            $this->_pointer = &$_SESSION;
            break;

        // COOKIE variables
        case 'COOKIE':
        case '_COOKIE':
            $this->_pointer = &$_COOKIE;
            break;

        // ENV variables
        case 'ENV':
        case '_ENV':
            $this->_pointer = &$_ENV;
            break;

        // global variables
        case 'GLOBALS':
        default:
            if (empty($varpath)) {
                throw new \Exception('GLOBALS needs a path');
            }
            $part = array_shift($varpath);
            if (!isset($GLOBALS[$part])) {
                return false;
            }
            $this->_pointer = &$GLOBALS[$part];
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

    /**
     * Set the internal pointer variable from the given variable path.
     *
     * @param string  $path   Variable path like GLOBALS/foo/bar
     * @param boolean $create If the variable should be created if it does not
     *                        exists
     *
     * @return boolean True if the pointer could be set (variable found)
     */
    protected function setPointerFromPath($path, $create = false)
    {
        $url    = parse_url($path);
        $scope = $url['host'];
        if (isset($url['path'])) {
            $varpath = substr($url['path'], 1);
        } else {
            $varpath = '';
        }

        return $this->_setPointer($scope, $varpath, $create);
    }
}
?>
