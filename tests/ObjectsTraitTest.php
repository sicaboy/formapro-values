<?php
namespace Formapro\Values\Tests;

use function Formapro\Values\add_object;
use function Formapro\Values\clone_object;
use function Formapro\Values\get_object;
use function Formapro\Values\get_objects;
use function Formapro\Values\get_values;
use Formapro\Values\HooksEnum;
use Formapro\Values\HookStorage;
use function Formapro\Values\register_hook;
use function Formapro\Values\register_object_hooks;
use function Formapro\Values\set_object;
use function Formapro\Values\set_objects;
use function Formapro\Values\set_values;
use Formapro\Values\Tests\Model\EmptyObject;
use Formapro\Values\Tests\Model\InvalidObject;
use Formapro\Values\Tests\Model\ObjectInterface;
use Formapro\Values\Tests\Model\OtherSubObject;
use Formapro\Values\Tests\Model\SubObject;
use PHPUnit\Framework\TestCase;

class ObjectsTraitTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        HookStorage::clearAll();

        register_object_hooks();
    }

    public function tearDown()
    {
        parent::tearDown();

        HookStorage::clearAll();
    }

    public function testShouldResetObjectIfValuesSetAgain()
    {
        $subObj = new SubObject();
        $subObj->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        self::assertAttributeNotEmpty('values', $obj);
        self::assertAttributeNotEmpty('objects', $obj);

        $values = [];
        set_values($obj, $values);

        self::assertAttributeEmpty('values', $obj);
        self::assertAttributeEmpty('objects', $obj);
    }

    public function testShouldAllowGetPreviouslySetObject()
    {
        $subObj = new SubObject();
        $subObj->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        self::assertSame($subObj, $obj->getObject('aName.aKey', SubObject::class));

        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));
    }

    public function testShouldCreateObjectOnGet()
    {
        $obj = new EmptyObject();

        $values = ['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]];
        set_values($obj, $values);

        $subObj = $obj->getObject('aName.aKey', SubObject::class);
        self::assertInstanceOf(SubObject::class, $subObj);

        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));
    }

    public function testShouldReturnNullIfValueNotSet()
    {
        $obj = new EmptyObject();

        self::assertNull($obj->getObject('aName.aKey', SubObject::class));
    }

    public function testShouldChangesInSubObjReflectedInObjValues()
    {
        $subObj = new SubObject();
        $subObj->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));

        $subObj->setValue('aSubName.aSubKey', 'aBarVal');

        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObj));
        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aBarVal']]]], get_values($obj));
    }

    public function testShouldChangesInSubSubObjReflectedInObjValues()
    {
        $subSubObj = new SubObject();
        $subSubObj->setValue('aSubSubName.aSubSubKey', 'aFooVal');

        $subObj = new EmptyObject();
        $subObj->setObject('aSubName.aSubKey', $subSubObj);

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        self::assertSame(['aName' => ['aKey' => [
            'aSubName' => [
                'aSubKey' => ['aSubSubName' => ['aSubSubKey' => 'aFooVal']],
            ], ]]], get_values($obj));
        self::assertSame(['aSubSubName' => ['aSubSubKey' => 'aFooVal']], get_values($subSubObj));

        $subSubObj->setValue('aSubSubName.aSubSubKey', 'aBarVal');

        self::assertSame(['aName' => ['aKey' => [
            'aSubName' => [
                'aSubKey' => ['aSubSubName' => ['aSubSubKey' => 'aBarVal']],
            ], ]]], get_values($obj));
        self::assertSame(['aSubSubName' => ['aSubSubKey' => 'aBarVal']], get_values($subSubObj));
    }

    public function testShouldNotChangesInSubObjReflectedInObjValuesIfUnset()
    {
        $subObj = new SubObject();
        $subObj->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));

        $obj->setObject('aName.aKey', null);

        self::assertSame(['aName' => []], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));

        $subObj->setValue('aSubName.aSubKey', 'aBarVal');
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObj));
    }

    public function testShouldUnsetSubObjIfSameValueChangedAfterSubObjSet()
    {
        $subObj = new SubObject();
        $subObj->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        self::assertAttributeSame(['aName' => ['aKey' => $subObj]], 'objects', $obj);

        $obj->setValue('aName.aKey', 'aFooVal');

        self::assertAttributeEquals(['aName' => []], 'objects', $obj);
    }

    public function testShouldAllowDefineClosureAsClass()
    {
        $subObjValues = ['aSubName' => ['aSubKey' => 'aFooVal']];

        $expectedSubClass = $this->getMockClass(SubObject::class);

        $obj = new EmptyObject();

        $values = ['aName' => ['aKey' => $subObjValues]];
        set_values($obj, $values);

        $subObj = $obj->getObject('aName.aKey', function ($actualSubObjValues) use ($subObjValues, $expectedSubClass) {
            self::assertSame($subObjValues, $actualSubObjValues);

            return $expectedSubClass;
        });

        self::assertInstanceOf($expectedSubClass, $subObj);
    }

    public function testShouldAllowGetPreviouslySetObjects()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->setObjects('aName.aKey', [$subObjFoo, $subObjBar]);

        $objs = $obj->getObjects('aName.aKey', SubObject::class);
        self::assertInstanceOf(\Traversable::class, $objs);

        self::assertSame([$subObjFoo, $subObjBar], iterator_to_array($objs));

        self::assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));
    }

    public function testShouldCreateObjectsOnGet()
    {
        $values = ['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]];

        $obj = new EmptyObject();
        set_values($obj, $values);

        $subObjs = $obj->getObjects('aName.aKey', SubObject::class);
        $subObjs = iterator_to_array($subObjs);

        self::assertCount(2, $subObjs);
        self::assertContainsOnlyInstancesOf(SubObject::class, $subObjs);

        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjs[0]));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjs[1]));
    }

    public function testThrowIfNotArrayInCollection()
    {
        $values = ['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            null,
            ['aSubName' => ['aSubKey' => 'aFooVal']],
        ]]];

        $obj = new EmptyObject();
        set_values($obj, $values);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The object on path "aName.aKey.1" could not be built. The path value is null.');

        $subObjs = get_objects($obj, 'aName.aKey', SubObject::class);
        iterator_to_array($subObjs);
    }

    public function testShouldAllowAddObjectToCollection()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->addObject('aName.aKey', $subObjFoo);
        $obj->addObject('aName.aKey', $subObjBar);

        $objs = $obj->getObjects('aName.aKey', SubObject::class);
        $objs = iterator_to_array($objs);

        self::assertSame([$subObjFoo, $subObjBar], $objs);

        self::assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));

        self::assertAttributeSame(['aName' => ['aKey' => [$subObjFoo, $subObjBar]]], 'objects', $obj);

        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));
    }

    public function testShouldAllowGetObjectsEitherSetAsValuesAndAddObject()
    {
        $values = ['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
        ]]];

        $obj = new EmptyObject();
        set_values($obj, $values);

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj->addObject('aName.aKey', $subObjBar);

        $subObjs = $obj->getObjects('aName.aKey', SubObject::class);
        self::assertInstanceOf(\Traversable::class, $subObjs);

        $subObjs = iterator_to_array($subObjs);

        self::assertCount(2, $subObjs);

        self::assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));

        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjs[0]));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjs[1]));
    }

    public function testShouldAllowUnsetObjects()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->setObjects('aName.aKey', [$subObjFoo, $subObjBar]);

        self::assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));

        self::assertAttributeSame(['aName' => ['aKey' => [$subObjFoo, $subObjBar]]], 'objects', $obj);

        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));

        $obj->setObjects('aName.aKey', null);

        self::assertSame(['aName' => []], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));
    }

    public function testShouldAllowResetObjects()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->setObjects('aName.aKey', [$subObjFoo, $subObjBar]);

        self::assertSame(['aName' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));

        self::assertAttributeSame(['aName' => ['aKey' => [$subObjFoo, $subObjBar]]], 'objects', $obj);

        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));

        $obj->setObjects('aName.aKey', []);

        self::assertAttributeSame(['aName' => []], 'objects', $obj);

        self::assertSame(['aName' => ['aKey' => []]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));
    }

    public function testShouldNotReflectChangesIfObjectWasCloned()
    {
        $values = [
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aFooVal'],
                ],
            ],
        ];

        $obj = new EmptyObject();
        set_values($obj, $values);

        /** @var SubObject $subObj */
        $subObj = $obj->getObject('aName.aKey', SubObject::class);

        //guard
        self::assertInstanceOf(SubObject::class, $subObj);

        $clonedSubObj = clone_object($subObj);
        $clonedSubObj->setValue('self.aSubKeyFoo', 'aBarVal');

        self::assertEquals([
            'aName' => [
                'aKey' => [
                    'aSubName' => ['aSubKey' => 'aFooVal'],
                ],
            ],
        ], get_values($obj));
    }

    public function testShouldAllowSetSelfObjectAndGetPreviouslySet()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('self.aKey', $subObjFoo);

        self::assertSame($subObjFoo, $obj->getObject('self.aKey', EmptyObject::class));
        self::assertSame(['self' => ['aKey' =>
            ['aSubName' => ['aSubKey' => 'aFooVal']],
        ]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
    }

    public function testShouldAllowSetSelfObjectsAndGetPreviouslySet()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->setObjects('self.aKey', [$subObjFoo, $subObjBar]);

        $objs = $obj->getObjects('self.aKey', SubObject::class);
        $objs = iterator_to_array($objs);

        self::assertSame([$subObjFoo, $subObjBar], $objs);

        self::assertSame(['self' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));
    }

    public function testShouldAllowAddSelfObjectsAndGetPreviouslySet()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubName.aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubName.aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->addObject('self.aKey', $subObjFoo);
        $obj->addObject('self.aKey', $subObjBar);

        $objs = $obj->getObjects('self.aKey', SubObject::class);
        $objs = iterator_to_array($objs);

        self::assertSame([$subObjFoo, $subObjBar], $objs);

        self::assertSame(['self' => ['aKey' => [
            ['aSubName' => ['aSubKey' => 'aFooVal']],
            ['aSubName' => ['aSubKey' => 'aBarVal']],
        ]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObjFoo));
        self::assertSame(['aSubName' => ['aSubKey' => 'aBarVal']], get_values($subObjBar));
    }

    public function testReplacePreviouslySetObjectAndUnReferenceIt()
    {
        $subObjFoo = new SubObject();
        $subObjFoo->setValue('aSubKey', 'aFooVal');

        $subObjBar = new SubObject();
        $subObjBar->setValue('aSubKey', 'aBarVal');

        $obj = new EmptyObject();
        $obj->setObject('aKey', $subObjFoo);

        $obj->setObject('aKey', $subObjBar);

        self::assertSame(['aKey' => ['aSubKey' => 'aBarVal']], get_values($obj));
        self::assertSame(['aSubKey' => 'aFooVal'], get_values($subObjFoo));
        self::assertSame(['aSubKey' => 'aBarVal'], get_values($subObjBar));
    }

    public function testThrowIfGetObjectWithoutClassOrClosureAndHook()
    {
        $values = [
            'aKey' => [
                'aSubName' => ['aSubKey' => 'aFooVal'],
            ],
        ];

        $obj = new EmptyObject();
        set_values($obj, $values);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either class or closure has to be passed explicitly or there must be a hook that provide an object class.');
        get_object($obj, 'aKey');
    }

    public function testShouldBuildObjectFromClassProvidedByHook()
    {
        $values = [
            'aKey' => [
                'aSubName' => ['aSubKey' => 'aFooVal'],
            ],
        ];

        $obj = new EmptyObject();
        set_values($obj, $values);

        register_hook(HooksEnum::BUILD_OBJECT, HooksEnum::GET_OBJECT_CLASS, function($object, $key, $values) {
            return SubObject::class;
        });

        $subObj = get_object($obj, 'aKey');

        $this->assertInstanceOf(SubObject::class, $subObj);
    }

    public function testClassProvidedByHookShouldTakePriorityOverClassAsArgument()
    {
        $values = [
            'aKey' => [
                'aSubName' => ['aSubKey' => 'aFooVal'],
            ],
        ];

        $argumentClass = SubObject::class;
        $hookClass = OtherSubObject::class;

        $obj = new EmptyObject();
        set_values($obj, $values);

        register_hook(HooksEnum::BUILD_OBJECT, HooksEnum::GET_OBJECT_CLASS, function($object, $key, $values) use ($hookClass) {
            return $hookClass;
        });

        $subObj = get_object($obj, 'aKey', $argumentClass);

        $this->assertInstanceOf($hookClass, $subObj);
    }

    public function testShouldNotChangeObjectValuesIfGetValuesCopiedTrue()
    {
        $subObj = new SubObject();
        $subObj->setValue('aSubName.aSubKey', 'aFooVal');

        $obj = new EmptyObject();
        $obj->setObject('aName.aKey', $subObj);

        $values = get_values($obj); // copy must be true by default

        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));

        $values['aName']['aKey']['aSubName']['aSubKey'] = 'aBarVal';

        self::assertSame(['aName' => ['aKey' => ['aSubName' => ['aSubKey' => 'aFooVal']]]], get_values($obj));
        self::assertSame(['aSubName' => ['aSubKey' => 'aFooVal']], get_values($subObj));
    }

    public function testClassProvidedByHookBasedOnInterface()
    {
        $values = [
            'aKey' => [
                'aSubName' => ['aSubKey' => 'aFooVal'],
            ],
        ];

        $obj = new EmptyObject();
        set_values($obj, $values);

        register_hook(HooksEnum::BUILD_OBJECT, HooksEnum::GET_OBJECT_CLASS, function($values, $context, $contextKey, $classOrCallable) {
            $this->assertSame(ObjectInterface::class, $classOrCallable);

            return SubObject::class;
        });

        $subObj = get_object($obj, 'aKey', ObjectInterface::class);

        $this->assertInstanceOf(SubObject::class, $subObj);
    }

    public function testClosureReturnsObject()
    {
        $values = [
            'aKey' => [
                'aSubName' => ['aSubKey' => 'aFooVal'],
            ],
        ];

        $expectedObj = new EmptyObject();

        $obj = new EmptyObject();
        set_values($obj, $values);

        $actualObj = get_object($obj, 'aKey', function() use ($expectedObj) {
            return $expectedObj;
        });

        $this->assertSame($expectedObj, $actualObj);

        $objects = $this->readAttribute($obj, 'objects');
        $this->assertSame($expectedObj, $objects['aKey']);
    }

    public function testThrowsOnSetObjectIfNotInit()
    {
        $obj = new InvalidObject();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected the property Formapro\Values\Tests\Model\InvalidObject::values to exist');

        set_object($obj, 'foo', new \stdClass());
    }

    public function testThrowsOnGetObjectIfNotInit()
    {
        $obj = new InvalidObject();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected the property Formapro\Values\Tests\Model\InvalidObject::values to exist');

        get_object($obj, 'foo', new \stdClass());
    }

    public function testThrowsOnAddObjectIfNotInit()
    {
        $obj = new InvalidObject();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected the property Formapro\Values\Tests\Model\InvalidObject::values to exist');

        add_object($obj, 'foo', new \stdClass());
    }

    public function testThrowsOnSetObjectsIfNotInit()
    {
        $obj = new InvalidObject();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected the property Formapro\Values\Tests\Model\InvalidObject::values to exist');

        set_objects($obj, 'foo', []);
    }
}