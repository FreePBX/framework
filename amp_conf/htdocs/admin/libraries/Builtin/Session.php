<?php

// Transparent session handler.
// Licenced under the AGPLv3 or higher
// Copyright (c) 2017 Rob Thomas <xrobau@linux.com>

// Taken from https://github.com/xrobau/sessions and modified for
// use with FreePBX.

// Usage:
//   \FreePBX\Builtin::register(DBHOST, DBUSER, DBPASS, DBNAME)
//
// Nothing else is required. After that, you can simply proceed as per normal
//
//   session_create();
//   $_SESSION['test'] = 'foo';
//   session_close();
//
// No code changes are needed apart from registering the handler

namespace FreePBX\Builtin;

class Session {
	private static $session;
	private $cache;
	private $ttl = 1800;

	public static function register($dbh) {
		// Only register if we haven't already done so.
		if (empty(self::$session)) {
			self::$session = new Session();
		}
	}

	public function __construct() {
		session_set_save_handler(
			[ $this, "session_open" ],
			[ $this, "session_close" ],
			[ $this, "session_read" ],
			[ $this, "session_write" ],
			[ $this, "session_destroy" ],
			[ $this, "session_gc" ]
		);
	}

	// The open callback works like a constructor in classes and is executed
	// when the session is being opened. It is the first callback function
	// executed when the session is started automatically or manually with
	// session_start(). Return value is TRUE for success, FALSE for failure.
	public function session_open() {
		$this->cache = \FreePBX::Cache()->cloneByNamespace('session');
		$this->ttl = \FreePBX::Config()->get('SESSION_TIMEOUT');
		return true;
	}

	// The close callback works like a destructor in classes and is executed
	// after the session write callback has been called. It is also invoked
	// when session_write_close() is called. Return value should be TRUE for
	// success, FALSE for failure.
	public function session_close() {
		unset($this->cache);
		return true;
	}

	// The read callback must always return a session encoded (serialized)
	// string, or an empty string if there is no data to read.
	//
	// This callback is called internally by PHP when the session starts or
	// when session_start() is called. Before this callback is invoked PHP
	// will invoke the open callback.
	//
	// The value this callback returns must be in exactly the same serialized
	// format that was originally passed for storage to the write callback.
	// The value returned will be unserialized automatically by PHP and used
	// to populate the $_SESSION superglobal. While the data looks similar to
	// serialize() please note it is a different format which is speficied  <-- typo in the docs
	// in the session.serialize_handler ini setting.
	//
	// Bug report of typo:  https://bugs.php.net/bug.php?id=75198
	public function session_read($id) {
		if($this->cache->contains($id)) {
			return $this->cache->fetch($id);
		}
		return '';
	}

	// The write callback is called when the session needs to be saved and closed.
	// This callback receives the current session ID a serialized version the
	// $_SESSION superglobal. The serialization method used internally by PHP
	// is specified in the session.serialize_handler ini setting.
	//
	// The serialized session data passed to this callback should be stored against
	// the passed session ID. When retrieving this data, the read callback must
	// return the exact value that was originally passed to the write callback.
	//
	// This callback is invoked when PHP shuts down or explicitly when session_write_close()
	// is called. Note that after executing this function PHP will internally execute
	// the close callback.
	//
	// Note: The "write" handler is not executed until after the output stream is closed.
	// Thus, output from debugging statements in the "write" handler will never be seen
	// in the browser. If debugging output is necessary, it is suggested that the debug
	// output be written to a file instead.

	public function session_write($id, $data){
		return $this->cache->save($id,$data,$this->ttl);
	}

	// This callback is executed when a session is destroyed with session_destroy()
	// or with session_regenerate_id() with the destroy parameter set to TRUE.
	// Return value should be TRUE for success, FALSE for failure.
	public function session_destroy($id) {
		$this->cache->delete($id);
		return true;
	}

	// The garbage collector callback is invoked internally by PHP periodically in
	// order to purge old session data. The frequency is controlled by
	// session.gc_probability and session.gc_divisor. The value of lifetime which is
	// passed to this callback can be set in session.gc_maxlifetime. Return value
	// should be TRUE for success, FALSE for failure.

	public function session_gc($lifetime) {
		// no action necessary because using EXPIRE
		return true;
	}
}
