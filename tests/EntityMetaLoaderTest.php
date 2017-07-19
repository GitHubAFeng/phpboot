<?php

namespace PhpBoot\Tests;


use PhpBoot\Annotation\Entity\EntityMetaLoader;
use PhpBoot\Entity\ArrayContainer;
use PhpBoot\Entity\EntityContainer;
use PhpBoot\Entity\ScalarTypeContainer;
use PhpBoot\Metas\PropertyMeta;

/**
 * Class LoadTest
 */
class LoadTest{

    /**
     * property1
     * @var string[]
     */
    public $property1;

    /**
     * @var string
     * @v email
     */
    public $property2='default0';

    public $property3=1;
}


class ScalarTypeTest{
    /**
     * @var int
     * @v max:101
     */
    public $property1;
}

class ScalarTypeArrayTest{
    /**
     * @var int[]
     * @v max:101 *
     */
    public $property1;
}

class EntityTest{
    /**
     * @var ScalarTypeTest
     */
    public $property1;
}


class EntityArrayTest{
    /**
     * @var ScalarTypeTest[]
     */
    public $property1;
}

class EntityMetaLoaderTest extends TestCase
{

    public function testLoad()
    {
        $loader = new EntityMetaLoader();
        $actual = $loader->loadFromClass(LoadTest::class);

        $expected = new EntityContainer(LoadTest::class);
        $expected->setSummary('Class LoadTest');
        $expected->setFileName(__FILE__);
        $expected->setProperty('property1', new PropertyMeta('property1', 'string[]', false,null,null,'property1', '', new ArrayContainer(new ScalarTypeContainer('string'))));
        $expected->setProperty('property2', new PropertyMeta('property2', 'string', true,'default0', 'email', '', '', new ScalarTypeContainer('string')));
        $expected->setProperty('property3', new PropertyMeta('property3', null, true,1, null, '', ''));
        self::assertEquals($expected, $actual);
    }

    public function testMakeScalarType()
    {
        $loader = new EntityMetaLoader();
        $container = $loader->loadFromClass(ScalarTypeTest::class);
        $actual = $container->make(['property1'=>100]);
        $expected = new ScalarTypeTest();
        $expected->property1 = 100;
        self::assertEquals($expected, $actual);

        self::assertException(function ()use($container){
            $container->make(['property1'=>'not string']);
        }, \InvalidArgumentException::class);

        self::assertException(function ()use($container){
            $container->make(['property1'=>102]);
        }, \InvalidArgumentException::class);
    }

    public function testMakeScalarTypeArray()
    {
        $loader = new EntityMetaLoader();
        $container = $loader->loadFromClass(ScalarTypeArrayTest::class);
        $actual = $container->make(['property1'=>[100]]);
        $expected = new ScalarTypeArrayTest();
        $expected->property1 = [100];
        self::assertEquals($expected, $actual);

        self::assertException(function ()use($container){
            $container->make(['property1'=>'not string']);
        }, \InvalidArgumentException::class);

        self::assertException(function ()use($container){
            $container->make(['property1'=>[102]]);
        }, \InvalidArgumentException::class);
    }

    public function testMakeEntity()
    {
        $loader = new EntityMetaLoader();
        $container = $loader->loadFromClass(EntityTest::class);
        $actual = $container->make([
            'property1'=>[
                'property1'=>100
            ]
        ]);

        $expected = new EntityTest();
        $expected->property1 = new ScalarTypeTest();
        $expected->property1->property1 = 100;
        self::assertEquals($expected, $actual);

        self::assertException(function ()use($container){
            $container->make(['property1'=>'not string']);
        }, \InvalidArgumentException::class);

        self::assertException(function ()use($container){
            $container->make([
                'property1'=>[
                    'property1'=>102
                ]
            ]);
        }, \InvalidArgumentException::class);
    }

    public function testMakeEntityArray()
    {
        $loader = new EntityMetaLoader();
        $container = $loader->loadFromClass(EntityArrayTest::class);
        $actual = $container->make([
            'property1'=>[
                ['property1'=>100]
            ]
        ]);

        $expected = new EntityArrayTest();
        $property1 = new ScalarTypeTest();
        $property1->property1 = 100;
        $expected->property1 =[$property1];

        self::assertEquals($expected, $actual);

        self::assertException(function ()use($container){
            $container->make(['property1'=>'not string']);
        }, \InvalidArgumentException::class);

        self::assertException(function ()use($container){
            $container->make([
                'property1'=>[
                    ['property1'=>102]
                ]
            ]);
        }, \InvalidArgumentException::class);
    }
}