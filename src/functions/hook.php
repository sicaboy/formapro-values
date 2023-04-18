<?php
namespace Formapro\Values;

/**
 * @param object|string $objectOrClass
 * @param string        $hook
 * @param \Closure      $callback
 */
function register_hook($objectOrClass, $hook, \Closure $callback): void
{
    HookStorage::register($objectOrClass, $hook, $callback);
}

/**
 * @param string        $hook
 * @param \Closure      $callback
 */
function register_global_hook($hook, \Closure $callback): void
{
    HookStorage::registerGlobal($hook, $callback);
}

/**
 * @param object|string $objectOrClass
 * @param string $hook
 *
 * @return \Closure[]|\Traversable
 */
function get_registered_hooks($objectOrClass, $hook): \Traversable
{
    return HookStorage::get($objectOrClass, $hook);
}

