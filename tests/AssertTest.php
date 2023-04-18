<?php

declare(strict_types=1);

namespace Formapro\Values\Tests;

use Formapro\Values\Assert;
use PHPUnit\Framework\TestCase;

class AssertTest extends TestCase
{
    public function testThrowIfPropertyNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected the property stdClass::foo to exist');
        Assert::propertyExists(new \stdClass(), 'foo');
    }

    public function testDoNothingIfPropertyExists()
    {
        $obj = new \stdClass();
        $obj->foo = null;

        Assert::propertyExists($obj, 'foo');

        $this->assertTrue(true);
    }

    public function testThrowIfNotArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array. Got string');
        Assert::isArray('foo');
    }

    public function testDoNothingIfValueIsArray()
    {
        $obj = new \stdClass();
        $obj->foo = null;

        Assert::isArray([]);

        $this->assertTrue(true);
    }

    public function testThrowIfNotScalar()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array or scalar. Got stdClass');
        Assert::scalarOrArrayOfScalars(new \stdClass());
    }

    public function testThrowIfSubElementNotScalar()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array or scalar. Got stdClass at path foo.bar');
        Assert::scalarOrArrayOfScalars(['foo' => ['bar' => new \stdClass()]]);
    }

    public function testDoNothingIfScalarValue()
    {
        Assert::scalarOrArrayOfScalars('foo');

        $this->assertTrue(true);
    }

    public function testDoNothingIfArrayOfScalarValues()
    {
        Assert::scalarOrArrayOfScalars(['foo', 1, 1.2, false, null, ['bar']]);

        $this->assertTrue(true);
    }
}