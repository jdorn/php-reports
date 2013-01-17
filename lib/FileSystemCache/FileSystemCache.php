<?php
/**
 * FileSystemCache providing utilities for caching data in a directory structure on the file system.
 *
 * @package    FileSystemCache
 * @author     Jeremy Dorn <jeremy@jeremydorn.com>
 * @copyright  2012 Jeremy Dorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://github.com/jdorn/FileSystemCache
 * @version    1.0.0
 */
class FileSystemCache {
	/**
	 * The root cache directory.  Everything will be cached relative to this directory.
	 * @var string
	 */
	public static $cacheDir = 'cache';
	
	/**
	 * Generates a cache key to use with store, retrieve, getAndModify, and invalidate
	 * @param mixed $key_data Unique data that identifies the key.  Can be a string, array, number, or object.
	 * @param String $group An optional group to put the cache key in.  Must be in the format "groupname" or "groupname/subgroupname".
	 * @return FileSystemCacheKey The cache key object.
	 */
	public static function generateCacheKey($key_data, $group=null) {
		return new FileSystemCacheKey($key_data,$group);
	}

	/**
	 * Stores data in the cache
	 * @param FileSystemCacheKey $key The cache key
	 * @param mixed $data The data to store (will be serialized before storing)
	 * @param int $ttl The number of seconds until the cache expires.  (optional)
	 * @return boolean True on success, false on failure
	 */
	public static function store(FileSystemCacheKey $key, $data, $ttl=null) {	
		$filename = $key->getFileName();
		
		$data = new FileSystemCacheValue($key,$data,$ttl);
		
		$fh = self::getFileHandle($filename,'c');

		if(!$fh) return false;
		
		if(!self::putContents($fh,$data)) return false;

		return true;
	}
	
	/**
	 * Retrieve data from cache
	 * @param FileSystemCacheKey $key The cache key
	 * @param int $newer_than If passed, only return if the cached value was created after this time
	 * @return mixed The cached data or FALSE if not found or expired
	 */
	public static function retrieve(FileSystemCacheKey $key, $newer_than=null) {
		$filename = $key->getFileName();
		
		if(!file_exists($filename)) return false;
		
		//if cached data is not newer than $newer_than
		if($newer_than && filemtime($filename) < $newer_than) return false;

		$fh = self::getFileHandle($filename,'r');
		if(!$fh) return false;
		
		$data = self::getContents($fh,$key);
		if(!$data) return false;


		self::closeFile($fh);		
		return $data->value;
	}
	
	/**
	 * Atomically retrieve data from cache, modify it, and store it back
	 * @param FileSystemCacheKey $key The cache key
	 * @param Closure $callback A closure function to modify the cache value.  
	 * Takes the old value as an argument and returns new value.
	 * If this function returns false, the cached value will be invalidated.
	 * @param bool $resetTtl If set to true, the expiration date will be recalculated using the previous TTL
	 * @return mixed The new value if it was stored successfully or false if it wasn't
	 * @throws Exception If an invalid callback method is given
	 */
	public static function getAndModify(FileSystemCacheKey $key, Closure $callback, $resetTtl=false) {
		$filename = $key->getFileName();
		
		if(!file_exists($filename)) return false;

		//open a file handle
		$fh = self::getFileHandle($filename,'c+');
		if(!$fh) return false;
		
		//get the data
		$data = self::getContents($fh,$key);
		if(!$data) return false;
		
		//get new value from callback function
		$old_value = $data->value;
		$data->value = $callback($data->value);
		
		//if the callback function returns false
		if($data->value === false) {
			self::closeFile($fh);

			//delete the cache file
			self::invalidate($key);
			return false;
		}
		
		//if value didn't change
		if(!$resetTtl && $data->value === $old_value) {
			self::closeFile($fh);			
			return $data->value;
		}
		
		//if we're resetting the ttl to now
		if($resetTtl) {
			$data->created = time();
			if($data->ttl) {
				$data->expires = $data->created + $data->ttl;
			}
		}

		if(!self::emptyFile($fh)) return false;

		//write contents and close the file handle
		self::putContents($fh,$data);
		
		//return the new value after modifying
		return $data->value;
	}
	
