<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class System extends Base {
	public function constructQuery() {
		return [
			'system' => [
				'type' => $this->typeContainer->get('system')->getReference(),
				'description' => 'General System information',
				'resolve' => function($root, $args) {
					return []; //trick the resolver into not thinking this is null
				}
			]
		];
	}

	public function initTypes() {
		$user = $this->typeContainer->create('system');
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
