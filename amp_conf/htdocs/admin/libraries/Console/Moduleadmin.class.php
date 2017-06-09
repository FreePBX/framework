<?php
namespace FreePBX\Console\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Moduleadmin extends Command {
	private $activeRepos = [];
	private $mf = null;
	private $setRepos = false;
	private $format = 'plain';
	private $pretty = false;
	private $skipchown = false;
	private $previousEdge = 0;
	private $tag = null;
	private $send_email = false;
	private $email_to = false;
	private $email_from = false;
	private $email_subject = false;
	private $emailbody = [];
	private $brand = false;
	private $updatemanager;
	private $willupdate = false;

	public function __destruct() {
		if (!$this->send_email) {
			return;
		}
		if (!$this->emailbody) {
			return;
		}

		// We are sending an email.
		$body = array_merge([
			sprintf(_("This is an automatic notification from your %s server."), $this->brand),
			"",
		], $this->emailbody);

		// Note this is force = true, as we always want to send it.
		$this->updatemanager->sendEmail("autoupdates", $this->email_to, $this->email_from, $this->email_subject, implode("\n", $body), 4, true);
	}

	protected function configure(){
		$this->setName('ma')
		->setAliases(array('moduleadmin'))
		->setDescription('Module Administration')
		->setDefinition(array(
			new InputOption('force', 'f', InputOption::VALUE_NONE, _('Force operation (skips dependency and status checks) <warning>WARNING:</warning> Use at your own risk, modules have dependencies for a reason!')),
			new InputOption('debug', 'd', InputOption::VALUE_NONE, _('Output debug messages to the console (be super chatty)')),
			new InputOption('edge', '', InputOption::VALUE_NONE, _('Download/Upgrade forcing edge mode')),
			new InputOption('color', '', InputOption::VALUE_NONE, _('Colorize table based list')),
			new InputOption('skipchown', '', InputOption::VALUE_NONE, _('Skip the chown operation')),
			new InputOption('autoenable', 'e', InputOption::VALUE_NONE, _('Automatically enable disabled modules without prompting')),
			new InputOption('skipdisabled', '', InputOption::VALUE_NONE, _('Don\'t ask to enable disabled modules assume no.')),
			new InputOption('snapshot', '', InputOption::VALUE_REQUIRED, _('Take a snapshot')),
			new InputOption('format', '', InputOption::VALUE_REQUIRED, sprintf(_('Format can be: %s'),'json, jsonpretty')),
			new InputOption('repo', 'R', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, _('Set the Repos. -R Commercial -R Contributed')),
			new InputOption('tag', 't', InputOption::VALUE_REQUIRED, _('Download/Upgrade to a specific tag')),
			new InputOption('onlystdout', '', InputOption::VALUE_NONE, _('Only send results to stdout.')),
			new InputOption('willupdate', '', InputOption::VALUE_NONE, _('Add "This machine will automatically update in 1 hour" to the email sent.')),
			new InputOption('securityonly', '', InputOption::VALUE_NONE, _('Only automatically update security issues.')),
			new InputOption('sendemails', '', InputOption::VALUE_NONE, _('Send emails when running listonline')),
			new InputArgument('args', InputArgument::IS_ARRAY, 'arguments passed to module admin, this is s stopgap', null),))
		->setHelp($this->showHelp());
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$this->mf = \module_functions::create();
		$this->out = $output;
		$this->input = $input;
		$this->color = false;
		$args = $input->getArgument('args');
		if($input->getOption('skipchown')) {
			$this->skipchown = true;
		}
		if($input->getOption('color')) {
			$this->color = true;
		}
		if ($input->getOption('tag')) {
			$this->tag = $input->getOption('tag');
		}
		if($input->getOption('edge')) {
			$this->writeln('<info>'._('Edge repository temporarily enabled').'</info>');
			$this->previousEdge = \FreePBX::Config()->get('MODULEADMINEDGE');
			\FreePBX::Config()->update('MODULEADMINEDGE',1);
		}
		if ($input->getOption('debug')) {
			$this->DEBUG = True;
		} else {
			$this->DEBUG = False;
		}
		if(!empty($input->getOption('snapshot'))){
			$ret = array();
			$ret['repos'] = array();
			$version = $input->getOption('snapshot');
			$ret['version'] = $version;
			$ret['modules'] = array();
			$modules = $this->mf->getinfo(false, \MODULE_STATUS_ENABLED);
			foreach ($modules as $mod => $modinfo) {
				$ret['modules'][$mod] = $modinfo['version'];
				$ret['repos'][$modinfo['repo']] = $modinfo['repo'];
			}
			$this->format = 'json';
			$this->jsonpretty = true;
			$this->writeln($ret);
			return true;
		}
		switch($input->getOption('format')) {
			case "jsonpretty":
				$this->pretty = true;
				$this->format = 'json';
			break;
			case "json":
				$this->format = 'json';
			break;
			default:
				$this->format = 'plain';
			break;
		}

		if ($input->getOption('force')) {
			$this->force = True;
			if($this->DEBUG){
				$this->writeln(_('Force Enabled'));
			}
		} else {
			$this->force = False;
			if($this->DEBUG){
				$this->writeln(_('Force Disabled'));
			}
		}
		$repos = $input->getOption('repo');
		if($repos){
			$this->write(_("Getting Remote Repo list..."));
			$remotes = $this->mf->get_remote_repos(true);
			$this->writeln(_("Done"));
			$local = $this->mf->get_active_repos();
			foreach ($repos as $repo) {
				if(in_array($repo, $remotes)) {
					$this->setRepos = true;
					if(!in_array($repo, array_keys($local))) {
						$this->writeln("Enabling repo: [$repo]");
						$this->mf->set_active_repo($repo);
					}
				} else {
					$this->writeln("No such repo: [$repo], skipping");
				}
			}
		}

		if (!$input->getOption('onlystdout')) {
			$this->updatemanager = new \FreePBX\Builtin\UpdateManager();
			$sendemails = new Sendemails();

			$settings = $this->updatemanager->getCurrentUpdateSettings(false); // Don't html encode the output
			$email_to = $settings['notification_emails'];
			$machine_id = $settings['system_ident'];
			$email_from = $sendemails->getFromEmail();
			if($this->DEBUG) {
				$this->writeln(sprintf("email_to: %s, machine_id: %s, fromemail: %s\n", $email_to, $machine_id, $email_from));
			}
			if ($email_to) {
				$this->send_email = true;
				$this->email_to = $email_to;
				$this->email_from = $email_from;
				$this->email_subject = sprintf(_("%s Upgrade Notifications (%s)"), "FreePBX", $machine_id);
		      		$this->brand = \FreePBX::Config()->get('DASHBOARD_FREEPBX_BRAND');
			} else {
				if($this->DEBUG) {
					$this->writeln("Not sending email, no email_to");
				}
			}
		}

		$this->willupdate = $input->getOption('willupdate');
		$this->sendemails = $input->getOption('sendemails');
		$this->securityonly = $input->getOption('securityonly');

		if(!empty($args)){
			if($this->DEBUG){
				print_r($args);
			}
			$this->handleArgs($args,$output);
		} else {
			$this->writeln($this->showHelp());
		}
		if($input->getOption('edge')) {
			\FreePBX::Config()->update('MODULEADMINEDGE',$this->previousEdge);
		}

	}

