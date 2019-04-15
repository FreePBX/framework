<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * This is the Cron Handler for the FreePBX Big Module Object.
 * Cron Class. Adds and removes entries to Crontab.
 *
 * If run as root, can manage any user:
 *   $cron = new Cron('username');
 *
 * Otherwise manages current user.
 *
 * $cron->add("@monthly /bin/true");
 * $cron->remove("@monthly /bin/true");
 * $cron->add(array("magic" => "@monthly", "command" => "/bin/true"));
 * $cron->add(array("hour" => "1", "command" => "/bin/true"));
 * $cron->removeAll("/bin/true");
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Job {
	private $db;
	private $freepbx;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->freepbx = $freepbx;
		$this->db = $this->freepbx->Database;
	}

	public function getAll() {
		return $this->db->query("SELECT * FROM cron_jobs")->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function getAllEnabled() {
		return $this->db->query("SELECT * FROM cron_jobs WHERE `enabled` = 1")->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function add($modulename, $jobname, $command, $class, $schedule, $max_runtime, $enabled) {
		$sth = $this->db->prepare("INSERT INTO cron_jobs (`modulename`, `jobname`, `command`, `class`, `schedule`, `max_runtime`, `enabled`) VALUES (:modulename,:jobname,:command,:class,:schedule,:max_runtime,:enabled) ON DUPLICATE KEY UPDATE `command` = :command, `class` = :class, `schedule` = :schedule, `max_runtime` = :max_runtime, `enabled` = :enabled");
		return $sth->execute([
			':modulename' => $modulename,
			':jobname' => $jobname,
			':command' => $command,
			':class' => $class,
			':schedule' => $schedule,
			':max_runtime' => $max_runtime,
			':enabled' => ($enabled ? 1 : 0)
		]);
	}

	public function remove($modulename, $jobname) {
		$sth = $this->db->prepare("DELETE FROM cron_jobs WHERE `modulename` = :modulename AND `jobname` = :jobname");
		return $sth->execute([
			':modulename' => $modulename,
			':jobname' => $jobname
		]);
	}

	public function update($modulename, $jobname, $command, $class, $schedule, $max_runtime, $enabled) {
		return $this->add($modulename, $jobname, $command, $class, $schedule, $max_runtime, $enabled);
	}

	public function removeAll() {
		return $this->db->query("DELETE FROM cron_jobs");
	}

	public function removeAllByModule($module) {
		$sth = $this->db->prepare("DELETE FROM cron_jobs WHERE `modulename` = :modulename");
		return $sth->execute([
			':modulename' => $modulename
		]);
	}

	public function setEnabled($modulename, $jobname, $enabled = true) {
		$sth = $this->db->prepare("UPDATE cron_jobs SET `enabled` = :enabled WHERE `modulename` = :modulename AND `jobname` = :jobname");
		return $sth->execute([
			':enabled' => ($enabled ? 1 : 0)
		]);
	}

	public function updateSchedule($modulename, $jobname, $schedule) {
		$sth = $this->db->prepare("UPDATE cron_jobs SET `schedule` = :schedule WHERE `modulename` = :modulename AND `jobname` = :jobname");
		return $sth->execute([
			':schedule' => $schedule
		]);
	}
}
