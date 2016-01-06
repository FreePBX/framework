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
	private $errors = array();
	private $infos = array();
	private $quiet = false;
	public $moduleName = '';
	protected function configure(){
		$this->setName('chown')
		->setDescription(_('Change ownership of files'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
		$this->fs = new Filesystem();
		$this->modfiles = array();
		$this->actions = new \SplQueue();
		$this->actions->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO | \SplDoublyLinkedList::IT_MODE_DELETE);
	}
	protected function execute(InputInterface $input, OutputInterface $output, $quiet=false){
		if(posix_geteuid() != 0) {
			$output->writeln("<error>"._("You need to be root to run this command")."</error>");
			exit(1);
		}
		$this->quiet = $quiet;
		if(!$this->quiet) {
			$output->writeln(_("Setting Permissions")."...");
		}
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
		$sessdir = !empty($session) ? $session : '/var/lib/php/session';
		$args = array();
		if($input){
			$args = $input->getArgument('args');
			$this->moduleName = !empty($this->moduleName) ? $this->moduleName : strtolower($args[0]);
		}
		// Always update hooks before running a Chown
		\FreePBX::Hooks()->updateBMOHooks();
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
														'perms' => 0744);
			$this->modfiles['framework'][] = array('type' => 'file',
														'path' => '/etc/amportal.conf',
														'perms' => 0640);
			$this->modfiles['framework'][] = array('type' => 'file',
														'path' => '/etc/freepbx.conf',
														'perms' => 0640);
			$this->modfiles['framework'][] = array('type' => 'dir',
														'path' => $ASTRUNDIR,
														'perms' => 0755);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => \FreePBX::GPG()->getGpgLocation(),
														'perms' => 0755);
			//we may wish to declare these manually or through some automated fashion
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTETCDIR,
														'perms' => 0750);
			$this->modfiles['framework'][] = array('type' => 'file',
														'path' => $ASTVARLIBDIR . '/.ssh/id_rsa',
														'perms' => 0600);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTLOGDIR,
														'perms' => 0755);
			$this->modfiles['framework'][] = array('type' => 'rdir',
														'path' => $ASTSPOOLDIR,
														'perms' => 0755);

			//I have added these below individually,
			$this->modfiles['framework'][] = array('type' => 'file',
												   'path' => $FPBXDBUGFILE,
												   'perms' => 0644);
			$this->modfiles['framework'][] = array('type' => 'file',
												   'path' => $FPBX_LOG_FILE,
												   'perms' => 0644);
			//We may wish to declare files individually rather than touching everything
			$this->modfiles['framework'][] = array('type' => 'rdir',
												   'path' => $ASTVARLIBDIR . '/' . $MOHDIR,
												   'perms' => 0755);
			$this->modfiles['framework'][] = array('type' => 'rdir',
												   'path' => $ASTVARLIBDIR . '/sounds',
												   'perms' => 0755);
			$this->modfiles['framework'][] = array('type' => 'file',
												   'path' => '/etc/obdc.ini',
												   'perms' => 0644);
			//we were doing a recursive on this which I think is not needed.
			//Changed to just be the directory
			//^ Needs to be the whole shebang, doesnt work otherwise
			$this->modfiles['framework'][] = array('type' => 'rdir',
												   'path' => $AMPWEBROOT,
												   'perms' => 0755);

			//Anything in bin and agi-bin should be exec'd
			//Should be after everything except but before hooks
			//So that we dont get overwritten by ampwebroot
			$this->modfiles['framework'][] = array('type' => 'execdir',
			'path' => $AMPBIN,
			'perms' => 0755);
			$this->modfiles['framework'][] = array('type' => 'execdir',
			'path' => $ASTAGIDIR,
			'perms' => 0755);
			//Merge static files and hook files, then act on them as a single unit
			$fwcCF = $this->fwcChownFiles();
			if(!empty($this->modfiles) && !empty($fwcCF)){
				$this->modfiles = array_merge_recursive($this->modfiles,$fwcCF);
			}
		}

		$ampowner = $AMPASTERISKWEBUSER;
		/* Address concerns carried over from amportal in FREEPBX-8268. If the apache user is different
		 * than the Asterisk user we provide permissions that allow both.
		 */
		$ampgroup =  $AMPASTERISKWEBUSER != $AMPASTERISKUSER ? $AMPASTERISKGROUP : $AMPASTERISKWEBGROUP;
		foreach($this->modfiles as $moduleName => $modfilelist){
			foreach($modfilelist as $file){
				if(!isset($file['path']) || !isset($file['perms']) || !file_exists($file['path'])){
						continue;
				}
				//Handle custom ownership (optional)
				$owner = isset($file['owner'])?$file['owner']:$ampowner;
				$group = isset($file['group'])?$file['group']:$ampgroup;
				//Set warning for bad permissions and move on
				$this->padPermissions($file['path'],$file['perms']);
				$file['type'] = isset($file['type'])?$file['type']:'file';
				switch($file['type']){
					case 'file':
					case 'dir':
						$path = \ForceUTF8\Encoding::toLatin1($file['path']);
						$owner = \ForceUTF8\Encoding::toLatin1($owner);
						$group = \ForceUTF8\Encoding::toLatin1($group);
						$json = @json_encode(array($path,$owner,$group,$file['perms']));
						$err = $this->jsonError();
						if(empty($err)) {
							$this->actions->enqueue($json);
						} else {
							$this->errors[] = sprintf(_('An error occurred while adding file %s because %s'), $f, $err);
						}
						break;
					case 'rdir':
						$fileperms = $this->stripExecute($file['perms']);
						$files = $this->recursiveDirList($file['path']);
						$path = \ForceUTF8\Encoding::toLatin1($file['path']);
						$owner = \ForceUTF8\Encoding::toLatin1($owner);
						$group = \ForceUTF8\Encoding::toLatin1($group);
						$json = @json_encode(array($path, $owner, $group, $file['perms']));
						$err = $this->jsonError();
						if(empty($err)) {
							$this->actions->enqueue($json);
						} else {
							$this->errors[] = sprintf(_('An error occurred while adding file %s because %s'), $f, $err);
						}
						foreach($files as $f){
							if(is_dir($f)){
								$path = \ForceUTF8\Encoding::toLatin1($f);
								$owner = \ForceUTF8\Encoding::toLatin1($owner);
								$group = \ForceUTF8\Encoding::toLatin1($group);
								$json = @json_encode(array($path, $owner, $group, $file['perms']));
								$err = $this->jsonError();
								if(empty($err)) {
									$this->actions->enqueue($json);
								} else {
									$this->errors[] = sprintf(_('An error occurred while adding file %s because %s'), $f, $err);
								}
							}else{
								$path = \ForceUTF8\Encoding::toLatin1($f);
								$owner = \ForceUTF8\Encoding::toLatin1($owner);
								$group = \ForceUTF8\Encoding::toLatin1($group);
								$json = @json_encode(array($path, $owner, $group, $fileperms));
								$err = $this->jsonError();
								if(empty($err)) {
									$this->actions->enqueue($json);
								} else {
									$this->errors[] = sprintf(_('An error occurred while adding file %s because %s'), $f, $err);
								}
							}
						}
						break;
					case 'execdir':
						$files = $this->recursiveDirList($file['path']);
						$path = \ForceUTF8\Encoding::toLatin1($file['path']);
						$owner = \ForceUTF8\Encoding::toLatin1($owner);
						$group = \ForceUTF8\Encoding::toLatin1($group);
						$json = @json_encode(array($path, $owner, $group, $file['perms']));
						$err = $this->jsonError();
						if(empty($err)) {
							$this->actions->enqueue($json);
						} else {
							$this->errors[] = sprintf(_('An error occurred while adding file %s because %s'), $f, $err);
						}
						foreach($files as $f){
							$path = \ForceUTF8\Encoding::toLatin1($f);
							$owner = \ForceUTF8\Encoding::toLatin1($owner);
							$group = \ForceUTF8\Encoding::toLatin1($group);
							$json = @json_encode(array($path, $owner, $group, $file['perms']));
							$err = $this->jsonError();
							if(empty($err)) {
								$this->actions->enqueue($json);
							} else {
								$this->errors[] = sprintf(_('An error occurred while adding file %s because %s'), $f, $err);
							}
						}
						break;
				}
			}
		}
		$actioncount = count($this->actions);
		if(!$this->quiet) {
			$progress = new ProgressBar($output, $actioncount);
			$progress->setRedrawFrequency(100);
			$progress->start();
		}
		foreach($this->actions as $action){
			$action = json_decode($action,true);
			//Ignore call files, Asterisk may process/delete them before we get to them.
			if(pathinfo($action[0], PATHINFO_EXTENSION) == 'call'){
				continue;
			}
			$this->singleChown($action[0],$action[1],$action[2]);
			$this->singlePerms($action[0], $action[3]);
			if(!$this->quiet) {
				$progress->advance();
			}
		}
		if(!$this->quiet) {
			$progress->finish();
			$output->writeln("");
			$output->writeln("Finished setting permissions");
			foreach($this->errors as $error) {
				$output->writeln("<error>".$error."</error>");
			}
			foreach($this->infos as $error) {
				$output->writeln("<info>".$error."</info>");
			}
		}
	}
	private function stripExecute($mask){
		$mask = ($mask & ~(1<<0));
		$mask = ($mask & ~(1<<3));
		$mask = ($mask & ~(1<<6));
		return $mask;
	}
	private function singleChown($file, $user, $group){
		try {
			$filetype = filetype($file);
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
			$filetype = filetype($file);
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
	private function recursiveChown($dir, $user, $group){
		try {
			$filetype = filetype($dir);
			if($filetype == "link") {
				$link = readlink($dir);
				if(file_exists($link)) {
					$this->fs->chown($link,$user, true);
					$this->fs->chown($dir,$user, true);
				}
			} else {
				$this->fs->chown($dir,$user, true);
			}
		} catch (IOExceptionInterface $e) {
			if($dir){
				$this->errors[] = sprintf(_('An error occurred while changing ownership %s'),$dir);
			}
		}
		try {
			$filetype = filetype($dir);
			if($filetype == "link") {
				$link = readlink($dir);
				if(file_exists($link)) {
					$this->fs->chgrp($link,$user, true);
					$this->fs->chgrp($dir,$user, true);
				}
			} else {
				$this->fs->chgrp($dir,$group, true);
			}
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] = sprintf(_('An error occurred while changing group %s'),$dir);
			}
		}
	}
	private function singlePerms($file, $perms){
		if(!trim($file)){
			$this->errors[] = _('We received an empty string for a file name. Some files may not have the proper permissions');
			return false;
		}
		$filetype = filetype($file);
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
	private function recursivePerms($dir, $perms){
		try {
			$this->fs->chmod($dir,$perms, 0000, true);
		} catch (IOExceptionInterface $e) {
			if($dir){
				$this->errors[] = sprintf(_('An error occurred while changing permissions %s'),$dir);
			}
		}
	}
	private function recursiveDirList($path){
		$list =  array();
		$files = scandir($path);
		foreach($files as $file){
			if ($file == '.' || $file == '..'){
				continue;
			}
			$fullpath = $path . '/' . $file;
			$filetype = filetype($fullpath);
			if($filetype == 'dir'){
				$list[] = $fullpath;
				$getFiles = $this->recursiveDirList($fullpath);
				foreach($getFiles as $f){
					$list[] = $f;
				}
			}else{
				$list[] = $fullpath;
			}
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

	private function fwcChownFiles(){
		$modules = \FreePBX::Hooks()->processHooks();
		return $modules;
	}

	private function jsonError() {
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return false;
			break;
			case JSON_ERROR_DEPTH:
				return 'Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				return 'Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				return 'Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				return 'Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				return 'Unknown error';
			break;
			}
	}
}
