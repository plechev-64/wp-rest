<?php

namespace Core\Rest;

use Core\Attributes\Param;
use Core\Attributes\Route;
use ReflectionClass;
use ReflectionMethod;

class ReflectionRouteParser
{

    public function __construct(public RouteCollector $collector)
    {
    }

    /**
     * @throws \ReflectionException
     */
    public function parse(string $routeClass): void
    {
        $classReflection = new ReflectionClass($routeClass);

        $classRoute = $classReflection->getAttributes(Route::class)[0]->getArguments()['path'];

        $this->collector->addGroup($classRoute, function (RouteCollector $collector) use ($classReflection) {

            $methods = $classReflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
	            $methodParameters = $method->getParameters();

	            $dependencies = [];
	            foreach ( $methodParameters as $parameter ) {

//		            if ( $parameter->isOptional() ) {
//			            $dependencies[] = $parameter->getDefaultValue()->getName();
//			            continue;
//		            }

		            //todo если тип данных параметра не указан - будет Null и фатал
		            $dependencies[] = [
			            'type' => $parameter->getType()->getName(),
			            'name' => $parameter->getName(),
		            ];

	            }

				$routeAttrs = $method->getAttributes(Route::class);
	            $routePath = $routeAttrs[0]->getArguments()['path'];
	            $routeMethod = $routeAttrs[0]->getArguments()['method'];

                $methodArguments = array_map(fn($p) => $p->getArguments(), $method->getAttributes(Param::class));

                $methodAction = 'POST';
                if ($routeMethod) {
                    $methodAction = $routeMethod;
                }

                $routeData = new RouteData(
                    $classReflection->getName(),
                    $method->getName(),
                    $methodAction
                );

                if ($methodArguments) {
                    $routeData->setParams($methodArguments);
                }

	            if ($dependencies) {
		            $routeData->setDependencies($dependencies);
	            }

                $collector->addRoute($routePath, $routeData);
            }
        });
    }

    public function getCollector(): RouteCollector
    {
        return $this->collector;
    }
}
