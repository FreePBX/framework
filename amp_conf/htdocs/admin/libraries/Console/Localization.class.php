<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//la mesa
use Symfony\Component\Console\Helper\Table;

use Symfony\Component\Process\Process;

use Symfony\Component\Console\Command\HelpCommand;

use Sepia\PoParser\SourceHandler\FileSystem as POFS;
use Sepia\PoParser\Parser as POP;

use Carbon\Carbon;
class Localization extends Command {

	const PROJECT_LIST = ['freepbx','fpbxc'];
	const BASE_URL = 'https://weblate.sangoma.com';
	const BASE_API_URL = '/api';
	const COMPONENTS_URL = '/projects/%project%/components/';
	const TRANSLATIONS_URL = '/components/%project%/%component%/translations/';
	const TRANSLATION_FILE_URL = '/translations/%project%/%component%/%language%/file';

	private $requests;
	private $headers = [];

	protected function configure(){
		$this->setName('localization')
			->setDescription(_('Localization Utilities'))
			->setDefinition(array(
				new InputOption('authorization', null, InputOption::VALUE_REQUIRED, sprintf(_('Authorization Token. Requests are limited to 100/day, without setting this. See %s for more information'),self::BASE_URL.'/accounts/profile/#api')),
				new InputOption('list', null, InputOption::VALUE_NONE, _('List Modules with localizations')),
				new InputOption('update', null, InputOption::VALUE_NONE, _('List Modules with localizations')),
				new InputOption('module', null, InputOption::VALUE_REQUIRED, _('List Modules with localizations')),
				new InputOption('language', null, InputOption::VALUE_REQUIRED, _('List Modules with localizations')),
			));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		if(!empty($input->getOption('authorization'))) {
			$this->setAuth($input->getOption('authorization'));
		}
		if($input->getOption('list')) {
			$this->getList($input,$output);
			return;
		}
		if($input->getOption('update')) {
			$this->update($input,$output);
			return;
		}
		$this->outputHelp($input,$output);
	}

	private function setAuth($token) {
		$this->headers['Authorization'] = 'Token '.$token;
	}

	private function update(InputInterface $input, OutputInterface $output) {
		$module = $input->getOption('module');
		if(empty($module)) {
			foreach($this->getModuleLanuageList() as $module) {
				if(!$module['hasLangs']) {
					continue;
				}
				try {
					$this->updateModulei18n($output, $module['rawname']);
				} catch(\Exception $e) {
					$output->writeln("<error>".$e->getMessage()."</error>");
				}
			}
		} else {
			$translations = $this->updateModulei18n($output, $input->getOption('module'));
		}
	}

	private function getList(InputInterface $input, OutputInterface $output) {
		$modules = $this->getModuleLanuageList();
		$rows = array_map(function($value){
			return [
				$value['name'],
				$value['rawname'],
				implode(",",$value['langs'])
			];
		},$modules);

		$table = new Table($output);
		$table->setHeaders(array(_('Module'),_('Rawname'),_('Languages')));
		$table->setRows($rows);
		$table->render($output);
	}

	private function updateModulei18n(OutputInterface $output, $module,$language=null) {
		$path = \FreePBX::Config()->get('AMPWEBROOT');
		$translations = $this->getTranslations($module);
		foreach($translations['results'] as $translation) {
			if(empty($translation['last_change'])) {
				continue;
			}
			//seconds are not inputed into the po files so remove them for comparison
			$translation['last_change'] = preg_replace("/:\d{2}\.\d*/", ":00", $translation['last_change']);
			$filename = $path."/admin/modules/".$module."/i18n/".$translation['language_code']."/LC_MESSAGES/".$module.".po";
			if(file_exists($filename)) {
				$fileHandler = new POFS($filename);
				$poParser = new POP($fileHandler);
				$catalog  = $poParser->parse();
				$revision = '';
				foreach($catalog->getHeaders() as $header) {
					if(preg_match('/^PO-Revision-Date/i',$header)) {
						$revision = trim(explode(":",$header,2)[1]);
					}
				}
				$previous = Carbon::parse($revision);
				$last = Carbon::parse($translation['last_change']);
				if($previous->getTimestamp() < $last->getTimestamp()) {
					$output->writeln($module."[".$translation['language_code']."] needs an update (".$revision." < ".$translation['last_change'].")");
					$filedata = $this->getRequest()->get($translation['file_url'],$this->headers);
					file_put_contents($filename, $filedata);
				}
			}
		}
	}

