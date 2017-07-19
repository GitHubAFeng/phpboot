<?php

namespace PhpBoot\Annotation\controller\Annotations;

use PhpBoot\Annotation\AnnotationBlock;
use PhpBoot\Annotation\AnnotationTag;
use PhpBoot\Annotation\Controller\ControllerAnnotationHandler;
use PhpBoot\Annotation\Entity\EntityMetaLoader;
use PhpBoot\Entity\ArrayContainer;
use PhpBoot\Entity\ContainerFactory;
use PhpBoot\Entity\MixedTypeContainer;
use PhpBoot\Entity\ScalarTypeContainer;
use PhpBoot\Exceptions\AnnotationSyntaxException;
use PhpBoot\Utils\AnnotationParams;
use PhpBoot\Utils\Logger;
use PhpBoot\Utils\TypeHint;

class ParamAnnotationHandler extends ControllerAnnotationHandler
{

    static public function getParamInfo($text)
    {

        $paramType = null;
        $paramName = null;
        $paramDoc = '';
        if(substr($text, 0, 1) == '$'){ //带$前缀的是变量
            $params = new AnnotationParams($text, 2);
            $paramName = substr($params->getParam(0), 1);
            $paramDoc = $params->getRawParam(1, '');
        }else{
            $params = new AnnotationParams($text, 3);
            if ($params->count() >=2 && substr($params->getParam(1), 0, 1) == '$'){
                $paramType = $params->getParam(0); //TODO 检测类型是否合法
                $paramName = substr($params->getParam(1), 1);
                $paramDoc = $params->getRawParam(2, '');
            }else{
                fail(new AnnotationSyntaxException("@param $text syntax error"));
            }
        }
        return [$paramType, $paramName, $paramDoc];
    }
    /**
     * @param AnnotationBlock|AnnotationTag $ann
     * @return void
     */
    public function handle($ann)
    {
        if(!$ann->parent){
            Logger::debug("The annotation \"@{$ann->name} {$ann->description}\" of {$this->container->getClassName()} should be used with parent route");
            return;
        }
        $target = $ann->parent->name;
        $route = $this->container->getRoute($target);
        if(!$route){
            Logger::debug("The annotation \"@{$ann->name} {$ann->description}\" of {$this->container->getClassName()}::$target should be used with parent route");
            return ;
        }
        $className = $this->container->getClassName();

        list($paramType, $paramName, $paramDoc) = self::getParamInfo($ann->description);

        $paramMeta = $route->getRequestHandler()->getParamMeta($paramName);
        $paramMeta or fail(new AnnotationSyntaxException("$className::$target param $paramName not exist "));
        //TODO 检测声明的类型和注释的类型是否匹配
        if($paramType){
            $paramMeta->type = TypeHint::normalize($paramType, $className);//or fail(new AnnotationSyntaxException("{$this->container->getClassName()}::{$ann->parent->name} @{$ann->name} syntax error, param $paramName unknown type:$paramType "));
            $container = ContainerFactory::create($paramMeta->type);
            $paramMeta->container = $container;
        }
        $paramMeta->description = $paramDoc;
    }
}