	private function writeln($data, $type = 'message', $status = true) {
		switch($this->format) {
			case "json":
				if($this->pretty) {
					$this->out->writeln($this->prettyPrint(json_encode(array(
						"status" => $status,
						"type" => $type,
						"data" => $data
					))));
				} else {
					$this->out->writeln(json_encode(array(
						"status" => $status,
						"type" => $type,
						"data" => $data
					)));
				}
			break;
			default:
			switch($type) {
				case "error":
					$this->out->writeln("<error>".$data."</error>");
				break;
				default:
					$this->out->writeln($data);
				break;
			}
			break;
		}
	}

	private function write($data, $type = 'message', $status = true) {
		switch($this->format) {
			case "json":
				if($this->pretty) {
					$this->out->writeln($this->prettyPrint(json_encode(array(
						"status" => $status,
						"type" => $type,
						"data" => $data
					))));
				} else {
					$this->out->writeln(json_encode(array(
						"status" => $status,
						"type" => $type,
						"data" => $data
					)));
				}
			break;
			default:
				switch($type) {
					case "error":
						$this->out->write("<error>".$data."</error>");
					break;
					default:
						$this->out->write($data);
					break;
				}
			break;
		}
	}

	private function enableRepo($repo){
		$this->write(_("Getting Remote Repo list..."));
		$this->activeRepos = $this->mf->get_remote_repos(true);
		$this->writeln(_("Done"));
		$this->mf->set_active_repo(strtolower($repo),1);
		if(!in_array($repo,$this->activeRepos)) {
			$this->writeln(_("Repo ").$repo._(" successfully enabled, but was not found in the remote list"));
		}else{
			$this->writeln(_("Repo ").$repo._(" successfully enabled"));
		}
	}

	private function disableRepo($repo){
		$this->mf->set_active_repo(strtolower($repo),0);
		$this->write(_("Getting Remote Repo list..."));
		$remote = $this->mf->get_remote_repos(true);
		$this->writeln(_("Done"));
		if(!in_array($repo,$remote)) {
			$this->writeln(_("Repo ").$repo._(" successfully disabled, but was not found in the remote list"));
		} else {
			$this->writeln(_("Repo ").$repo._(" successfully disabled"));
		}
	}

	private function doReload() {
		$result = do_reload();
		if ($result['status'] != true) {
			$this->writeln(_("Error(s) have occured, the following is the retrieve_conf output:"), "error", false);
			$retrieve_array = explode('<br/>',$result['retrieve_conf']);
			foreach ($retrieve_array as $line) {
				$this->writeln($line, "error", false);
			}
		}else{
			$this->writeln($result['message']);
		}
	}

	private function getFwconsolePath() {
		static $fwconsole = false;
		if (!$fwconsole) {
			// Try to find our fwconsole.
			try {
				$fwconsole = \FreePBX::Config()->get('AMPSBIN')."/fwconsole";
			} catch (\Exception $e) {
				$fwconsole = "/usr/sbin/fwconsole";
			}
			if (!file_exists($fwconsole)) {
				$attempts = array ("/var/lib/asterisk/bin/fwconsole", "/usr/sbin/fwconsole", "/usr/local/sbin/fwconsole", "/var/www/html/admin/modules/framework/amp_conf/bin/fwconsole", "/usr/local/bin/fwconsole");
				$found = false;
				foreach ($attempts as $f) {
					if (file_exists($f)) {
						$found = true;
						$fwconsole = $f;
						break;
					}
				}
				if (!$found) {
					// Well.. Just hope it's in the path somehwere.
					$fwconsole = "fwconsole";
				}
			}
		}
		return $fwconsole;
	}

	private function doForkInstall($modulename, $force) {

		$fwconsole = $this->getFwconsolePath();

		$descriptorspec = array(
			0 => array("pipe","r"),
			1 => STDOUT,
			2 => STDERR
		);
		$force = $force ? "--force" : "";
		$cmd = "$fwconsole --onlystdout ma install ".escapeshellarg($modulename)." ".$force;
		$process = proc_open($cmd, $descriptorspec, $pipes);
		if( is_resource( $process ) ) {
			// Close stdin
			fclose($pipes[0]);
			// Now we can just wait for the process to finish
			$result = proc_close($process);
			return $result;
		} else {
			print "Error, unable to proc_open '$cmd'\n";
			return false;
		}
	}