	/**
	 * Invalidate a specific cache key
	 * @param FileSystemCacheKey $key The cache key
	 * @return boolean True on success.  Currently never returns false.
	 */
	public static function invalidate(FileSystemCacheKey $key) {
		$filename = $key->getFileName();
		if(file_exists($filename)) {
			unlink($filename);
		}
		return true;
	}

	/**
	 * Invalidate a group of cache keys
	 * @param string $name The name of the group to invalidate (e.g. 'groupname', 'groupname/subgroupname', etc.).  If null, the entire cache will be invalidated.
	 * @param boolean $recursive If set to false, none of the subgroups will be invalidated.
	 * @throws Exception If an invalid group name is given
	 */
	public static function invalidateGroup($name=null, $recursive=true) {
		//if invalidating a group, make sure it's valid
		if($name) {
			//it needs to have a trailing slash and no leading slashes
			$name = trim($name,'/').'/';

			//make sure the key isn't going up a directory
			if(strpos($name,'..') !== false) {
				throw new Exception("Invalidate path cannot go up directories.");
			}
		}

		array_map("unlink", glob(self::$cacheDir.'/'.$name.'*.cache'));
		
		//if recursively invalidating
		if($recursive) {
			$subdirs = glob(self::$cacheDir.'/'.$name.'*',GLOB_ONLYDIR);
			
			foreach($subdirs as $dir) {
				$dir = basename($dir);
				
				//skip all subdirectories that start with '.'
				if($dir[0] == '.') continue;
				
				self::invalidateGroup($name.$dir,true);
			}
		}
	}


	/**
	 * Get a file handle from a file name. Will create the directory if it doesn't exist already. Also, automatically locks the file with the proper read or write lock.
	 * @param String $filename The full file path.
	 * @param String $mode The file mode.  Accepted modes are 'c', 'c+', and 'r'.
	 * @return resource The file handle
	 */
	private static function getFileHandle($filename, $mode='c') {
		$write = in_array($mode,array('c','c+'));

		if($write) {
			//make sure the directory exists and is writable
			$directory = dirname($filename);
			if(!file_exists($directory)) {
				if(!mkdir($directory,777,true)) {
					return false;
				}
			}
			elseif(!is_dir($directory)) {
				return false;
			}
			elseif(!is_writable($directory)) {
				return false;
			}
		}
		
		//get file pointer
		$fh = fopen($filename,$mode);

		if(!$fh) return false;

		//lock file with appropriate lock type
		if($write) {
			if(!flock($fh,LOCK_EX)) {
				self::closeFile($fh);
				return false;
			}	
		}
		else {
			if(!flock($fh,LOCK_SH)) {
				self::closeFile($fh);
				return false;
			}
		}
		
		return $fh;
	}

