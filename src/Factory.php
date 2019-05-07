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

    public function get(string $key, $castable = null)
    {

        if (str_contains($key, '.')) {
            return $this->search($key, $castable);
        }

        if ( ! $this->has($key)) {
            if (is_callable($castable)) {
                return call_user_func($castable);
            }

            if ($this->wantsReturnDefault($castable)) {
                return $castable;
            }

            return null;
        }

        $value = $this->raw($key)->value;

        return $this->castable($value, $castable);
    }

    public function only(...$keys)
    {
        return collect($keys)->mapWithKeys(function ($key) {
            return [$key => $this->get($key)];
        });
    }

    public function delete(string $key)
    {
        return $this->raw($key)->delete();
    }

    public function has(string $key)
    {
        return $this->subject->metable()->where('key', $key)->exists();
    }

    public function search(string $dotNotation, $default = null)
    {

        if ( ! str_contains($dotNotation, '.')) {
            return $this->get($dotNotation);
        }

        $key = $this->getFirstKey($dotNotation);
        $meta = $this->get($key);

        return data_get($meta, str_after($dotNotation, $key . '.'), $default);
    }

    public function replace(string $dotNotation, $value)
    {

        throw_unless(str_contains($dotNotation, '.'), new \Exception('you have to give a value that can be replaceable'));

        $meta = $this->raw($this->getFirstKey($dotNotation));

        tap($meta)->forceFill([
            $this->qualifiedValueName($dotNotation) => $value,
        ])->save();

        return $meta;
    }

    public function toArray()
    {
        return $this->all()->toArray();
    }

    private function qualifiedValueName($keys)
    {
        return 'value->' . $this->getUpdatableAttributes($keys);
    }

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

    private function replaceDotsWithArrows($keys)
    {
        return preg_replace('/\./', '->', $keys);
    }

    private function raw(string $key)
    {
        return $this->subject->metable()->where('key', $key)->first();
    }

    public function wantsReturnDefault($castable)
    {
        return ! in_array($castable, ['int', 'integer', 'string', 'bool', 'boolean', 'object', 'array', 'json', 'collection']) && ! is_null($castable);
    }

    /**
     * Decode the given JSON back into an array or object.
     *
     * @param  string  $value
     * @param  bool  $asObject
     * @return mixed
     */
    private function fromJson($value, $asObject = false)
    {
        return json_decode(json_encode($value), ! $asObject);
    }

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
