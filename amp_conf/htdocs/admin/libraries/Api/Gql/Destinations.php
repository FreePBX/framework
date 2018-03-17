<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

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
			return (is_array($value)) ? $value[$info->fieldName] : $destinations[$value][$info->fieldName];
		});
	}

	private function getDestinations() {
		if(empty($this->destinations)) {
			$this->destinations = $this->freepbx->Modules->getDestinations();
		}
		return $this->destinations;
	}
}
