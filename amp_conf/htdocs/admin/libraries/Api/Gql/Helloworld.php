<?php

namespace FreePBX\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

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
