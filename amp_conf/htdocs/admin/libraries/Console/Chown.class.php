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
		$this->actions = new \SplQueue();
		$this->actions->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE);
		$this->loadChownConf();
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
				$this->modfiles['framework'][] = array('type' => 'rdir',
															'path' => $home,
															'perms' => 0755);
				// SSH folder needs non-world-readable permissions (otherwise ssh complains, and refuses to work)
				$this->modfiles['framework'][] = array('type' => 'rdir',
															'path' => "$home/.ssh",
															'perms' => 0700);

			}
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $sessdir,
														'perms' => 0774,
														'always' => true);
			$this->modfiles['framework'][] = array('type' => 'file',
														'path' => '/etc/amportal.conf',
														'perms' => 0660,
														'always' => true);
			$this->modfiles['framework'][] = array('type' => 'file',
														'path' => '/etc/freepbx.conf',
														'perms' => 0660,
														'always' => true);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTRUNDIR,
														'perms' => 0775,
														'always' => true);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => \FreePBX::GPG()->getGpgLocation(),
														'perms' => 0775,
														'always' => true);
			//we may wish to declare these manually or through some automated fashion
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTETCDIR,
														'perms' => 0775,
														'always' => true);
			$this->modfiles['framework'][] = array('type' => 'file',
														'path' => $ASTVARLIBDIR . '/.ssh/id_rsa',
														'perms' => 0600);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTLOGDIR,
														'perms' => 0775,
														'always' => true);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTSPOOLDIR,
														'perms' => 0775);

			//I have added these below individually,
			$this->modfiles['framework'][] = array('type' => 'file',
												   'path' => $FPBXDBUGFILE,
												   'perms' => 0664);
			$this->modfiles['framework'][] = array('type' => 'file',
												   'path' => $FPBX_LOG_FILE,
												   'perms' => 0664);
			//We may wish to declare files individually rather than touching everything
			$this->modfiles['framework'][] = array('type' => 'rdir',
												   'path' => $ASTVARLIBDIR . '/' . $MOHDIR,
												   'perms' => 0775);
			$this->modfiles['framework'][] = array('type' => 'rdir',
												   'path' => $ASTVARLIBDIR . '/sounds',
												   'perms' => 0775);
			$this->modfiles['framework'][] = array('type' => 'file',
												   'path' => '/etc/obdc.ini',
												   'perms' => 0664);
			//we were doing a recursive on this which I think is not needed.
			//Changed to just be the directory
			//^ Needs to be the whole shebang, doesnt work otherwise
			$this->modfiles['framework'][] = array('type' => 'rdir',
												   'path' => $AMPWEBROOT,
												   'perms' => 0775);

			//Anything in bin and agi-bin should be exec'd
			//Should be after everything except but before hooks
			//So that we dont get overwritten by ampwebroot
			$this->modfiles['framework'][] = array('type' => 'execdir',
			'path' => $AMPBIN,
			'perms' => 0775,
			'always' => true);
			$this->modfiles['framework'][] = array('type' => 'execdir',
			'path' => $ASTAGIDIR,
			'perms' => 0775,
			'always' => true);
			$this->modfiles['framework'][] = array('type' => 'execdir',
			'path' => $ASTVARLIBDIR. "/bin",
			'perms' => 0775,
			'always' => true);
			//Merge static files and hook files, then act on them as a single unit
			$fwcCF = $this->fwcChownFiles();
			if(!empty($this->modfiles) && !empty($fwcCF)){
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
		$fcount = 0;
		$output->write("\t"._("Collecting Files..."));
		$exclusive = $input->hasOption('file') ? $input->getOption('file') : null;
		$process = array();
		foreach($this->modfiles as $moduleName => $modfilelist) {
			if(!is_array($modfilelist)) {
				continue;
			}
			foreach($modfilelist as $file) {
				switch($file['type']){
					case 'file':
					case 'dir':
						if(empty($exclusive) || $exclusive == $file['path']) {
							$file['files'] = array($file['path']);
							$fcount++;
						} else {
							continue 2;
						}
					break;
					case 'execdir':
					case 'rdir':
						$files = $this->recursiveDirList($file['path']);
						$children = false;
						if(empty($exclusive) || $exclusive == $file['path']) {
							$file['files'] = array($file['path']);
							$fcount++;
							$children = true;
						}
						foreach($files as $f){
							if(empty($exclusive) || $children || $exclusive == $f) {
								$file['files'][] = $f;
								$fcount++;
							} else {
								continue;
							}
						}
						if(empty($file['files'])) {
							continue 2;
						}
					break;
				}
				$process[$file['type']][] = $file;
			}
		}
		$verbose = $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
		$output->writeln(_("Done"));
		if(!$verbose) {
			$progress = new ProgressBar($output, $fcount);
			$progress->setRedrawFrequency(100);
			$progress->start();
		}
		foreach($process as $type => $modfilelist) {
			foreach($modfilelist as $file) {
				if(!isset($file['path']) || !isset($file['perms']) || !file_exists($file['path'])){
					if(!$verbose) {
						$progress->advance();
					}
					continue;
				}
				//Handle custom ownership (optional)
				$owner = isset($file['owner'])?$file['owner']:$ampowner;
				$group = isset($file['group'])?$file['group']:$ampgroup;
				$owner = \ForceUTF8\Encoding::toLatin1($owner);
				$group = \ForceUTF8\Encoding::toLatin1($group);
				foreach($file['files'] as $path) {
					if($this->checkBlacklist($path)){
						$this->infos[] = sprintf(_('%s skipped by configuration'), $path);
						continue;
					}
					switch($file['type']){
						case 'file':
						case 'dir':
							$this->setPermissions(array($path,$owner,$group,$file['perms']));
						break;
						case 'rdir':
							if(is_dir($path)){
								$this->setPermissions(array($path, $owner, $group, $file['perms']));
							}else{
								$fileperms = $this->stripExecute($file['perms']);
								$this->setPermissions(array($path, $owner, $group, $fileperms));
							}
						break;
						case 'execdir':
							$this->setPermissions(array($path, $owner, $group, $file['perms']));
						break;
					}
					if(!$verbose) {
						$progress->advance();
					}
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

	private function setPermissions($action) {
		if(pathinfo($action[0], PATHINFO_EXTENSION) == 'call'){
			return;
		}
		$this->singleChown($action[0],$action[1],$action[2]);
		$this->singlePerms($action[0], $action[3]);
		$this->d("Setting ".$action[0]." owner to: ".$action[1].":".$action[2].", with permissions of: ".decoct($action[3]));
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
	private function stripExecute($mask){
		$mask = ( $mask & ~0111 );
		return $mask;
	}

	private function singleChown($file, $user, $group){
		clearstatcache(true, $file);
		if(!file_exists($file)) {
			return false;
		}
		$filetype = \freepbx_filetype($file);
		try {
			if($filetype == "link") {
				$link = readlink($file);
				if(file_exists($link)) {
					$this->fs->chown($link,$user);
					$this->fs->chown($file,$user);
				}
			} else {
				$this->fs->chown($file,$user);
			}
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] = sprintf(_('An error occurred while changing ownership on %s'),$file);
			}
		}
		try {
			if($filetype == "link") {
				$link = readlink($file);
				if(file_exists($link)) {
					$this->fs->chgrp($link,$group);
					$this->fs->chgrp($file,$user);
				}
			} else {
				$this->fs->chgrp($file,$group);
			}
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] = sprintf(_('An error occurred while changing groups %s'),$file);
			}
		}
	}

	private function singlePerms($file, $perms){
		if(!trim($file)){
			$this->errors[] = _('We received an empty string for a file name. Some files may not have the proper permissions');
			return false;
		}
		clearstatcache(true, $file);
		if(!file_exists($file)) {
			return false;
		}
		$filetype = \freepbx_filetype($file);
		switch($filetype){
			case 'link':
				$realfile = readlink($file);
				try {
					$this->fs->chmod($realfile,$perms);
				} catch (IOExceptionInterface $e) {
					if(file_exists($realfile)) {
						$this->errors[] = sprintf(_('An error occurred while changing permissions on link %s which points to %s'), $file, $realfile);
					} else {
						//Make sure this isn't a voicemail symlink
						$asd = \FreePBX::Config()->get("ASTSPOOLDIR") . "/voicemail";
						if (strpos($file, $asd) === false) {
							//File does not exist. Now we have a dangling symlink so remove it.
							$this->infos[] = sprintf(_('Removing dangling symlink %s which points to a file that no longer exists'),$file);
							unlink($file);
						}
					}
				}
			break;
			case 'dir':
			case 'socket':
			case 'file':
				try {
					$this->fs->chmod($file,$perms);
				} catch (IOExceptionInterface $e) {
					if($file){
						$this->errors[] = sprintf(_('An error occurred while changing permissions on file %s'),$file);
					}
				}
			break;
			default:
				throw new \Exception(sprintf(_("Unknown filetype of: %s[%s]"),$filetype,$file));
			break;
		}
	}

	private function recursiveDirList($path){
		clearstatcache(true, $path);
		if(!file_exists($path)) {
			return array();
		}
		$list =  array();
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
		$skipped = false;
		foreach($objects as $path => $object){
			if($this->checkBlacklist($path)){
				$skipped = true;
				continue;
			}
			$list[] = $path;
		}
		if($skipped) {
			$this->infos[] = _("One or more files skipped by configuration in freepbx_chown.conf");
		}
		return array_unique($list);
	}

	private function padPermissions($file, $mode){
		if(($mode>>9) == 0){
			return true;
		}else{
			$this->errors[] = sprintf(_('%s Likely will not work as expected'),$file);
			$this->errors[] = sprintf(_('Permissions should be set with a leading 0, example 644 should be 0644 File: %s Permission set as: %s'),$file,$mode);
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
}
