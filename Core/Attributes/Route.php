<?php

namespace Core\Attributes;

#[\Attribute] class Route {
	public string $path;
	public ?string $method = null;

	/**
	 * @param string $path
	 * @param string|null $method
	 */
	public function __construct( string $path, ?string $method = 'POST' ) {
		$this->path   = $path;
		$this->method = $method;
	}

}
