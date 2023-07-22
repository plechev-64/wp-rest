<?php

namespace Core\Rest;

use WP_Error;

class ControllerAbstract {

	protected function error($text, ?int $status = 500): WP_Error {
		return new WP_Error( 'don-rest-api', $text, array( 'status' => $status ) );
	}

}
