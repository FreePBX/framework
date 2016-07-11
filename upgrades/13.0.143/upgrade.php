<?php
$sbin = \FreePBX::Config()->get("AMPSBIN");
$mf = module_functions::create();
$lock = __DIR__."/lock";
if(!file_exists($lock)) {
	$pid = getmypid();
	file_put_contents($lock,$pid);
	$modules = $mf->getinfo(false, array(MODULE_STATUS_NEEDUPGRADE));
	if(is_array($modules)) {
		foreach($modules as $module) {
			$rawn = $module['rawname'];
			exec($sbin."/fwconsole ma install $rawn --force");
		}
	}
	unlink($lock);
}