	/**
	 * Empties a file.  If empty fails, the file will be closed and it will return false.
	 * @param resource $fh The file handle
	 * @return boolean true for success, false for failure
	 */
	private static function emptyFile($fh) {
		rewind($fh);
		if(!ftruncate($fh,0)) {
			//release lock
			self::closeFile($fh);
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Closes a file.  Also releases any locks on the file.
	 * @param resource $fh The file handle
	 */
	private static function closeFile($fh) {
		flock($fh,LOCK_UN);
		fclose($fh);
	}

	/**
	 * Returns the contents of a cache file.  If the data is not in the right form or expired, it will be invalidated.
	 * @param resource $fh The file handle
	 * @param FileSystemCacheKey $key The cache key.  This is used to invalidate the key when the data is expired.
	 * @return boolean|FileSystemCacheValue FALSE if something went wrong or the data is expired.  Otherwise, a FileSystemCacheValue object will be returned.
	 */
	private static function getContents($fh,FileSystemCacheKey $key) {
		//get the existing file contents
		$contents = stream_get_contents($fh);
		$data = @unserialize($contents);
		
		//if we can't unserialize the data or if the data is expired
		if(!$data || !($data instanceof FileSystemCacheValue) || $data->isExpired()) {
			//release lock
			self::closeFile($fh);
			
			//delete the cache file so we don't try to retrieve it again
			self::invalidate($key);
			
			return false;
		}
		
		return $data;
	}

	/**
	 * Writes to a file.  Also closes and releases any locks on the file.
	 * @param resource $fh The file handle
	 * @param FileSystemCacheValue $data The cache value to store in the file.
	 * @return boolean True on success.  Currently, never returns false.
	 */
	private static function putContents($fh,FileSystemCacheValue $data) {
		fwrite($fh,serialize($data));
		fflush($fh);

		//release lock
		self::closeFile($fh);

		return true;
	}
}

/**
 * Class that represents a cache key.
 * Most of the time, you would get a FileSystemCacheKey object from FileSystemCache::generateCacheKey();
 */
class FileSystemCacheKey {
	/**
	 * @var mixed The key data used to generate the cache key
	 */
	public $key;
	/**
	 * @var string The group (if any) that the key will be stored in.  Can be null.
	 */
	public $group;

	/**
	 * Creates a FileSystemCacheKey object
	 * @param mixed $key Key data that will be used to generate a cache key
	 * @param string $group The group (if any) that the key will be stored in.  Can be null.
	 */
	public function __construct($key,$group) {
		$this->key = $key;
		$this->group = $group;
	}

	/**
	 * Returns the generated cache key.
	 * Non-string key data will be serialized and hashed
	 * @return string The generated cache key.
	 */
	public function __toString() {
		$key = $this->key;

		//convert arrays and objects into strings
		if(!is_string($key)) {
			$key = serialize($key);
		}
		
		//if we can't use the key directly, md5 it
		if(preg_match('/[^a-zA-Z0-9_\-\.]/',$key)) {
			$key = md5($key);
		}

		//if it contains a group
		if($this->group) {
			//sanitize the group part
			$parts = explode('/',$this->group);
			foreach($parts as $i=>&$part) {
				$part = preg_replace('/[^a-zA-Z0-9_\-]/','',$part);

				if(!$part) unset($parts[$i]);
			}

			$group = implode('/',$parts);

			$key = $group.'/'.$key;
		}

		return $key;
	}

	/**
	 * Returns the full path to the cache file for this key.
	 * @return string The full path to the cache file for this key.
	 */
	public function getFileName() {
		return FileSystemCache::$cacheDir . '/' . $this->__toString() . '.cache';
	}
}

/**
 * This class represents the actual data stored in the cache file.
 * You should never need to use this class directly.
 */
class FileSystemCacheValue {
	/**
	 * @var FileSystemCacheKey The cache key the file is stored under.
	 */
	public $key;
	/**
	 * @var mixed The value being cached
	 */
	public $value;
	/**
	 * @var int The max number of seconds to store the data.  If null, the data won't expire.
	 */
	public $ttl;
	/**
	 * @var int The timestamp of when the data will expire.  If null, the data won't expire.
	 */
	public $expires;
	/**
	 * @var int The timestamp of when the value was created.
	 */
	public $created;

	/**
	 * Creates a FileSystemCacheValue object.
	 * @param FileSystemCacheKey $key The cache key the file is stored under.
	 * @param mixed $value The data being stored
	 * @param int $ttl The timestamp of when the data will expire.  If null, the data won't expire.
	 */
	public function __construct($key,$value,$ttl = null) {
		$this->key = $key;
		$this->value = $value;
		$this->ttl = $ttl;
		$this->created = time();
		
		if($ttl) $this->expires = $this->created + $ttl;
		else $this->expires = null;
	}

	/**
	 * Checks if a value is expired
	 * @return bool True if the value is expired.  False if it is not.
	 */
	public function isExpired() {
		//value doesn't expire
		if(!$this->expires) return false;
		
		//if it is after the expire time
		return time() > $this->expires;
	}
}
