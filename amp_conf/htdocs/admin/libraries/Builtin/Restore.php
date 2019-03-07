<?php
namespace FreePBX\Builtin;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$configs = $this->getConfigs();
		$sql = "UPDATE IGNORE freepbx_settings SET `value` = :value WHERE `keyword` = :keyword AND `module` = ''";
		$sth = $this->FreePBX->Database->prepare($sql);
		foreach($configs['settings'] as $keyword => $value) {
			$sth->execute([
				":keyword" => $keyword,
				":value" => $value
			]);
		}
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
				$usth->execute([
					":keyword" => $data['keyword'],
					":value" => $data['value']
				]);
			}
		}
	}

	public function reset() {
		//dont uninstall as it will screw up the entire system
		//Also it shouldn't be allowed even if forced!
	}
}
