<?php

namespace Core\Attributes;

use Attribute;

#[\Attribute( Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)] class Param {
	public string $name;
	public ?string $entity = null;
	public ?string $model = null;
	public ?string $type = null;

	public function __construct(
		string $name,
		?bool $type = null,
		?string $entity = null,
		?string $model = null,
	) {
		$this->name    = $name;
		$this->entity = $entity;
		$this->type = $type;
		$this->model = $model;
	}

}
