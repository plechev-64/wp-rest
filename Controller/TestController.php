<?php

namespace Controller;

use Common\DTO\Object\Model\InpitGirlModel;
use Common\Entity\Girl\Girl;
use Common\Entity\Post\Post;
use Common\Service\GirlService;
use Core\Attributes\Param;
use Core\Attributes\Route;
use Core\Rest\ControllerAbstract;

#[Route(path: '/test')]
class TestController extends ControllerAbstract {

	#[Route(path: '/string', method: 'GET')]
	#[Param(name: 'str', type: 'string')]
	#[Param(name: 'girl', entity: Girl::class)]
	#[Param(name: 'model', model: InpitGirlModel::class)]
	public function getGirl(
		InpitGirlModel $model,
		string $str,
		Girl $girl,
		GirlService $service
	){
		var_dump([$model, $str, $girl->getId(), $service]);
	}

	#[Route(path: '/post')]
	#[Param(name: 'post', entity: Post::class)]
	public function getPost(Post $post){
		print_r($post);
	}

}
