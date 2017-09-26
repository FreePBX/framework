<?php

namespace FreePBX\Gqlapi;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Gqlapi\includes\Base;

class Destinations extends Base {
	private $destinations;
	public function initReferences() {
		$user = $this->typeContainer->get('destination');
		$user->addFields([
			'id' => [
				'type' => Type::id()
			],
			'description' => [
				'type' => Type::string()
			]
		]);
		$user->addResolve(function($value, $args, $context, $info) {
			$destinations = $this->getDestinations();
			return ($info->fieldName == 'id') ? $value : $destinations[$value][$info->fieldName];
		});
	}

	private function getDestinations() {
		if(empty($this->destinations)) {
			$this->destinations = $this->freepbx->Modules->getDestinations();
		}
		return $this->destinations;
	}
}
