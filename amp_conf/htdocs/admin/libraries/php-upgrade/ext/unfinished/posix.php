<?php
/*
   POSIX subsystem functions are emulated using regular commandline
   utilities or by invoking Perl.  Of course not the full spectrum
   of features can be simulated this way. You shouldn't rely on the
   emulation here, and just abort on PHP version without POSIX ext.
*/

if (!function_exists("posix_mkfifo") && strlen($_ENV["SHELL"]) && !ini_get("safe_mode")) {

   function posix_mkfifo($pathname, $mode=0400) {
      $cmd = "/usr/bin/mkfifo -m 0" . decoct($mode) . " " . escape_shell_arg($pathname);
      exec($cmd, $uu, $error);
      return(!$error);
   }
   
   function posix_getcwd() {
      return realpath(getcwd());
   }
   
   function posix_kill($pid, $sig) {
      $cmd = "kill -" . ((int)$sig) . " " . ((int)$pid);
      exec($cmd, $uu, $error);
      return(!$error);
   }

   function posix_uname() {
      return array(
         "sysname" => `/bin/uname -s`,
         "nodename" => `/bin/uname -n`,
         "release" => `/bin/uname -r`,
         "version" => `/bin/uname -v`,
         "machine" => `/bin/uname -m`,
         "domainname" => `/bin/domainname`,
      );
   }
}


?>