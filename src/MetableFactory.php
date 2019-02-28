<?php

namespace Jobcerto\Metable;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class MetableFactory implements Arrayable
{
    protected $subject;

    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    public function all()
    {
        return $this->subject->metable;
    }

    public function create(string $key, $value)
    {
        return $this->subject->metable()->create(['key' => $key, 'value' => $value]);
    }

    public function update(string $key, $newValue)
    {
        $meta = $this->raw($key);

        return tap($meta)->update(['value' => $newValue]);
    }

    public function find(string $key, $castable = null)
    {

        $value = $this->raw($key)->value;

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

    public function findMany(...$keys)
    {
        return collect($keys)->mapWithKeys(function ($key) {
            return [$key => $this->raw($key)->value];
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
            return $this->find($dotNotation);
        }

        $key = $this->getFirstKey($dotNotation);
        $meta = $this->find($key);

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
        return collect($this->all())->toArray();
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
        return $this->subject->metable()->where('key', $key)->firstOrFail();
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
}
