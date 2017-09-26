<?php

namespace FreePBX\Gqlapi;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Gqlapi\includes\Base;

class Helloworld extends Base {
	public function constructQuery() {
		return [
			'version' => [
				'type' => Type::string(),
				'resolve' => function ($root, $args) {
					return getVersion();
				}
			]
		];
	}
}