	private function getModuleLanuageList() {
		$path = \FreePBX::Config()->get('AMPWEBROOT');
		$modules = \FreePBX::Modules()->getInfo();
		$modules = array_filter($modules, function($value) {
			return !($value['status'] === MODULE_STATUS_BROKEN);
		});
		$final = [];
		foreach($modules as $value) {
			$langs = [];
			if(file_exists($path."/admin/modules/".$value['rawname']."/i18n")) {
				$langs = glob($path."/admin/modules/".$value['rawname']."/i18n/*",GLOB_ONLYDIR);
				array_walk($langs, function(&$v, $k) {
					$v = basename($v);
				});
			}
			$final[$value['rawname']] = [
				'name' => $value['name'],
				'rawname' => $value['rawname'],
				'langs' => $langs,
				'hasLangs' => !empty($langs)
			];
		}
		return $final;
	}

	private function getTranslations($component, $project=null) {
		if(empty($project)) {
			foreach($this->getComponents() as $p => $components) {
				if(isset($components[$component])) {
					$project = $p;
					break;
				}
			}
		}
		if(empty($project)) {
			throw new \Exception("Unable to find $component");
		}
		$translations = $this->getRequest()->get(str_replace(['%project%','%component%'],[$project,$component],self::BASE_API_URL.self::TRANSLATIONS_URL).'?limit=200',$this->headers);
		if($translations->status_code === 200) {
			return json_decode($translations->body,true);
		}
		if($translations->status_code === 429) {
			throw new \Exception("Too many requests to weblate. Consider using an Authorization Token. See --authorization for more information");
		}
		throw new \Exception("Unable to find $component on weblate");
	}

	private function getComponents() {
		$data = $this->getData('components');
		if(!empty($data)) {
			$this->components = $data;
			return $this->components;
		}
		$this->components = [];
		foreach(self::PROJECT_LIST as $project) {
			$components = $this->getRequest()->get(str_replace(['%project%'],[$project],self::BASE_API_URL.self::COMPONENTS_URL).'?limit=200',$this->headers);
			if($components->status_code === 200) {
				$tmp = json_decode($components->body,true)['results'];
				foreach($tmp as $t) {
					$this->components[$project][$t['name']] = $t;
				}
			}
			if($components->status_code === 429) {
				throw new \Exception("Consider using an Authorization Token. See --authorization for more information");
			}
		}
		$this->setData('components', $this->components, time() + 3600);
		return $this->components;
	}

	private function getData($key) {
		$sql = "SELECT `data`, `time` FROM `module_xml` WHERE `id` = :id";
		$sth = \FreePBX::Database()->prepare($sql);
		$sth->execute(array(":id" => 'loc_'.$key));
		$res = $sth->fetch(\PDO::FETCH_ASSOC);
		if(isset($res['data'])) {
			if(!empty($res['expires']) && $res['expires'] < time()) {
				return false;
			}
			return json_decode($res['data'],true);
		}
		return false;
	}

	private function setData($key, $data, $expires=0) {
		$sql = "REPLACE INTO `module_xml` (`id`,`time`,`data`) VALUES (:id,:time,:data)";
		$sth = \FreePBX::Database()->prepare($sql);
		$sth->execute(array(":id" => 'loc_'.$key,":time" => $expires, ":data" => json_encode($data)));
	}

	private function getRequest() {
		if(!empty($this->requests)) {
			return $this->requests;
		}
		$this->requests = \FreePBX::Curl()->requests(self::BASE_URL);
		return $this->requests;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 * @throws \Symfony\Component\Console\Exception\ExceptionInterface
	 */
	protected function outputHelp(InputInterface $input, OutputInterface $output)	 {
		$help = new HelpCommand();
		$help->setCommand($this);
		return $help->run($input, $output);
	}
}
