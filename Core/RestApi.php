<?php

namespace Core\Rest;

use Controller\TestController;
use Core\Container\Container;
use Core\Exception\NotFoundEntityException;
use Core\Exception\NotFoundRequiredParamException;
use Core\Exception\NotFoundServiceException;
use Core\ORM;
use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use WP_Error;

class RestApi {

	const NAMESPACE = 'don/v2';
	private const SIMPLE_PARAM_TYPE_INTEGER = 'int';
	private const SIMPLE_PARAM_TYPE_STRING = 'string';
	private const SIMPLE_PARAM_TYPE_BOOLEAN = 'bool';
	private const SIMPLE_PARAM_TYPE_ARRAY = 'array';
	private const SIMPLE_PARAM_TYPES = [
		self::SIMPLE_PARAM_TYPE_INTEGER,
		self::SIMPLE_PARAM_TYPE_STRING,
		self::SIMPLE_PARAM_TYPE_BOOLEAN,
		self::SIMPLE_PARAM_TYPE_ARRAY,
	];

	public const CONTROLLERS = [
		TestController::class
	];

	private Container $container;
	private ReflectionRouteParser $reflectionRouteParser;

	/**
	 * @param Container $container
	 * @param ReflectionRouteParser $reflectionRouteParser
	 */
	public function __construct( Container $container, ReflectionRouteParser $reflectionRouteParser ) {
		$this->container             = $container;
		$this->reflectionRouteParser = $reflectionRouteParser;
	}


	public function init(): void {

		foreach ( self::CONTROLLERS as $CONTROLLER ) {
			$this->reflectionRouteParser->parse( $CONTROLLER );
		}

		add_action( 'rest_api_init', [ $this, 'initRoutes' ] );

	}

	public function initRoutes() {
		foreach ( $this->reflectionRouteParser->getCollector()->all() as $route ) {
			$this->initRoute( $route );
		}

	}

	private function initRoute( RouteData $routeData ): void {

		$args = [
			'methods'  => $routeData->getActionMethod(),
			'callback' => $this->routeCallback( $routeData ),
			//'permission_callback' => fn(\WP_REST_Request $r) => true
		];

		$argsParams = [
			'methods' => $routeData->getActionMethod()
		];

//		$routeParams = $routeData->getParams();
//
//		if ($routeParams) {
//			foreach ($routeParams as $routeParam) {
//
//				$data = $this->buildRouteParamData($routeParam);
//
//				$argsParams[$routeParam->getName()] = $data;
//			}
//		}

		if ( $argsParams ) {
			$args['args'] = $argsParams;
		}

		register_rest_route( self::NAMESPACE, $routeData->getPath(), $args );

	}

	/**
	 * Метод возвращает функцию которая вызывает обработчик эндпоинта
	 *
	 * @param RouteData $routeData
	 *
	 * @return \Closure
	 */
	public function routeCallback( RouteData $routeData ): \Closure {
		return function ( \WP_REST_Request $request ) use ( $routeData ) {

			$controller = new ( $routeData->getController() );
			$method     = $routeData->getMethod();

			$depends = [];
			if ( $routeData->getDependencies() ) {

				try {
					foreach ( $routeData->getDependencies() as $dependency ) {

						$dependencyName = $dependency['name'];
						$dependencyType = $dependency['type'];

						$routeParam = $this->findRouteParam( $routeData, $dependencyName );

						if ( $routeParam ) {
							$requestParamValue = $request->get_param( $dependencyName );

							if ( $requestParamValue === null ) {
								throw new NotFoundRequiredParamException( sprintf( 'Не передан обязательный параметр - %s', $dependencyName ) );
							} else if ( ! empty( $routeParam['type'] ) ) {
								$depends[ $dependencyName ] = $this->validateSimpleParamValue( $requestParamValue, $routeParam['type'] );
							} else if ( ! empty( $routeParam['entity'] ) ) {
								if ( $entity = ORM::get()->getRepository( $routeParam['entity'] )->find( (int) $requestParamValue ) ) {
									$depends[ $dependencyName ] = $entity;
								} else {
									throw new NotFoundEntityException( sprintf( 'Не найдена сущность - %s', $routeParam['entity'] ) );
								}
							} else if ( ! empty( $routeParam['model'] ) ) {
								try {
									$model = new $routeParam['model']();
									$classReflection = new ReflectionClass($model);
									foreach($requestParamValue as $prop => $value){
										$propReflection = $classReflection->getProperty($prop);
										$type = $propReflection->getType()->getName();
										$model->$prop = $this->validateSimpleParamValue($value, $type);
									}
								} catch (\Exception $e) {
									throw new NotFoundServiceException( sprintf( 'Не удалось собрать модель - %s', $routeParam['model'] ) );
								}
								$depends[ $dependencyName ] = $model;
							}

						} else if ( in_array( $dependencyType, self::SIMPLE_PARAM_TYPES ) ) {
							throw new NotFoundRequiredParamException( sprintf( 'Не передан обязательный параметр - %s', $dependencyName ) );
						} else {
							try {
								$depends[ $dependencyName ] = $this->container->get( $dependencyType );
							}catch (\Exception $e){
								throw new NotFoundServiceException( sprintf( 'Не удалось получить сервис - %s', $dependencyType ) );
							}

						}

					}
				} catch ( \Exception $e ) {
					return new WP_Error( 'don-rest-api', $e->getMessage(), array( 'status' => 500 ) );
				}
			}

			return $controller->$method( ...$depends );
		};
	}

	private function validateSimpleParamValue( mixed $value, string $type ): string|int|bool {
		return match ( $type ) {
			self::SIMPLE_PARAM_TYPE_INTEGER => (int) $value,
			self::SIMPLE_PARAM_TYPE_STRING => (string) $value,
			self::SIMPLE_PARAM_TYPE_BOOLEAN => (bool) $value,
			self::SIMPLE_PARAM_TYPE_ARRAY => (array) $value,
		};

	}

	#[Pure] private function findRouteParam( RouteData $routeData, string $name ): ?array {
		foreach ( $routeData->getParams() as $routeParam ) {
			if ( $routeParam['name'] === $name ) {
				return $routeParam;
			}
		}

		return null;
	}

}