	private function doInstall($modulename, $force) {
		$start = time();
		\FreePBX::Modules()->loadAllFunctionsInc();
		$module = $this->mf->getinfo($modulename);
		$modulestatus = isset($module[$modulename]['status'])?$module[$modulename]['status']:false;
		if($modulestatus === 1 && !$this->input->getOption('skipdisabled') && !$this->input->getOption('autoenable')){
			$helper = $this->getHelper('question');
			$question = new ChoiceQuestion(sprintf(_("%s appears to be disabled. What would you like to do?"),$modulename),array(_("Continue"), _("Enable"),_("Cancel")),0);
			$question->setErrorMessage('Choice %s is invalid');
			$action = $helper->ask($this->input,$this->out,$question);
			switch($action){
				case _("Enable"):
					$this->mf->enable($modulename, $force);
				break;
				case _("Cancel"):
					exit;
				break;
			}
		}
		if($this->input->getOption('autoenable') && $modulestatus === 1){
			$this->writeln(sprintf(_("Enabling %s because autoenable was passed at the command line"),$modulename));
			$this->mf->enable($modulename, $force);
		}
		if(!$force && !$this->mf->resolveDependencies($modulename,array($this,'progress'))) {
			$this->writeln(sprintf(_("Unable to resolve dependencies for module %s:"),$modulename), "error", false);
			$this->addToEmail(sprintf(_("Module %s installation failed, could not resolve dependencies"), $name));
			return false;
		} else {
			if (is_array($errors = $this->mf->install($modulename, $force))) {
				$this->writeln("Unable to install module ${modulename}:", "error", false);
				$this->writeln(' - '.implode("\n - ",$errors), "error", false);
				$this->addToEmail(sprintf(_("Module %s installation failed with errors: %s"), $modulename, implode("\n -", $errors)));
				return false;
			} else {
				$this->writeln("Module ".$modulename." successfully installed");
				$this->addToEmail(sprintf(_("Module %s installation completed in %s seconds"), $modulename, time()-$start));
			}
		}
		return true;
	}

