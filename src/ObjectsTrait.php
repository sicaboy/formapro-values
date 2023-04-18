<?php
namespace Formapro\Values;

/**
 * @property array $values
 * @property array $changedValues
 */
trait ObjectsTrait
{
    /**
     * @var array
     */
    protected $objects = [];

    /**
     * @var object|null
     */
    protected $rootObject;

    /**
     * @var string|null
     */
    protected $rootObjectKey;

    /**
     * @param string|\Closure|null $classOrClosure
     */
    protected function getObject(string $key, $classOrClosure = null): ?object
    {
        return get_object($this, $key, $classOrClosure);
    }

    protected function setObject(string $key, ?object $object): void
    {
        set_object($this, $key, $object);
    }

    /**
     * @param object[]|array $objects
     */
    protected function setObjects(string $key, ?array $objects): void
    {
        set_objects($this, $key, $objects);
    }

    protected function addObject(string $key, object $object, ?string $objectKey = null): void
    {
        add_object($this, $key, $object, $objectKey);
    }

    /**
     * @param string|\Closure|null $classOrClosure
     *
     * @return object[]|\Traversable
     */
    protected function getObjects(string $key, $classOrClosure = null): \Traversable
    {
        return get_objects($this, $key, $classOrClosure);
    }
}