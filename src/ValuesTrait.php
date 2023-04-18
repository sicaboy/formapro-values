<?php
namespace Formapro\Values;

trait ValuesTrait
{
    protected $values = [];

    protected function addValue(string $key, $value): void
    {
        add_value($this, $key, $value);
    }

    protected function setValue(string $key, $value): void
    {
        set_value($this, $key, $value);
    }

    protected function getValue(string $key, $default = null)
    {
        $castTo = null;
        $args = func_get_args();
        if (3 == count($args)) {
            return get_value($this, $key, $default, $args[2]);
        }

        return get_value($this, $key, $default);
    }
}
