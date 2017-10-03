<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class Chown extends Command {
	//private $requireroot = true;  //commented out: http://issues.freepbx.org/browse/FREEPBX-13793
	private $errors = array();
	private $infos = array();
	private $blacklist = array('files' => array(), 'dirs' => array());
	public $moduleName = '';
	protected function configure(){
		$this->setName('chown')
		->setDescription(_('Change ownership of files'))
		->setDefinition(array(
			new InputOption('file', 'f', InputOption::VALUE_REQUIRED, _('Execute on only this file/dir')),
			new InputArgument('args', InputArgument::IS_ARRAY, _('Set permissions on a specific module: <rawname>'), null),));
		$this->fs = new Filesystem();
		$this->modfiles = array();
		$this->loadChownConf();
	}

	protected function execute(InputInterface $input, OutputInterface $output, $quiet=false){
		if($quiet) {
			$output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
		}
		$this->output = $output;
		$args = array();
		if($input){
			$args = $input->getArgument('args');
			$mname = isset($args[0])?$args[0]:'';
			$this->moduleName = !empty($this->moduleName) ? $this->moduleName : strtolower($mname);
		}

		if((empty($this->moduleName) || $this->moduleName == 'framework') && posix_geteuid() != 0) {
			$output->writeln("<error>"._("You need to be root to run this command")."</error>");
			exit(1);
		}

		$etcdir = \FreePBX::Config()->get('ASTETCDIR');
		if(!file_exists($etcdir.'/freepbx_chown.conf')) {
			$output->writeln("<info>".sprintf(_("Taking too long? Customize the chown command, See %s"),"http://wiki.freepbx.org/display/FOP/FreePBX+Chown+Conf")."</info>");
		}
		$output->writeln(_("Setting Permissions")."...");
		$freepbx_conf = \freepbx_conf::create();
		$conf = $freepbx_conf->get_conf_settings();
		foreach ($conf as $key => $val){
			${$key} = $val['value'];
		}
		/*
		 * These are files Framework is responsible for This list can be
		 * reduced by moving responsibility to other modules as a hook
		 * where appropriate.
		 *
		 * Types:
		 * 		file:		Set permissions/ownership on a single item
		 * 		dir: 		Set permissions/ownership on a single directory
		 * 		rdir: 		Set permissions/ownership on a single directory then recursively on
		 * 					files within less the execute bit. If the dir is 755, child files will be 644,
		 * 					child directories will be set the same as the parent.
		 * 		execdir:	Same as rdir but the execute bit is not stripped.
		 */
		$sessdir = session_save_path();
		$sessdir = !empty($sessdir) ? $sessdir : '/var/lib/php/session';

		if (!empty($this->moduleName) && $this->moduleName != 'framework') {
			$mod = $this->moduleName;
			$this->modfiles[$mod][] = array('type' => 'rdir',
					'path' => $AMPWEBROOT.'/admin/modules/'.$mod,
					'perms' => 0755,
				);
			$hooks = $this->fwcChownFiles();
			$current = isset($hooks[ucfirst($mod)]) ? $hooks[ucfirst($mod)] : false;
			if(is_array($current)){
				$this->modfiles[$mod] = array_merge_recursive($this->modfiles[$mod],$current);
			}
			// These are known 'binary' directories. If they exist, always set them and their contents to be executable.

			$bindirs = array("bin", "hooks", "agi-bin");
			foreach ($bindirs as $bindir) {
				if (is_dir($AMPWEBROOT."/admin/modules/".$mod."/".$bindir)) {
					$this->modfiles[$mod][] = array('type' => 'execdir',
									'path' => $AMPWEBROOT."/admin/modules/".$mod."/".$bindir,
									'perms' => 0755);
				}
			}
			if(posix_geteuid() != 0) {
				//only allow changes on our current path if we aren't root!
				$pth = $AMPWEBROOT.'/admin/modules/'.$mod;
				$esc = preg_quote($pth,"/");
				$tmp = $this->modfiles[$mod];
				foreach($tmp as $key => $item) {
					if(!preg_match("/^".$esc."/",$item['path'])) {
						unset($this->modfiles[$mod][$key]);
					}
				}
				$this->modfiles[$mod] = array_values($this->modfiles[$mod]);
			}
		}else{
			$webuser = \FreePBX::Freepbx_conf()->get('AMPASTERISKWEBUSER');
			$web = posix_getpwnam($webuser);
			if (!$web) {
				throw new \Exception(sprintf(_("I tried to find out about %s, but the system doesn't think that user exists"),$webuser));
			}
			$home = trim($web['dir']);
			if (is_dir($home)) {
				$this->modfiles['framework'][] = array('type' => 'rdir', 'path' => $home, 'perms' => 0755);
				// SSH folder needs non-world-readable permissions (otherwise ssh complains, and refuses to work)
				$this->modfiles['framework'][] = array('type' => 'rdir', 'path' => "$home/.ssh", 'perms' => 0700);
			}
			$this->modfiles['framework'][] = array('type' => 'rdir', 'path' => $sessdir, 'perms' => 0774, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'file', 'path' => '/etc/amportal.conf', 'perms' => 0660, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'file', 'path' => '/etc/freepbx.conf', 'perms' => 0660, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'rdir', 'path' => $ASTRUNDIR, 'perms' => 0775, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'rdir', 'path' => \FreePBX::GPG()->getGpgLocation(), 'perms' => 0775, 'always' => true);

			//we may wish to declare these manually or through some automated fashion
			$this->modfiles['framework'][] = array('type' => 'rdir', 'path' => $ASTETCDIR, 'perms' => 0775, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'file', 'path' => $ASTVARLIBDIR . '/.ssh/id_rsa', 'perms' => 0600);

			// Logfiles.
			$this->modfiles['framework'][] = array('type' => 'file', 'path' => $FPBXDBUGFILE, 'perms' => 0664);
			$this->modfiles['framework'][] = array('type' => 'file', 'path' => $FPBX_LOG_FILE, 'perms' => 0664);

			//We may wish to declare files individually rather than touching everything
			$this->modfiles['framework'][] = array('type' => 'file', 'path' => '/etc/obdc.ini', 'perms' => 0664);

			// Anything in bin, agi-bin, and roothooks should be exec'd
			// Should be after everything except but before hooks so that we dont get overwritten by ampwebroot
			$this->modfiles['framework'][] = array('type' => 'execdir', 'path' => $AMPBIN, 'perms' => 0775, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'execdir', 'path' => $ASTAGIDIR, 'perms' => 0775, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'execdir', 'path' => $ASTVARLIBDIR. "/bin", 'perms' => 0775, 'always' => true);
			$this->modfiles['framework'][] = array('type' => 'execdir', 'path' => $AMPWEBROOT."/admin/modules/framework/hooks", 'perms' => 0755, 'always' => true);

			//Merge static files and hook files, then act on them as a single unit
			$fwcCF = $this->fwcChownFiles();
			if(!empty($fwcCF)){
				foreach ($fwcCF as $key => $value) {
					$this->modfiles[$key] = $value;
				}
			}
			// These are known 'binary' directories. If they exist, always set them and their contents to be executable.
			$mods = array_keys(\FreePBX::Modules()->getActiveModules());
			$bindirs = array("bin", "hooks", "agi-bin");
			foreach($mods as $mod) {
				if(in_array($mod,array("framework","builtin"))) {
					continue;
				}
				foreach ($bindirs as $bindir) {
					if (is_dir($AMPWEBROOT."/admin/modules/".$mod."/".$bindir)) {
						$this->modfiles[$mod][] = array('type' => 'execdir',
										'path' => $AMPWEBROOT."/admin/modules/".$mod."/".$bindir,
										'perms' => 0755);
					}
				}
			}
		}
		//Let's move the custom array to the end so it happens last
		//FREEPBX-12515
		//Store in a temporary variable. If Null we make it an empty array
		$holdarray = $this->modfiles['byconfig'];
		//Unset it from the array
		unset($this->modfiles['byconfig']);
		//Add it back to the array
		$this->modfiles['byconfig'] = $holdarray;

		$ampowner = $AMPASTERISKWEBUSER;
		/* Address concerns carried over from amportal in FREEPBX-8268. If the apache user is different
		 * than the Asterisk user we provide permissions that allow both.
		 */
		$ampgroup =  $AMPASTERISKWEBUSER != $AMPASTERISKUSER ? $AMPASTERISKGROUP : $AMPASTERISKWEBGROUP;

		if((empty($this->moduleName) && $this->moduleName != 'framework') && posix_geteuid() == 0) {
			$output->write(_("Setting base permissions..."));

			$this->systemSetRecursivePermissions($ASTVARLIBDIR . '/' . $MOHDIR, 0775, $ampowner, $ampowner, 'rdir');
			$this->systemSetRecursivePermissions($ASTVARLIBDIR . '/sounds', 0775, $ampowner, $ampowner, 'rdir');
			$this->systemSetRecursivePermissions($ASTLOGDIR, 0775, $ampowner, $ampowner, 'rdir');
			$this->systemSetRecursivePermissions($ASTSPOOLDIR, 0775, $ampowner, $ampowner, 'rdir');
			$this->systemSetRecursivePermissions($AMPWEBROOT, 0775, $ampowner, $ampowner, 'rdir');
			//$this->systemSetRecursivePermissions('/usr/src/freepbx', 0775, $ampowner, $ampowner, 'rdir');

			$output->writeln(_("Done"));
		}

		$output->writeln(_("Setting specific permissions..."));

		$verbose = $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
		if(!$verbose) {
			$progress = new ProgressBar($output);
			$progress->setRedrawFrequency(100);
			$progress->start();
		}

		foreach($this->modfiles as $moduleName => $modfilelist) {
			if(!$verbose) {
				$progress->advance();
			}
			if(!is_array($modfilelist)) {
				continue;
			}
			foreach($modfilelist as $file) {
				$owner = isset($file['owner'])?$file['owner']:$ampowner;
				$group = isset($file['group'])?$file['group']:$ampgroup;
				$owner = \ForceUTF8\Encoding::toLatin1($owner);
				$group = \ForceUTF8\Encoding::toLatin1($group);
				switch($file['type']){
					case 'file':
					case 'dir':
						try {
							$this->checkPermissions($file['path'], $file['perms']);
							$this->chmod($progress, $file['path'], $file['perms'], 0000, false, false);
							$this->chown($progress, $file['path'], $owner);
							$this->chgrp($progress, $file['path'], $group);
						} catch(\Exception $e) {
						}
					break;
					case 'rdir':
						try {
							$this->checkPermissions($file['path'], $file['perms']);
							$this->chmod($progress, $file['path'], $file['perms'], 0000, true);
							$this->chown($progress, $file['path'], $owner, true);
							$this->chgrp($progress, $file['path'], $group, true);
						} catch(\Exception $e) {
						}
					break;
					case 'execdir':
						try {
							$this->checkPermissions($file['path'], $file['perms']);
							$this->chmod($progress, $file['path'], $file['perms'], 0000, true, false);
							$this->chown($progress, $file['path'], $owner, true);
							$this->chgrp($progress, $file['path'], $group, true);
						} catch(\Exception $e) {
						}
					break;
				}
			}
		}
		if(!$verbose) {
			$progress->finish();
		}
		$output->writeln("");
		$output->writeln("Finished setting permissions");
		$errors = array_unique($this->errors);
		foreach($errors as $error) {
			$output->writeln("<error>".$error."</error>");
		}
		$infos = array_unique($this->infos);
		foreach($infos as $error) {
			$output->writeln("<info>".$error."</info>");
		}
	}

	/**
	 * Set permissions through the system instead of PHP
	 * This is 600% faster but not as fine grained
	 * @method systemSetRecursivePermissions
	 * @param  string               $path  The path to chown
	 * @param  int                  $mode  The new mode (octal)
	 * @param  string               $owner The new owner
	 * @param  string               $group The new group
	 * @param  string               $rmode Recursive mode (rdir or execdir)
	 * @param  int                  $umask The mode mask (octal)
	 */
	private function systemSetRecursivePermissions($path, $mode, $owner='asterisk', $group='asterisk', $rmode = 'rdir', $umask = 0000) {
		$blacklist = $this->blacklist;
		if(!empty($blacklist['files'])) {
			array_walk($blacklist['files'], function(&$value, $key) {
				$value = escapeshellarg($value);
			});
			$skip .= "-not -path ".implode(" -not -path ",$blacklist['files']);
		}
		if(!empty($blacklist['dirs'])) {
			$tmp = $blacklist['dirs'];
			foreach($tmp as $t) {
				$blacklist['dirs'][] = rtrim($t, '/') . '/*';
			}
			array_walk($blacklist['dirs'], function(&$value, $key) {
				$value = escapeshellarg($value);
			});
			$skip .= " -not -path ".implode(" -not -path ",$blacklist['dirs']);
		}
		if(!empty($skip)) {
			$skip = "\( ".$skip." \)";
		}

		$directoryMode = $fileMode = $mode;
		switch($rmode) {
			case 'rdir':
				$fileMode = $this->stripExecute($fileMode);
			case 'execdir':
				exec("find ".escapeshellarg($path)." -type d ".$skip." -exec chmod ".escapeshellarg(decoct($directoryMode & ~$umask))." {} +");
				exec("find ".escapeshellarg($path)." -type f ".$skip." -exec chmod ".escapeshellarg(decoct($fileMode & ~$umask))." {} +");
			break;
			default:
				throw new \Exception("Unknown mode of ".$mode);
			break;
		}

		exec("find ".escapeshellarg($path)." \( -type f -o -type d \) ".$skip." -exec chown ".escapeshellarg($owner).":".escapeshellarg($group)." {} +");
	}

	/**
	 * Check blacklist to see if file/dir is blacklisted
	 * @param  string $file The file/dir
	 * @return boolean       True if blacklisted/false if not
	 */
	private function checkBlacklist($file){
		//If path is in the blacklist we move on.
		if(in_array($file, $this->blacklist['files']) || in_array($file, $this->blacklist['dirs'])){
			return true;
		}
		//recursively ignore files
		foreach($this->blacklist['dirs'] as $dir) {
			if(preg_match("/^".preg_quote($dir,'/')."/",$file)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strip execute bit off of chown
	 * @param  bit $mask The bitmask
	 * @return bit       Bitmask
	 */
	private function stripExecute($mask) {
		$mask = ( $mask & ~0111 );
		return $mask;
	}

	private function checkPermissions($file, $mode) {
		if(decoct(octdec($mode)) == $mode){
			return true;
		}else{
			$this->d[] = "<error>".sprintf(_('%s Likely will not work as expected'),$file)."</error>";
			$this->d[] = "<error>".sprintf(_('Permissions should be set with a leading 0, example 644 should be 0644 File: %s Permission set as: %s'),$file,$mode)."</error>";
			return false;
		}
	}

	private function d($message) {
		$debug = $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
		if($debug && is_object($this->output)) {
			$this->output->writeln($message);
		}
	}

	private function fwcChownFiles(){
		$modules = \FreePBX::Hooks()->processHooks();
		return $modules;
	}

	/**
	 * Change the group of an array of files or directories.
	 *
	 * @param string|array|\Traversable $files     A filename, an array of files, or a \Traversable instance to change group
	 * @param string                    $group     The group name
	 * @param bool                      $recursive Whether change the group recursively or not
	 *
	 * @throws IOException When the change fail
	 */
	public function chgrp($progress=null, $files, $group, $recursive = false) {
		foreach ($this->toIterator($files) as $file) {
			if(!is_null($progress)) {
				$progress->advance();
			}
			if($this->checkBlacklist($file)){
				$this->d(sprintf(_('%s skipped by configuration'), $file));
				continue;
			}
			if ($recursive && is_dir($file) && !is_link($file)) {
				$this->chgrp($progress, new \FilesystemIterator($file), $group, true);
			}
			if (is_link($file) && function_exists('lchgrp')) {
				$this->d("Setting ".$file." group owner: ".$group);
				if (true !== @lchgrp($file, $group) || (defined('HHVM_VERSION') && !posix_getgrnam($group))) {
					throw new IOException(sprintf('Failed to chgrp file "%s".', $file), 0, null, $file);
				}
			} else {
				if (true !== @chgrp($file, $group)) {
					$this->d("Setting ".$file." group owner: ".$group);
					throw new IOException(sprintf('Failed to chgrp file "%s".', $file), 0, null, $file);
				}
			}
		}
	}

	/**
	 * Change the owner of an array of files or directories.
	 *
	 * @param string|array|\Traversable $files     A filename, an array of files, or a \Traversable instance to change owner
	 * @param string                    $user      The new owner user name
	 * @param bool                      $recursive Whether change the owner recursively or not
	 *
	 * @throws IOException When the change fail
	 */
	public function chown($progress, $files, $user, $recursive = false) {
		foreach ($this->toIterator($files) as $file) {
			if(!is_null($progress)) {
				$progress->advance();
			}
			if($this->checkBlacklist($file)){
				$this->d(sprintf(_('%s skipped by configuration'), $file));
				continue;
			}
			if ($recursive && is_dir($file) && !is_link($file)) {
				$this->chown($progress, new \FilesystemIterator($file), $user, true);
			}
			if (is_link($file) && function_exists('lchown')) {
				$this->d("Setting ".$file." user owner: ".$user);
				if (true !== @lchown($file, $user)) {
					throw new IOException(sprintf('Failed to chown file "%s".', $file), 0, null, $file);
				}
			} else {
				$this->d("Setting ".$file." user owner to: ".$user);
				if (true !== @chown($file, $user)) {
					throw new IOException(sprintf('Failed to chown file "%s".', $file), 0, null, $file);
				}
			}
		}
	}

	/**
	 * Change mode for an array of files or directories.
	 *
	 * @param string|array|\Traversable $files     A filename, an array of files, or a \Traversable instance to change mode
	 * @param int                       $mode      The new mode (octal)
	 * @param int                       $umask     The mode mask (octal)
	 * @param bool                      $recursive Whether change the mod recursively or not
	 * @param bool                      $stripx    Whether to strip the executable bit from files (but not directories)
	 *
	 * @throws IOException When the change fail
	 */
	public function chmod($progress, $files, $mode, $umask = 0000, $recursive = false, $stripx = true) {
		foreach ($this->toIterator($files) as $file) {
			if(!is_null($progress)) {
				$progress->advance();
			}
			if($this->checkBlacklist($file)){
				$this->d(sprintf(_('%s skipped by configuration'), $file));
				continue;
			}
			$omode = $mode;
			if($stripx && !is_dir($file)) {
				$mode = $this->stripExecute($mode);
			}
			$this->d("Setting ".$file." to permissions of: ".decoct($mode & ~$umask));
			if (true !== @chmod($file, $mode & ~$umask)) {
				if(!is_link($file)) {
					throw new IOException(sprintf('Failed to chmod file "%s".', $file), 0, null, $file);
				} else {
					@unlink($file);
				}
			}
			$mode = $omode;
			if ($recursive && is_dir($file) && !is_link($file)) {
				$this->chmod($progress, new \FilesystemIterator($file), $mode, $umask, true, $stripx);
			}
		}
	}

	/**
	 * Convert an array into an iterator if not already one
	 * @method toIterator
	 * @param  mixed     $files Anything to turn into an array
	 * @return object            Iterator object
	 */
	private function toIterator($files) {
		if (!$files instanceof \Traversable) {
			$files = new \ArrayObject(is_array($files) ? $files : array($files));
		}
		return $files;
	}

	private function loadChownConf(){
		$etcdir = \FreePBX::Config()->get('ASTETCDIR');
		if(!file_exists($etcdir.'/freepbx_chown.conf')){
			return;
		}
		$conf  = \FreePBX::LoadConfig()->getConfig("freepbx_chown.conf");
		if(isset($conf['blacklist'])){
			if(isset($conf['blacklist']['item'])){
				$conf['blacklist']['item'] = is_array($conf['blacklist']['item'])?$conf['blacklist']['item']:array($conf['blacklist']['item']);
				foreach ($conf['blacklist']['item'] as $item) {
					$this->blacklist['files'][] = $item;
				}
			}
			if(isset($conf['blacklist']['directory'])){
				$conf['blacklist']['directory'] = is_array($conf['blacklist']['directory'])?$conf['blacklist']['directory']:array($conf['blacklist']['directory']);
				foreach ($conf['blacklist']['directory'] as $dir) {
					$dir = rtrim($dir, '/');
					$this->blacklist['dirs'][] = $dir;
				}
			}
		}
		$this->modfiles['byconfig'] = array();
		if(isset($conf['custom'])){
			if(isset($conf['custom']['file'])){
				$conf['custom']['file'] = is_array($conf['custom']['file'])?$conf['custom']['file']:array($conf['custom']['file']);
				foreach ($conf['custom']['file'] as $file) {
					$file = $this->parse_conf_line($file);
					if($file === false){continue;}
					$this->modfiles['byconfig'][] = array('type' => 'file',
							'path' => $file['path'],
							'perms' => $file['perms'],
							'owner' => $file['owner'],
							'group' => $file['group']
						);
				}
			}

			if(isset($conf['custom']['dir'])){
				$conf['custom']['dir'] = is_array($conf['custom']['dir'])?$conf['custom']['dir']:array($conf['custom']['dir']);
				foreach ($conf['custom']['dir']  as $dir) {
					$dir = $this->parse_conf_line($dir);
					if($dir === false){continue;}
					$this->modfiles['byconfig'][] = array('type' => 'dir',
							'path' => $dir['path'],
							'perms' => $dir['perms'],
							'owner' => $dir['owner'],
							'group' => $dir['group'],
							'always' => true
						);
				}
			}
			if(isset($conf['custom']['rdir'])){
				$conf['custom']['rdir'] = is_array($conf['custom']['rdir'])?$conf['custom']['rdir']:array($conf['custom']['rdir']);
				foreach ($conf['custom']['rdir']  as $rdir) {
					$rdir = $this->parse_conf_line($rdir);
					if($rdir === false){continue;}
					$this->modfiles['byconfig'][] = array('type' => 'rdir',
							'path' => $rdir['path'],
							'perms' => $rdir['perms'],
							'owner' => $rdir['owner'],
							'group' => $rdir['group'],
							'always' => true
						);
				}
			}
			if(isset($conf['custom']['execdir'])){
				$conf['custom']['execdir'] = is_array($conf['custom']['execdir'])?$conf['custom']['execdir']:array($conf['custom']['execdir']);
				foreach ($conf['custom']['execdir']  as $edir) {
					$edir = $this->parse_conf_line($edir);
					if($edir === false){continue;}
					$this->modfiles['byconfig'][] = array('type' => 'execdir',
							'path' => $edir['path'],
							'perms' => $edir['perms'],
							'owner' => $edir['owner'],
							'group' => $edir['group'],
							'always' => true
						);
				}
			}
		}
	}

	private function parse_conf_line($line){
		if(!is_string($line)) {
			throw new \Exception("freepbx_chown.conf has malformed data. Please fix the file");
		}
		$line = explode(",", $line);
		if(count($line) !== 4){
			return false;
		}
		$ret = array('path' => $line[0], 'perms' => intval($line[1], 8), 'owner' => $line[2], 'group' => $line[3]);
		return $ret;
	}
}
