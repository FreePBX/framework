<?php

namespace FreePBX\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\ObjectType;

/**
 * System
 */
class System extends Base {
	public static function getScopes() {
		return [
				'read:system' => [
						'description' => _('Read system information'),
				],
				'write:system' => [
						'description' => _('Update System Informations'),
				]
		];
	}
	
	/**
	 * mutationCallback
	 *
	 * @return void
	 */
	public function mutationCallback() {
		if($this->checkReadScope('system')) {
			return function() {
				return [
				'addInitialSetup' => Relay::mutationWithClientMutationId([
					'name' => 'updateAdminAuth',
					'description' => _('This will Set the Administator Auth credentials'),
					'inputFields' => $this->getInputFields(),
					'outputFields' => $this->getOutputFields(),
					'mutateAndGetPayload' => function ($input) {
						return $this->addInitialSetup($input);
					}
				]),
				'updateSystemRPM' => Relay::mutationWithClientMutationId([
					'name' => 'updateSystemRPM',
					'description' => _('Update system RPM package'),
					'inputFields' => [],
					'outputFields' => $this->getOutputFields(),
					'mutateAndGetPayload' => function () {
						return $this->yumUpgrade();
					}
				])
				];
			};
		}
	}
	
	/**
	 * queryCallback
	 *
	 * @return void
	 */
	public function queryCallback() {
		if($this->checkReadScope("system")) {
			return function() {
				return [
					'system' => [
						'type' => $this->typeContainer->get('system')->getObject(),
						'description' => 'General System information',
						'resolve' => function($root, $args) {
							return []; //trick the resolver into not thinking this is null
						}
					],
					'fetchRPMUpgradeStatus' => [
						'type' => $this->typeContainer->get('system')->getObject(),
						'description' => _('Check the system yum-upgrade status'),
						'resolve' => function() {
							return $this->yumUpgradeStatus();
						}
					]
				];
			};
		}
	}
	
	/**
	 * getInputFields
	 *
	 * @return void
	 */
	private function getInputFields() {
		return [
			'username' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('PBX GUI administrator username')
			],
			'password' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('PBX GUI administrator password')
			],
			'notificationEmail' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Notification Email address')
			],
			'systemIdentifier' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('System Identity')
			],
			'autoModuleUpdate' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Authomatic module updates(enabled,disabled,emailonly')
			],
			'autoModuleSecurityUpdate' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Authomatic Module Security updates Email address(enabled,disabled)')
			],
			'securityEmailUnsignedModules' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Send Security Emails for Unsigned Modules(enabled,disabled)')
			],
			'updateDay' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Check for update on Every day (monday,tuesday,wednesday,thursday,friday,saterday,sunday)')
			],
			'updatePeriod' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Update sytem time(0to4,4to8,8to12,12to16,16to20,20to0)')
			]
		];
	}
		
	/**
	 * initializeTypes
	 *
	 * @return void
	 */
	public function initializeTypes() {
		$user = $this->typeContainer->create('system', 'object');
		$user->setDescription('System Information');

		$user->addFieldCallback(function() {
			return [
				'version' => [
					'type' => Type::string(),
					'description' => 'Version of the PBX',
					'resolve' => function ($root, $args) {
						return getVersion();
					}
				],
				'engine' => [
					'type' => Type::string(),
					'description' => 'Version of Asterisk',
					'resolve' => function ($root, $args) {
						return engine_getinfo()['version'];
					}
				],
				'needReload' => [
					'type' => Type::boolean(),
					'description' => 'Does the system need to be reloaded?',
					'resolve' => function ($root, $args) {
						return check_reload_needed();
					}
				],
				'message' =>[
					'type' => Type::string(),
					'description' => _('Message for the request')
				],
				'status' =>[
					'type' => Type::boolean(),
					'description' => _('Status for the request')
				],
			];
		});
	}
	
	/**
	 * addInitialSetup
	 *
	 * @param  mixed $settings
	 * @return void
	 */
	private function addInitialSetup($settings) {
		$db = $this->freepbx->Database();
		$username = htmlentities(strip_tags($settings['username']));

		//validate if user already exists
		$sql = $db->prepare("SELECT * FROM ampusers WHERE username LIKE ?");
		$sql->execute(array("%".$username."%"));
		$rows = $sql->fetchAll(\PDO::FETCH_ASSOC);
		if (count($rows) > 0) {
			return ['message' => _("Admin user already exists"),'status' => false];
		}

		$sth = $db->prepare("INSERT INTO `ampusers` (`username`, `password_sha1`, `sections`) VALUES ( ?, ?, '*')");
		$sth->execute(array($username, sha1($settings['password'])));

		$um = new \FreePBX\Builtin\UpdateManager();
		$um->updateUpdateSettings($settings);
		$um->setNotificationEmail($settings['notificationEmail']);
		// need to make in OOBE as framework completed
		$this->completeOOBE('framework');

		return ['message' => _("Initial Setup is completed"),'status' => false];
	}
	
	/**
	 * completeOOBE
	 *
	 * @param  mixed $mod
	 * @return void
	 */
	private function completeOOBE($mod = false) {
		if (!$mod) {
			throw new \Exception("No module given to mark as complete");
		}
		$complete = $this->freepbx->OOBE->getConfig("completed");
		if (!is_array($complete)) {
			$complete = array($mod => $mod);
		} else {
			$complete[$mod] = $mod;
		}

		$this->freepbx->OOBE->setConfig("completed", $complete);
	}
	
	/**
	 * getOutputFields
	 *
	 * @return void
	 */
	private function getOutputFields(){
		return [
			'message' =>[
				'type' => Type::string(),
				'description' => _('Message for the request')
			],
			'status' =>[
				'type' => Type::boolean(),
				'description' => _('Status for the request')
			],
			'transaction_id' => [
				'type' => Type::string(),
				'description' => _('Transaction Id for status check')
			]
		];
	}
	
	/**
	 * yumUpgrade
	 *
	 * @return void
	 */
	private function yumUpgrade(){
		try{
			$res = $this->freepbx->Framework->getSystemObj()->startYumUpdate();
			if($res){
				return ['message' => _('Yum Upgrade has been initiated. Please check the status using yumUpgradeStatus api'),'status' => true];
			}else{
				return ['message' => _('Yum Upgrade is already running'),'status' => true];
			}
		}catch(Exception $ex){
			return ['message' => _($ex->message) , 'status' => false];
		}
	}
	
	/**
	 * yumUpgradeStatus
	 *
	 * @return void
	 */
	private function yumUpgradeStatus(){
		$res = $this->freepbx->Framework->getSystemObj()->getYumUpdateStatus();
		
		if($res['status'] == "complete"){
			return ['message' => _('Yum upgrade is completed'), 'status' => true];
		}elseif($res['status'] == "inprogress"){
			return ['message' => _('Yum upgrade is in progress'), 'status' => true];
		}else{
			return ['message' => _('Sorry, yum upgrade has failed'), 'status' => false];
		}
	}
}