	private function doRemoteDownload($modulelocation) {
		$this->writeln("Starting module download from {$modulelocation} ...");
		if (is_array($errors = $this->mf->handledownload($modulelocation, array($this,'progress')))) {
			$this->writeln(_("The following error(s) occured:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
			exit(2);
		} else {
			$this->writeln(sprintf(_("Module %s successfully downloaded"),$modulelocation));
		}
		return true;
	}

	private function doDownload($modulename, $force) {
		global $modulexml_path;
		global $modulerepository_path;

		// If we have a version tag, use it
		if ($this->tag) {
			$xml = $this->mf->getModuleDownloadByModuleNameAndVersion($modulename, $this->tag);
			if (empty($xml)) {
				$this->writeln("Unable to update module ${modulename} - ".$this->tag.":", "error", false);
				return false;
			}
			return $this->doRemoteDownload($xml['downloadurl']);
		}

		$line = sprintf(_("Downloading module '%s'"), $modulename);
		$this->writeln($line);

		// Try to get the module
		$start = time();
		$result = $this->mf->download($modulename, $this->force, array($this,'progress'), $modulerepository_path, $modulexml_path);
		$end = time();

		$elapsed = $end - $start;

		if ($result !== true) {
			$this->addToEmail(sprintf(_("Downloading module '%s' failed after %s seconds!"), $modulename, $elapsed));
			$line = _("The following error(s) occured:");
			$this->writeln($line, "error", false);
			$this->addToEmail($line);
			$this->writeln(' - '.implode("\n - ",$result), "error", false);
			$this->addToEmail(' - '.implode("\n - ",$result));
			$this->addToEmail(_("The automatic upgrade was aborted, and will be automatically retried in the future. You can manually re-run the upgrade with 'fwconsole ma installall' after resolving the error."));
			exit(2);
		}

		$this->writeln(sprintf(_("Download completed in %s seconds"), $elapsed));
		$this->addToEmail(sprintf(_("Module %s successfully downloaded in %s seconds"),$modulename, $elapsed));
		return true;
	}

	public function progress($type, $data) {
		switch($type) {
			case "verifying":
				switch($data['status']) {
					case 'start':
						$this->write("Verifying local module download...");
					break;
					case 'redownload':
						$this->writeln("Redownloading");
					break;
					case 'verified':
						$this->writeln("Verified");
					break;
				}
			break;
			case "getinfo":
				$this->writeln("Processing ".$data['module']);
			break;
			case "downloading":
				if($this->format == 'json') {
					if(!isset($this->progress) && $data['read'] < $data['total']) {
						$this->progress = true;
						$this->writeln(array("read" => $data['read'], "total" => $data['total']),"downloading");
					} elseif(isset($this->progress) && $data['read'] < $data['total']) {
						$this->writeln(array("read" => $data['read'], "total" => $data['total']),"downloading");
					} elseif($data['read'] == $data['total']) {
						if(isset($this->progress) && $data['read'] = $data['total']) {
							$this->writeln(array("read" => $data['read'], "total" => $data['total']),"downloading");
							unset($this->progress);
						}
					}
				} else {
					if(!isset($this->progress) && $data['read'] < $data['total']) {
						$this->progress = new ProgressBar($this->out, $data['total']);
						$this->writeln("Downloading...");
						$this->progress->start();
					} elseif(isset($this->progress) && $data['read'] < $data['total']) {
						$this->progress->setProgress($data['read']);
					} elseif($data['read'] == $data['total']) {
						if(isset($this->progress) && $this->progress->getProgress() != $data['total']) {
							$this->progress->finish();
							$this->writeln("");
							$this->writeln("Finished downloading");
							unset($this->progress);
						}
					}
				}
			break;
			case "done":
				$this->writeln("Done");
			break;
			case "untar":
				$this->write("Extracting...");
			break;
		}
	}

	private function doDelete($modulename, $force) {
		\FreePBX::Modules()->loadAllFunctionsInc();
		if (is_array($errors = $this->mf->delete($modulename, $this->force))) {
			$this->writeln(_("The following error(s) occured:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
			exit(2);
		} else {
			$this->writeln(_("Module ").$modulename._(" successfully deleted"));
		}
	}

	private function doUninstall($modulename, $force) {
		\FreePBX::Modules()->loadAllFunctionsInc();
		if (is_array($errors = $this->mf->uninstall($modulename, $this->force))) {
			$this->writeln(_("The following error(s) occured:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
			exit(2);
		} else {
			$this->writeln(_("Module ").$modulename._(" successfully uninstalled"));
		}
	}

	private function doUpgrade($modulename, $force) {
		$this->doDownload($modulename, $this->force);
		$this->doForkInstall($modulename, $this->force);
	}

	private function doInstallLocal($force) {
		//refresh module cache
		$this->mf->getinfo(false,false,true);
		$module_info=$this->mf->getinfo(false, array(MODULE_STATUS_NOTINSTALLED,MODULE_STATUS_NEEDUPGRADE));
		$modules = array();
		foreach ($module_info as $module) {
			if ($module['rawname'] != 'builtin') {
				$modules[] = $module['rawname'];
			}
		}
		if (in_array('core', $modules)){
			$this->writeln("Installing core...");
			$this->doForkInstall('core', $this->force);
		}
		if (count($modules) > 0) {
			$this->writeln("Installing: ".implode(', ',$modules));
			foreach ($modules as $module => $name) {
				if (($name != 'core')){//we dont want to reinstall core
					\FreePBX::Modules()->loadAllFunctionsInc(); //get functions from other modules, in case we need them here
					$this->writeln("Installing $name...");
					$this->doForkInstall($name, $this->force);
					$this->writeln("");
				}
			}
			$this->writeln(_("Done. All modules installed."));
		} else {
			$this->writeln(_("All modules up to date."));
		}
		return $modules;
	}

	/**
	 * Get all installable modules.
	 *
	 */
	private function getInstallableModules() {
		$modules_online = $this->mf->getonlinexml();
		$module_info = $this->mf->getinfo(false);
		$modules_installable = array();
		foreach ($modules_online as $name) {
			// Theory: module is not in the defined repos, and since it is not local (meaning we loaded it at some point) then we
			//         don't show it. Exception, if the status is BROKEN then we should show it because it was here once.
			//
			if ((!isset($this->activeRepos[$modules_online[$name['rawname']]['repo']]) || !$this->activeRepos[$modules_online[$name['rawname']]['repo']]) && (!isset($module_info[$name['rawname']]) || $module_info[$name['rawname']]['status'] == MODULE_STATUS_NOTINSTALLED)) {
				continue;
			}
			if ((!isset($module_info[$name['rawname']]['status'])) || ($module_info[$name['rawname']]['status'] == MODULE_STATUS_NEEDUPGRADE) || ($module_info[$name['rawname']]['status'] == MODULE_STATUS_NOTINSTALLED)){
				$modules_installable[$name['rawname']]=$name['rawname'];
			}
		}
		return $modules_installable;
	}

	/**
	 * Returns a list of modules to be upgraded
	 *
	 * @return array [ name: => [array], name: => array, ... ]
	 */
	private function getUpgradableModules() {
		$modules_local = $this->mf->getinfo(false, array(MODULE_STATUS_ENABLED,MODULE_STATUS_NEEDUPGRADE));
		$modules_online = $this->mf->getonlinexml();
		$modules_upgradable = array();
		$this->check_active_repos();
		foreach (array_keys($modules_local) as $name) {
			if (isset($modules_online[$name])) {
				if (($modules_local[$name]['status'] == MODULE_STATUS_NEEDUPGRADE) || version_compare_freepbx($modules_local[$name]['version'], $modules_online[$name]['version']) < 0) {
					$modules_upgradable[$name] = [
						'name' => $name,
						'local_version' => $modules_local[$name]['version'],
						'online_version' => $modules_online[$name]['version'],
					];
				}
			}
		}
		return $modules_upgradable;
	}

	/**
	 * This upgrades all modules that need upgrading.
	 *
	 * This is called by the automatica updater, so its output
	 * is saved for sendemail to possibly use
	 */
	private function doUpgradeAll($force) {
		$modules = $this->getUpgradableModules();
		if ($modules) {
			$line = sprintf("Module(s) requiring upgrades: %s", implode(", ", array_keys($modules)));
			$this->addToEmail($line);
			$this->writeln($line);

			// Upgrade framework, core, and sipsettings, first!
			$prepend = ['framework' => 'framework', 'core' => 'core', 'sipsettings' => 'sipsettings'];

			foreach($prepend as $m) {
				if (isset($modules[$m])) {
					$prepend[$m] = $modules[$m];
					unset($modules[$m]);
				} else {
					unset($prepend[$m]);
				}
			}

			$upgrades = $prepend + $modules;
			foreach ($upgrades as $name => $arr) {
				$line = sprintf(_("Upgrading module '%s' from %s to %s"), $name, $arr['local_version'], $arr['online_version']);
				$this->writeln($line);
				$this->addToEmail($line);
				$this->doUpgrade($name, $this->force);
			}
			$line = _("All upgrades completed successfully!");
			$this->writeln($line);
			$this->addToEmail($line);
			$this->addToEmail("");
		} else {
			// We don't add to email here, as people don't need to know that
			// nothing happened.
			$this->writeln(_("Up to date."));
		}
		return $modules;
	}

	private function showi18n($modulename) {
		//special case core so that we have everything we need for localizations
		switch($modulename) {
			case 'core':
				$modules = $this->mf->getinfo();
			break;
			default:
				$modules = $this->mf->getinfo($modulename);
			break;
		}

		$modulesProcessed = array(
			'name' => array(),
			'category' => array(),
			'description' => array(),
			'menuitem' => array()
		);
		foreach ($modules as $rawname => $mod) {
			if (!isset($modules[$rawname])) {
				fatal($rawname._(' not found'));
			}

			if (!in_array($modules[$rawname]['name'], $modulesProcessed['name'])) {
				$modulesProcessed['name'][] = $modules[$rawname]['name'];
				if (isset($modules[$rawname]['name'])) {
					echo "# name:\n_(\"".$modules[$rawname]['name']."\");\n";
				}
			}
			if (!in_array($modules[$rawname]['category'], $modulesProcessed['category'])) {
				$modulesProcessed['category'][] = $modules[$rawname]['category'];
				if (isset($modules[$rawname]['category'])) {
					echo "# category:\n_(\"".str_replace("\n","",$modules[$rawname]['category'])."\");\n";
				}
			}
			if (!in_array($modules[$rawname]['description'], $modulesProcessed['description'])) {
				$moduleProcessed['description'][] = $modules[$rawname]['description'];
				if (isset($modules[$rawname]['description'])) {
					echo "# description:\n_(\"".trim(str_replace("\n","",$modules[$rawname]['description']))."\");\n";
				}
			}
			if (isset($modules[$rawname]['menuitems']) && !empty($modules[$rawname]['menuitems'])) {
				foreach ($modules[$rawname]['menuitems'] as $key => $menuitem) {
					if (!in_array($menuitem, $modulesProcessed['menuitem'])) {
						$modulesProcessed['menuitem'][] = $menuitem;
						echo "# $key:\n_(\"$menuitem\");\n";
					}
				}
			}
		}

		//get our settings
		$freepbx_conf = \freepbx_conf::create();
		$conf = $freepbx_conf->get_conf_settings();
		$settingsProcessed = array(
			'name' => array(),
			'category' => array(),
			'description' => array()
		);
		foreach ($conf as $keyword => $settings) {
			//we don't need hidden settings as the user never sees them
			if ($settings['hidden'] != true && ($rawname == 'framework' && $settings['module'] == '') || $settings['module'] == $modulename) {
				if (!in_array($settings['name'], $settingsProcessed['name'])) {
					$settingsProcessed['name'][] = $settings['name'];
					echo "# Setting name - $keyword:\n_(\"".$settings['name']."\");\n";
				}
				if (!in_array($settings['category'], $settingsProcessed['category'])) {
					$settingsProcessed['category'][] = $settings['category'];
					echo "# Setting category - $keyword:\n_(\"".$settings['category']."\");\n";
				}
				if (!in_array($settings['description'], $settingsProcessed['description'])) {
					$settingsProcessed['description'][] = $settings['description'];
					echo "# Setting description - $keyword:\n_(\"".$settings['description']."\");\n";
				}
			}
		}

		$codes = \featurecode::getAll($rawname);
		foreach($codes as $code) {
			if(!empty($code['description'])) {
				echo "# Feature Code description - description:\n_(\"".$code['description']."\");\n";
			}
			if(!empty($code['helptext'])) {
				echo "# Feature Code helptext - helptext:\n_(\"".$code['helptext']."\");\n";
			}
		}
	}

	private function listDisabled(){
		$modules = $this->mf->getinfo(false, MODULE_STATUS_DISABLED);
		return array_keys($modules);
	}

	private function listEnabled(){
		$modules = $this->mf->getinfo(false, MODULE_STATUS_ENABLED);
		return array_keys($modules);
	}

	private function showCheckDepends($modulename) {
		$modules = $this->mf->getinfo($modulename);
		if (!isset($modules[$modulename])) {
			//TODO: what?
			fatal(sprintf(_('%s not found'),$modulename));
		}
		if (($errors = $this->mf->checkdepends($modules[$modulename])) !== true) {
			$this->writeln(_("The following dependencies are not met:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
			exit(1);
		} else {
			$this->writeln(sprintf(_("All dependencies met for module %s"),$modulename));
		}
	}

	private function showEngine() {
		$engine = engine_getinfo();
		foreach ($engine as $key=>$value) {
			$this->writeln(str_pad($key,15,' ',STR_PAD_LEFT).': '.$value);
		}
	}

	private function setPerms($action,$args) {
		if($this->skipchown) {
			return;
		}
		if (posix_getuid() == 0) {
			$chown = new Chown();
			if(sizeof($args) == 1){
				$chown->moduleName = is_array($args[0]) ? $args[0]['name'] : $args[0];
			}
			$chown->execute($this->input, $this->out, !($this->out->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE));
		}
	}

	private function check_active_repos() {
		if (empty($this->activeRepos)) {
			$this->activeRepos = $this->mf->get_active_repos();
			if(!empty($this->activeRepos) && !$this->setRepos) {
				$list = implode(',',array_keys($this->activeRepos));
				$this->writeln(_("No repos specified, using: [$list] from last GUI settings"));
				$this->writeln("");

			} else {
				$this->write(_("Getting Remote Repo list..."));
				$this->mf->get_remote_repos(true);
				$this->activeRepos = $this->mf->get_active_repos();
				$this->writeln(_("Done"));
				if(!empty($this->activeRepos)) {
					$this->writeln(sprintf(_("Using repos: [%s]"),implode(",",array_keys($this->activeRepos))));
					$this->writeln("");
				}
			}
		}
	}

	/**
	 * This is run by the automatic updater
	 *
	 * It makes sure that all available modules are up to date and
	 * installed on this machine. (It installs newly available modules
	 * so that old, deprecated, or broken modules, can be automatically
	 * repaired or removed)
	 */
	private function doInstallAll() {
		$this->doUpgradeAll(true);
		$modules = $this->getInstallableModules();

		// Belt-and-suspenders check. This makes sure that core is
		// installed, just in case it somehow failed in the doUpgradeAll
		// function, above. Rare, but has happened.
		if (isset($modules['core'])) {
			$this->writeln(_("Installing core..."));
			$this->doDownload('core', $this->force);
			$this->doForkInstall('core', $this->force);
			unset($modules['core']);
		}

		if ($modules) {
			$line = sprintf(_("Installing missing modules: %s"), implode(', ',$modules));
			$this->writeln($line);
			$this->addToEmail($line);

			foreach ($modules as $i => $name) {

				// Get functions from other modules, in case we need them here.
				//
				// FreePBX 14: This should go away, eventually, as we move to
				// autoloaded and OO code. However, it's here for the foreseeable
				// future. Sigh.
				//
				\FreePBX::Modules()->loadAllFunctionsInc();
				$line = sprintf(_("Downloading & Installing '%s'"), $name);
				$this->writeln($line);
				$this->doDownload($name, $this->force);
				$start = time();
				// Note this will addToEmail if it fails.
				$this->doForkInstall($name, $this->force);
				$elapsed = time() - $start;
				$this->addToEmail(sprintf(_("Module %s installation completed in %s seconds"), $name, $elapsed), $line);
				$this->writeln("");
			}
			$line = _("Done. All modules installed.");
			$this->writeln($line);
			$this->addToEmail($line);
		} else {
			// No email needed if no changes.
			$this->writeln(_("All modules up to date."));
		}
		return $modules;
	}

	private function showInfo($modulename) {
		function recursive_print($array, $parentkey = '', $level=0) {
			foreach ($array as $key => $value) {
				if (is_array($value)) {
					// check if there is a numeric key in the sub-array, if so, we don't print the title
					if (!isset($value[0])) {
						$this->writeln(str_pad($key,15+($level * 3),' ',STR_PAD_LEFT).': ');
					}
					recursive_print($value, $key, $level + 1);
				} else {
					if (is_numeric($key)) {
						// its just multiple parent keys, so we don't indent, and print the parentkey instead
						$this->writeln(str_pad($parentkey,15+(($level-1) * 3),' ',STR_PAD_LEFT).': '.$value);
					} else {
						if ($key == 'status') {
							switch ($value) {
								case MODULE_STATUS_NOTINSTALLED: $value = 'Not Installed'; break;
								case MODULE_STATUS_NEEDUPGRADE: $value = 'Disabled; Needs Upgrade'; break;
								case MODULE_STATUS_ENABLED: $value = 'Enabled'; break;
								case MODULE_STATUS_DISABLED: $value = 'Disabled'; break;
								case MODULE_STATUS_BROKEN: $value = 'Broken'; break;
							}
						}
						$this->writeln(str_pad($key,15+($level * 3),' ',STR_PAD_LEFT).': '.$value);
					}
				}
			}
		}
		$modules = $this->mf->getinfo($modulename);
		if (!isset($modules[$modulename])) {
			fatal($modulename.' not found');
		}

		recursive_print($modules[$modulename]);
	}

	private function updateKeys() {
		\FreePBX::GPG()->refreshKeys();
	}

	private function showList($online = false) {
		global $amp_conf;
		$modules_local = $this->mf->getinfo(false,false,true);
		$modules = $modules_local;
		$this->check_active_repos();
		if ($online) {
			$modules_online = $this->mf->getonlinexml();
			if (isset($modules_online)) {
				$modules += $modules_online;
			}
		}
		ksort($modules);
		$this->mf->getAllSignatures(!$online, $online);
		$rows = array();
		foreach (array_keys($modules) as $name) {
			$status_index = isset($modules[$name]['status'])?$modules[$name]['status']:'';
			// Don't include modules not in our repo unless they are locally installed already
			if ((!isset($this->activeRepos[$modules[$name]['repo']]) || !$this->activeRepos[$modules[$name]['repo']]) && $status_index != MODULE_STATUS_BROKEN && !isset($modules_local[$name])) {
				continue;
			}
			switch ($status_index) {
				case MODULE_STATUS_NOTINSTALLED:
					if (isset($modules_local[$name])) {
						$status = _('Not Installed (Locally available)');
					} else {
						$status = _('Not Installed (Available online: ').$modules_online[$name]['version'].')';
					}
					$status = ($this->color && $this->format != 'json')?'<comment>'.$status.'</comment>':$status;
				break;
				case MODULE_STATUS_DISABLED:
					$status = _('Disabled');
					$status = ($this->color && $this->format != 'json')?'<question>'.$status.'</question>':$status;
				break;
				case MODULE_STATUS_NEEDUPGRADE:
					$status = _('Disabled; Pending upgrade to ').$modules[$name]['version'];
					$status = ($this->color && $this->format != 'json')?'<question>'.$status.'</question>':$status;
				break;
				case MODULE_STATUS_BROKEN:
					$status = _('Broken');
					$status = ($this->color && $this->format != 'json')?'<error>'.$status.'</error>':$status;
				break;
				default:
					// check for online upgrade
					if (isset($modules_online[$name]['version'])) {
						$vercomp = version_compare_freepbx($modules[$name]['version'], $modules_online[$name]['version']);
						if ($vercomp < 0) {
							$status = sprintf(_('Online upgrade available (%s)'),$modules_online[$name]['version']);
						} else if ($vercomp > 0) {
							$status = sprintf(_('Newer than online version (%s)'),$modules_online[$name]['version']);
						} else {
							$status = _('Enabled and up to date');
						}
					} else if (isset($modules_online)) {
						// we're connected to online, but didn't find this module
						$status = _('Enabled; Not available online');
					} else {
						$status = _('Enabled');
					}
					$status = ($this->color && $this->format != 'json')?'<info>'.$status.'</info>':$status;

				break;
			}
			$module_version = isset($modules[$name]['dbversion'])?$modules[$name]['dbversion']:'';
			$module_license = isset($modules[$name]['license'])?$modules[$name]['license']:'';
			array_push($rows,array($name, $module_version, $status, $module_license));
		}
		if($this->format == 'json') {
			$this->writeln($rows);
		} else {
			$table = new Table($this->out);
			$table
				->setHeaders(array(_('Module'), _('Version'), _('Status'),_('License')))
				->setRows($rows);
			$table->render();
		}

		// Send any emails if we've been requested to.
		if ($this->sendemails) {
			$fwconsole = $this->getFwconsolePath();
			if ($this->securityonly) {
				$cmd = "$fwconsole --securityonly sendemail";
			}
			$cmd = "$fwconsole sendemail";
			if ($this->willupdate) {
				$cmd .= " --willupdate";
			}
			print "Running $cmd\n";
			exec($cmd);
		}

	}

	private function refreshsignatures() {
		\FreePBX::GPG();
		$fpbxmodules = \FreePBX::Modules();
		$list = $fpbxmodules->getActiveModules();
		$this->writeln(_("Getting Data from Online Server..."));
		//Leaving this here so that we at least know we can get to the mirror server
		$modules_online = $this->mf->getonlinexml();
		if(empty($modules_online)) {
			$this->writeln(_('Cant Reach Online Server'));
			exit(1);
		} else {
			$this->writeln(_("Done"));
		}
		$this->writeln(_("Checking Signatures of Modules..."));
		$modules = array();
		foreach($list as $m) {
			//Check signature status, then if its online then if its signed online then redownload (through force)
			$this->writeln(sprintf(_("Checking %s..."),$m['rawname']));
			$msig = \FreePBX::GPG()->verifyModule($m['rawname']);

			// Check to see if the STATE_GOOD bit is NOT set.
			if(~$msig['status'] & \FreePBX\GPG::STATE_GOOD) {
				$this->writeln(_("Signature Invalid"));
				if(isset($modules_online[$m['rawname']]) && isset($modules_online[$m['rawname']]['signed'])) {
					$this->writeln("\t".sprintf(_("Refreshing %s"),$m['rawname']));
					$modulename = $m['rawname'];
					$moduleversion = $m['version'];
					$modules[] = $modulename;
					$this->doInstallByModuleAndVersion($modulename, $moduleversion, true);
					$this->writeln("\t"._("Verifying GPG..."));
					$this->mf->updateSignature($modulename);
					$this->writeln(_("Done"));
				} else {
					$this->writeln("\t"._("Could not find signed module on remote server!"), "error", false);
				}
			} else {
				$this->writeln(_("Good"));
			}
		}
		$this->writeln(_("Done"));
		return $modules;
	}

	private function showReverseDepends($modulename) {
		$modules = $this->mf->getinfo($modulename);
		if (!isset($modules[$modulename])) {
			fatal(sprintf(_('%s not found'),$modulename));
		}

		if (($depmods = $this->mf->reversedepends($modulename)) !== false) {
			$this->writeln(sprintf(_("The following modules depend on this one: %s"),implode(', ',$depmods)));
			exit(1);
		} else {
			$this->writeln(_("No enabled modules depend on this module."));
		}
	}

	private function updateHooks() {
		$this->write(_("Updating Hooks..."));
		try {
			\FreePBX::Hooks()->updateBMOHooks();
		}catch(\Exception $e) {}
		$this->writeln(_("Done"));
	}

	private function showUpgrades() {
		$modules = $this->getUpgradableModules(true);
		if ($modules) {
			$this->writeln(_("Upgradable: "));
			$rows = array();
			foreach ($modules as $mod) {
				array_push($rows, array($mod['name'],$mod['local_version'],$mod['online_version']));
				//$this->writeln($mod['name'].' '.$mod['local_version'].' -> '.$mod['online_version']);
			}
			if($this->format == 'json') {
				$this->writeln($rows);
			} else {
				$table = new Table($this->out);
				$table
					->setHeaders(array(_('Module'), _('Local Version'), _('Online Version')))
					->setRows($rows);
				$table->render();
			}
		} else {
			$this->writeln(_("Up to date."));
		}
	}

	private function doDisable($modulename, $force) {
		\FreePBX::Modules()->loadAllFunctionsInc();
		if (is_array($errors = $this->mf->disable($modulename, $force))) {
			$this->writeln(_("The following error(s) occured:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
			exit(2);
		} else {
			$this->writeln(sprintf(_("Module %s successfully disabled"),$modulename));
		}
	}

	private function doEnable($modulename, $force) {
		\FreePBX::Modules()->loadAllFunctionsInc();
		if (is_array($errors = $this->mf->enable($modulename, $this->force))) {
			$this->writeln(_("The following error(s) occured:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
			exit(2);
		} else {
			$this->writeln(sprintf(_("Module %s successfully enabled"),$modulename));
		}
	}

	private function tryEnable($modulename, $force) {
		\FreePBX::Modules()->loadAllFunctionsInc();
		if (is_array($errors = $this->mf->enable($modulename, $this->force))) {
			$this->writeln(_("The following error(s) occured:"), "error", false);
			$this->writeln(' - '.implode("\n - ",$errors), "error", false);
		} else {
			$this->writeln(sprintf(_("Module %s successfully enabled"),$modulename));
		}
	}

	private function showHelp(){
		$help = '<info>'._('Module Administration Help').':'.PHP_EOL;
		$help .= _('Usage').': fwconsole moduleadmin [-f][-R reponame][-R reponame][action][arg1][arg2][arg...]</info>' . PHP_EOL;
		$help .= _('Flags').':' . PHP_EOL;
		$help .= '-f - FORCE' . PHP_EOL;
		$help .= '-R - REPO, accepts reponame as a single argument' . PHP_EOL;

		$help .= '<question>'._('Module Actions').':</question>' . PHP_EOL;
		$rows[] = array('checkdepends',_('Checks dependencies for provided module[s], accepts argument module[s]'));
		$rows[] = array('disable',_('Disables module[s] accepts argument module[s]'));
		$rows[] = array('download',_('Download module[s], accepts argument module[s] or URLs'));
		$rows[] = array('downloadinstall',_('Download and install module[s], accepts argument module[s] or URLs'));
		$rows[] = array('delete',_('Deleted module[s], accepts argument module[s]'));
		$rows[] = array('enable',_('Enable module[s], accepts argument module[s]'));
		$rows[] = array('install',_('Installs module[s], accepts argument module[s]'));
		$rows[] = array('uninstall',_('Uninstalls module[s], accepts argument module[s]'));
		$rows[] = array('upgrade',_('Upgrade module[s], accepts argument module[s]'));
		foreach($rows as $k => $v){
			$help .= '<info>'.$v[0].'</info> : <comment>' . $v[1] . '</comment>'. PHP_EOL;
		}
		unset($rows);
		$rows = array();
		$help .= '<question>'._('All inclusive Module Actions').':</question>' . PHP_EOL;
		$rows[] = array('installall',_('Installs all modules, accepts no arguments'));
		$rows[] = array('enableall',_('Trys to enable all modules, accepts no arguments'));
		$rows[] = array('upgradeall',_('Upgrades all modules, accepts no arguments'));
		$rows[] = array('installlocal',_('Install all local modules, accepts no arguments'));
		foreach($rows as $k => $v){
			$help .= '<info>'.$v[0].'</info> : <comment>' . $v[1] . '</comment>'. PHP_EOL;
		}
		unset($rows);
		$rows = array();
		$help .= '<question>Repository Actions:</question>' . PHP_EOL;
		$rows[] = array('disablerepo',_('Disables repo, accepts argument repo[s]'));
		$rows[] = array('enablerepo',_('Enables repo, accepts argument repo[s]'));
		$rows[] = array('list',_('List all local modules, accepts no arguments'));
		$rows[] = array('listonline',_('List online modules, accepts no arguments'));
		$rows[] = array('showupgrades',_('Shows a list of modules that may be updated, accepts no arguments'));
		$rows[] = array('i18n',_('Shows translation information for supplied modules, accepts argument module[s]'));
		$rows[] = array('refreshsignatures',_('ReDownloads all modules that have invalid signatures'));
		foreach($rows as $k => $v){
			$help .= '<info>'.$v[0].'</info> : <comment>' . $v[1] . '</comment>'. PHP_EOL;
		}
		unset($rows);
		$rows = array();
		return $help;

	}

	private function isUrl($str){
		if (parse_url($str, PHP_URL_SCHEME)) {
			return true;
		}
		return false;
	}

	private function handleArgs($args,$output){
		$devmode = \FreePBX::Config()->get('DEVEL');
		$action = array_shift($args);
		switch($action){
			case 'updatekeys':
				$this->updateKeys();
			break;
			case 'install':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					//do NOT fork install here!
					$this->doInstall($module, $this->force);
				}
				$this->updateHooks();
				$this->setPerms($action,$args);
				break;
			case 'installall':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				$this->check_active_repos();
				$modules = $this->doInstallAll();
				$this->updateHooks();
				foreach($modules as $module) {
					$this->setPerms($action,array($module));
				}
				break;
			case 'installlocal':
				$modules = $this->doInstallLocal(true);
				$this->updateHooks();
				foreach($modules as $module) {
					$this->setPerms($action,array($module));
				}
				break;
			case 'uninstall':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->doUninstall($module, $this->force);
				}
				$this->updateHooks();
				break;
			case 'download':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				if(empty($args)){
					fatal(_("Missing module name or URL"));
				}
				$this->check_active_repos();
				foreach($args as $module){
					if($this->isUrl($module)) {
						$this->doRemoteDownload($module);
					} else {
						$this->doDownload($module, $this->force);
					}
				}
				$this->setPerms($action,$args);
				break;
			case 'downloadinstall':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				if(empty($args)){
					fatal(_("Missing module name or URL"));
				}
				$this->check_active_repos();
				foreach($args as $module){
					if($this->isUrl($module)) {
						$this->doRemoteDownload($module);
						if(!empty($this->mf->downloadedRawname)) {
							$this->doInstall($this->mf->downloadedRawname, $this->force);
						} else {
							fatal(_("Could not determine module name"));
						}
					} else {
						$this->doDownload($module, $this->force);
						$this->doInstall($module, $this->force);
					}
				}
				$this->updateHooks();
				$this->setPerms($action,$args);
			break;
			case 'upgrade':
			case 'update':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				$this->check_active_repos();
				foreach($args as $module){
					$this->doUpgrade($module, $this->force);
				}
				$this->updateHooks();
				$this->setPerms($action,$args);
				break;
			case 'updateall':
			case 'upgradeall':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				$this->check_active_repos();
				$modules = $this->doUpgradeAll($force);
				$this->updateHooks();
				foreach($modules as $module) {
					$this->setPerms($action,array($module));
				}
				break;
			case 'list':
				$this->showList();
				break;
			case 'listonline':
				$announcements = $this->mf->get_annoucements();
				$this->showList(true);
				break;
			case 'reversedepends':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->showReverseDepends($module);
				}
				break;
			case 'enablerepo':
				if(empty($args)){
					fatal(_("Missing repo name"));
				}
				foreach($args as $repo){
					$this->enableRepo($repo);
				}
				break;
			case 'disablerepo':
				if(empty($args)){
					fatal(_("Missing repo name"));
				}
				foreach($args as $repo){
					$this->disableRepo($repo);
				}
				foreach($args as $module){

				}
				break;
			case 'checkdepends':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->showCheckDepends($module);
				}
				break;
			case 'remove':
			case 'delete':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->doDelete($module, $this->force);
				}
				$this->updateHooks();
				break;
			case 'disable':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->doDisable($module, $this->force);
				}
				$this->updateHooks();
				break;
			case 'enable':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->doEnable($module, $this->force);
				}
				$this->updateHooks();
				break;
			case 'enableall':
				$modules = $this->listDisabled();
				foreach($modules as $module){
					$this->writeln(sprintf(_('Attempting to Enable %s'),$module));
					$this->tryEnable($module, $this->force);
				}
				$this->writeln(_('This action understands somethings may be disabled for a reason.'));
				$this->writeln(_('Please review the output above for any errors while enabling modules'));
				$this->updateHooks();
				break;
			case 'showupgrade':
			case 'showupgrades':
				$this->check_active_repos();
				$this->showUpgrades();
				break;
			case 'i18n':
				if(empty($args)){
					fatal(_("Missing module name"));
				}
				foreach($args as $module){
					$this->showi18n($module);
				}
				break;
			case 'refreshsignatures':
				if($devmode) {
					fatal(_("Can not run this command while 'Developer Mode' is enabled"));
				}
				$modules = $this->refreshsignatures();
				$this->updateHooks();
				foreach($modules as $module) {
					$this->setPerms($action,array($module));
				}
				break;
			case 'updatexml':
				break;
			case 'help':
			case 'h':
			case '?':
			default:
				$this->writeln(sprintf(_('Unknown Command! (%s)'),$$action), "error", false);
				break;
		}
	}

	private function prettyPrint($json){
		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );

		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
						$level--;
						$ends_line_level = NULL;
						$new_line_level = $level;
					break;

					case '{': case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
					break;

					case ':':
						$post = " ";
					break;

					case " ": case "\t": case "\n": case "\r":
						$char = "";
						$ends_line_level = $new_line_level;
						$new_line_level = NULL;
					break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "\n".str_repeat( "\t", $new_line_level );
			}
			$result .= $char.$post;
		}
		return $result;
	}

	/**
	 * Do a remote download of a specific module version after getting the module
	 * xml back from the mirror server
	 * @param string $modulename The module rawname
	 * @param string $moduleversion The module release version we want to reinstall
	 * @return boolean
	 */
	private function doInstallByModuleAndVersion($modulename, $moduleversion) {
		$xml = $this->mf->getModuleDownloadByModuleNameAndVersion($modulename, $moduleversion);
		if (empty($xml)) {
			$this->writeln("Unable to update module ${modulename} - ${moduleversion}:", "error", false);
			return false;
		}

		\FreePBX::Modules()->loadAllFunctionsInc();
		$this->doRemoteDownload($xml['downloadurl']);
		$this->doForkInstall($modulename, $this->force);
		return true;
	}

	/**
	 * Capture text for sending via email
	 *
	 * This is used when --sendemail is set.
	 *
	 * @param string $line
	 */
	private function addToEmail($line) {
		$this->emailbody[] = $line;
	}

}
