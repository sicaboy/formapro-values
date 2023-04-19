<?php
namespace Formapro\Values;

function set_values(object $object, array &$values, bool $byReference = false): object
{
    $func = (function (array &$values, $byReference) {
        Assert::propertyExists($this, 'values');

        if ($byReference) {
            $this->values = &$values;
        } else {
            $this->values = $values;
        }

        foreach (get_registered_hooks($this, HooksEnum::POST_SET_VALUES) as $callback) {
            call_user_func($callback, $this, $values, $byReference);
        }

        return $this;
    })->bindTo($object, $object);

    return $func($values, $byReference);
}

function get_values(object $object, bool $copy = true): array
{
    $values = (function () {
        Assert::propertyExists($this, 'values');
        Assert::isArray($this->values);

        return $this->values;
    })->call($object);

    return $copy ? array_copy($values) : $values;
}

function add_value(object $object, string $key, $value, ?string $valueKey = null)
{
    if ($value instanceof \DateTimeZone || $value instanceof \DateTime || $value instanceof \DateInterval) {
        @trigger_error('Calling add_value with date objects is deprecated. Use cast classes.', E_USER_DEPRECATED);
    }

    return (function($key, $value, $valueKey) {
        foreach (get_registered_hooks($this, HooksEnum::PRE_ADD_VALUE) as $callback) {
            if (null !== $changedValue = call_user_func($callback, $this, $key, $value)) {
                $value = $changedValue;
            }
        }

        Assert::propertyExists($this, 'values');
        Assert::isArray($this->values);

        $newValue = array_get($key, [], $this->values);
        if (false == is_array($newValue)) {
            throw new \LogicException(sprintf('Cannot set value to %s it is already set and not array', $key));
        }

        if (null === $valueKey) {
            $newValue[] = $value;

            end($newValue);
            $valueKey = key($newValue);
            reset($newValue);

            $modified = array_set($key, $newValue, $this->values);
        } else {
            // workaround solution for a value key that contains dot.
            $newValue = array_get($key, [], $this->values);
            $newValue[$valueKey] = $value;

            $modified = array_set($key, $newValue, $this->values);
        }

        foreach (get_registered_hooks($this, HooksEnum::POST_ADD_VALUE) as $callback) {
            call_user_func($callback, $this, $key.'.'.$valueKey, $value, $modified);
        }

        return $valueKey;
    })->call($object, $key, $value, $valueKey);
}

function set_value(object $object, string $key, $value): void
{
    if ($value instanceof \DateTimeZone || $value instanceof \DateTime || $value instanceof \DateInterval) {
        @trigger_error('Calling set_value with date objects is deprecated. Use cast classes.', E_USER_DEPRECATED);
    }

    (function($key, $value) {
        foreach (get_registered_hooks($this, HooksEnum::PRE_SET_VALUE) as $callback) {
            if (null !== $newValue = call_user_func($callback, $this, $key, $value)) {
                $value = $newValue;
            }
        }

        Assert::propertyExists($this, 'values');
        Assert::isArray($this->values);

        if (null !== $value) {
            $modified = array_set($key, $value, $this->values);
        } else {
            $modified = array_unset($key, $this->values);
        }

        foreach (get_registered_hooks($this, HooksEnum::POST_SET_VALUE) as $callback) {
            call_user_func($callback, $this, $key, $value, $modified);
        }
    })->call($object, $key, $value);
}

function get_value(object $object, string $key, $default = null)
{
    $castTo = null;

    $args = func_get_args();
    if (4 == count($args) && $args[3]) {
        $castTo = $args[3];

        @trigger_error('Calling get_value with $castTo argument is deprecated. Use cast classes.', E_USER_DEPRECATED);
    }

    return (function($key, $default, $castTo) {
        Assert::propertyExists($this, 'values');
        Assert::isArray($this->values);

        $value = array_get($key, $default , $this->values);

        foreach (get_registered_hooks($this, HooksEnum::POST_GET_VALUE) as $callback) {
            if (null !== $newValue = call_user_func($callback, $this, $key, $value, $default, $castTo)) {
                $value = $newValue;
            }
        }

        return $value;
    })->call($object, $key, $default, $castTo);
}

/**
 * @param string|callable|null $classOrCallable
 * @param array $values
 * @param object|null $context
 * @param string|null $contextKey
 *
 * @return object
 */
