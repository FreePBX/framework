<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Destinations extends Base {
	private $destinations;
	public function initReferences() {
		$user = $this->typeContainer->get('destination');
		$user->addFields([
			'destination' => [
				'type' => Type::id()
			],
			'description' => [
				'type' => Type::string()
			],
			'category' => [
				'type' => Type::string()
			],
			'module' => [
				'type' => Type::string()
			],
			'name' => [
				'type' => Type::string()
			],
			'edit_url' => [
				'type' => Type::string()
			]
		]);
		$user->addResolve(function($value, $args, $context, $info) {
			$destinations = $this->getDestinations();
			if(is_array($value) && !empty($value[$info->fieldName])) {
				return $value[$info->fieldName];
			} elseif(!empty($destinations[$value][$info->fieldName])) {
				return $destinations[$value][$info->fieldName];
			}
			return null;
		});
	}

	private function getDestinations() {
		if(empty($this->destinations)) {
			$this->destinations = $this->freepbx->Modules->getDestinations();
		}
		return $this->destinations;
	}
}
