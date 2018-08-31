<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\ObjectType;

class System extends Base {
	public static function getScopes() {
		return [
				'read:system' => [
						'description' => _('Read system information'),
				]
		];
	}

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
					]
				];
			};
		}
	}

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
				]
			];
		});
	}
}