function build_object_ref($classOrCallable = null, array &$values = [], ?object $context = null, ?string $contextKey = null): object
{
    foreach (get_registered_hooks(HooksEnum::BUILD_OBJECT, HooksEnum::GET_OBJECT_CLASS) as $callback) {
        if ($dynamicClassOrCallable = call_user_func($callback, $values, $context, $contextKey, $classOrCallable)) {
            $classOrCallable = $dynamicClassOrCallable;
        }
    }

    if (false == $classOrCallable) {
        if ($context) {
            throw new \LogicException(sprintf(
                'Cannot built object for %s::%s. Either class or closure has to be passed explicitly or there must be a hook that provide an object class. Values: %s',
                get_class($context),
                $contextKey,
                str_pad(var_export($values, true), 100)
            ));
        } else {
            throw new \LogicException(sprintf(
                'Cannot built object. Either class or closure has to be passed explicitly or there must be a hook that provide an object class. Values: %s',
                str_pad(var_export($values, true), 100)
            ));
        }
    }

    if (is_callable($classOrCallable)) {
        $class = $classOrCallable($values);
    } else {
        $class = (string) $classOrCallable;
    }

    if (is_object($class)) {
        $object = $class;
    } else {
        $object = new $class();

        //values set in constructor
        $defaultValues = get_values($object, false);
        $values = array_replace($defaultValues, $values);

        set_values($object, $values, true);
    }

    if ($context) {
        foreach (get_registered_hooks($context, HooksEnum::POST_BUILD_SUB_OBJECT) as $callback) {
            call_user_func($callback, $object, $context, $contextKey);
        }
    } else {
        foreach (get_registered_hooks($object, HooksEnum::POST_BUILD_OBJECT) as $callback) {
            call_user_func($callback, $object);
        }
    }

    return $object;
}

/**
 * @param string|callable|null $classOrCallable
 * @param array $values
 *
 * @return object
 */
function build_object($classOrCallable = null, array $values = []): object
{
    return build_object_ref($classOrCallable, $values);
}

function clone_object(object $object): object
{
    return build_object(get_class($object), get_values($object, true));
}

/**
 * @deprecated
 */
class CastHooks {
    private static $castValueHook;

    private static $castToHook;

    public static function getCastValueHook(): \Closure
    {
        @trigger_error('CastHooks::getCastValueHook() is deprecated.', E_USER_DEPRECATED);

        if (static::$castValueHook === null) {
            static::$castValueHook = function($object, $key, $value) {
                return (function($key, $value) {
                    if (method_exists($this, 'castValue')) {
                        return $this->castValue($value);
                    }
                })->call($object, $key, $value);
            };
        }

        return static::$castValueHook;
    }

    public static function getCastToHook(): \Closure
    {
        @trigger_error('CastHooks::getCastValueHook() is deprecated.', E_USER_DEPRECATED);

        if (static::$castToHook === null) {
            static::$castToHook = function($object, $key, $value, $default, $castTo) {
                return (function($key, $value, $default, $castTo) {
                    if (method_exists($this, 'cast')) {
                        return $castTo ? $this->cast($value, $castTo) : $value;
                    }
                })->call($object, $key, $value, $default, $castTo);
            };
        }

        return static::$castToHook;
    }

}

/**
 * @deprecated
 */
function register_cast_hooks($objectOrClass = null): void
{
    @trigger_error('register_cast_hooks() is deprecated.', E_USER_DEPRECATED);

    $castValueHook = CastHooks::getCastValueHook();
    $castToHook = CastHooks::getCastToHook();

    if ($objectOrClass) {
        register_hook($objectOrClass, HooksEnum::PRE_SET_VALUE, $castValueHook);
        register_hook($objectOrClass, HooksEnum::PRE_ADD_VALUE, $castValueHook);
        register_hook($objectOrClass, HooksEnum::POST_GET_VALUE, $castToHook);
    } else {
        foreach (get_registered_hooks('_', HooksEnum::PRE_SET_VALUE) as $callback) {
            if ($castValueHook === $callback) {
                break;
            }
        }

        register_global_hook(HooksEnum::PRE_SET_VALUE, $castValueHook);
        register_global_hook(HooksEnum::PRE_ADD_VALUE, $castValueHook);
        register_global_hook(HooksEnum::POST_GET_VALUE, $castToHook);
    }
}

function call()
{
    $args = func_get_args();

    /** @var object $object */
    $object = array_shift($args);

    /** @var \Closure $closure */
    $closure = array_pop($args);

    return $closure->call($object, ...$args);
}
