<?php

/** @var RestApi $restApi */

use Core\Rest\RestApi;

$restApi = Core\Container\Container::getInstance()->get(RestApi::class);
$restApi->init();
