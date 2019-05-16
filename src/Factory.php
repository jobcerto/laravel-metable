<?php

namespace Jobcerto\Metable;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class Factory implements Arrayable
{
    protected $subject;

    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Get an collection of meta values
     *
     * @return Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->subject->metable->pluck('value', 'key');
    }

    /**
     * [set description]
     * @param string $key   [description]
     * @param [type] $value [description]
     */
    public function set(string $key, $value)
    {
        if (str_contains($key, '.')) {
            throw new \Exception('you can\'t add a meta using dots on a key');
        }

        $meta = $this->raw($key);

        $meta ? $meta->update(['value' => $value])
        : $this->subject->metable()->create(['key' => $key, 'value' => $value]);

        return $this->get($key);
    }

    /**
     * [get description]
     * @param  string $key      [description]
     * @param  [type] $castable [description]
     * @return [type]           [description]
     */
    public function get(string $key, $castable = null, $ignoreTransformation = false)
    {

        if (str_contains($key, '.')) {
            return $this->findViaDotNotation($key, $castable);
        }

        if ( ! $this->has($key)) {
            return $this->tryCallableOrDefault($castable);
        }

        $value = $this->raw($key)->value;

        if (is_callable($castable)) {

            if (! $ignoreTransformation) {
                return call_user_func($castable, $value);
            }
        }

        return $this->castable($value, $castable);
    }

    /**
     * [tryCallableOrDefault description]
     * @param  [type] $castable [description]
     * @return [type]           [description]
     */
    private function tryCallableOrDefault($castable)
    {
        if (is_callable($castable)) {
            return call_user_func($castable);
        }

        if ($this->wantsReturnDefault($castable)) {
            return $castable;
        }

        return null;
    }

    /**
     * [only description]
     * @param  [type] $keys [description]
     * @return [type]       [description]
     */
    public function only(...$keys)
    {
        return collect($keys)->mapWithKeys(function ($key) {
            return [$key => $this->get($key)];
        });
    }

    /**
     * [delete description]
     * @param  string $key [description]
     * @return [type]      [description]
     */
    public function delete(string $key)
    {
        return $this->raw($key)->delete();
    }

    /**
     * [has description]
     * @param  string  $key [description]
     * @return boolean      [description]
     */
    public function has(string $key)
    {
        return $this->subject->metable()->where('key', $key)->exists();
    }

    /**
     * [findViaDotNotation description]
     * @param  string $dotNotation [description]
     * @param  [type] $default     [description]
     * @return [type]              [description]
     */
    protected function findViaDotNotation(string $dotNotation, $default = null)
    {
        $key = $this->getFirstKey($dotNotation);
        $meta = $this->get($key);

        $value = data_get($meta, str_after($dotNotation, $key . '.'), $default);

        if ($value !== $default) {
            return is_callable($default) ? call_user_func($default, $value) : $value;
        }

        return $value;
    }

    /**
     * [replace description]
     * @param  string $dotNotation [description]
     * @param  [type] $value       [description]
     * @return [type]              [description]
     */
    public function replace(string $dotNotation, $value)
    {

        throw_unless(str_contains($dotNotation, '.'), new \Exception('you have to give a value that can be replaceable'));

        $meta = $this->raw($this->getFirstKey($dotNotation));

        tap($meta)->forceFill([
            $this->qualifiedValueName($dotNotation) => $value,
        ])->save();

        return $meta;
    }

    /**
     * [toArray description]
     * @return [type] [description]
     */
    public function toArray()
    {
        return $this->all()->toArray();
    }

    /**
     * [qualifiedValueName description]
     * @param  [type] $keys [description]
     * @return [type]       [description]
     */
    private function qualifiedValueName($keys)
    {
        return 'value->' . $this->getUpdatableAttributes($keys);
    }

    /**
     * [getUpdatableAttributes description]
     * @param  [type] $keys [description]
     * @return [type]       [description]
     */
    private function getUpdatableAttributes($keys)
    {
        return str_after($this->replaceDotsWithArrows($keys), '->');
    }

    /**
     * Get the first key of search
     *
     * @param  string $keys dot notation
     * @return string
     */
    private function getFirstKey($keys)
    {
        return str_before($this->replaceDotsWithArrows($keys), '->');
    }

    /**
     * Replace Dots With Arrows
     * @param  string $keys
     *
     * @return stirng
     */
    private function replaceDotsWithArrows($keys)
    {
        return preg_replace('/\./', '->', $keys);
    }

    /**
     * Get Raw Value of meta
     *
     * @param  string $key
     *
     * @return mixed
     */
    private function raw(string $key)
    {
        return $this->subject->metable()->where('key', $key)->first();
    }

    /**
     * Determine if wants return the default value
     *
     * @param  mixed $castable
     *
     * @return bool
     */
    public function wantsReturnDefault($castable)
    {
        return ! in_array($castable, ['int', 'integer', 'string', 'bool', 'boolean', 'object', 'array', 'json', 'collection']) && ! is_null($castable);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string  $value
     * @param  bool  $asObject
     *
     * @return mixed
     */
    private function fromJson($value, $asObject = false)
    {
        return json_decode(json_encode($value), ! $asObject);
    }

    /**
     * Cast the given value
     *
     * @param mixed $value
     * @param  mixed $castable
     *
     * @return mixed
     */
    private function castable($value, $castable)
    {

        switch ($castable) {
            case 'int':
            case 'integer':
            return (int) $value;
            case 'string':
            return (string) $value;
            case 'bool':
            case 'boolean':
            return (bool) $value;
            case 'object':
            return $this->fromJson($value, true);
            case 'array':
            case 'json':
            return $this->fromJson($value);
            case 'collection':
            return new Collection($this->fromJson($value));
            default:
            return $value;
        }
    }
}
