<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Destinations extends Base {
	private $destinations;

	protected static $priority = 100;

	public function initTypes() {
		$user = $this->typeContainer->create('destination','union');
		$user->setDescription("Destination for a call to hit");
		$user->addResolve(function($value, $context, $info) {
			if(!empty($value['gqltype'])) {
				return $this->typeContainer->get($value['gqltype'])->getObject();
			}
			return null;
		});

		$user = $this->typeContainer->create('invaliddestination');
		$user->setDescription('Invalid Destination Holder');
		$user->addFieldCallback(function() {
			return [
				'id' => [
					'type' => Type::id(),
					'description' => 'The invalid destination id'
				],
				'description' => [
					'type' => Type::string(),
					'description' => 'The invalid destination description',
					'resolve' => function($row) {
						return 'Invalid Destination';
					},
				],
			];
		});
	}

	public function postInitTypes() {
		$destinations = $this->typeContainer->get('destination');
		$destinations->addType($this->typeContainer->get('invaliddestination')->getReference());
	}

	private function getDestinations() {
		if(empty($this->destinations)) {
			$this->destinations = $this->freepbx->Modules->getDestinations();
		}
		return $this->destinations;
	}
}
