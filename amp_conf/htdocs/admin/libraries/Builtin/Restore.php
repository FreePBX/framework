<?php
namespace FreePBX\Builtin;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$sql = "UPDATE IGNORE freepbx_settings SET `value` = :value WHERE `keyword` = :keyword AND `module` = ''";
		$sth = $this->FreePBX->Database->prepare($sql);
		foreach($configs['settings'] as $keyword => $value) {
			if ($keyword === 'AMPMGRPASS') {
				$this->log(sprintf(_("Ignorning restore of AMPMGRPASS Advanced Settings from %s"), $module));
				continue;
			}
			$sth->execute([
				":keyword" => $keyword,
				":value" => $value
			]);
		}
		$this->FreePBX->Framework->amiUpdate(true,true,true);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$sql = "SELECT `keyword`, `value` FROM freepbx_settings WHERE module= ''";
		$sth = $pdo->prepare($sql);
		$sth->execute();
		$res = $sth->fetchAll(\PDO::FETCH_ASSOC);

		if(!empty($res)) {
			$this->log(sprintf(_("Importing Advanced Settings from %s"), $module));
			$sql = "UPDATE IGNORE freepbx_settings SET `value` = :value WHERE `keyword` = :keyword AND `module` = ''";
			$usth = $this->FreePBX->Database->prepare($sql);

			foreach($res as $data) {
				if ($data['keyword'] === 'AMPMGRPASS') {
					$this->log(sprintf(_("Ignorning restore of AMPMGRPASS Advanced Settings from %s"), $module));
					continue;
				}
				$val = str_replace('\r\n', "\r\n", $data['value']);
				$usth->execute([
					":keyword" => $data['keyword'],
					":value" => $val
				]);
			}
		}
		$this->FreePBX->Framework->amiUpdate(true,true,true);
	}

	public function reset() {
		//dont uninstall as it will screw up the entire system
		//Also it shouldn't be allowed even if forced!
	}
}
