<?php

namespace Jobcerto\Metable;

use Illuminate\Contracts\Support\Arrayable;

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

    public function find(string $key)
    {
        return $this->raw($key)->value;
    }

    public function delete(string $key)
    {
        $this->raw($key)->delete();
    }

    public function has(string $key)
    {
        return $this->subject->metable()->where('key', $key)->exists();
    }

    public function search(string $dotNotation, $default = null)
    {
        $key = $this->getFirstKey($dotNotation);
        $meta = $this->find($key);

        return data_get($meta, str_after($dotNotation, $key . '.'), $default);
    }

    public function replace(string $dotNotation, $value)
    {

        throw_unless(str_contains($dotNotation, '.'), new \Exception('you have to give a value that can be replaceable'));

        $meta = $this->raw($this->getFirstKey($dotNotation));

        $meta->forceFill([
            $this->qualifiedValueName($dotNotation) => $value,
        ])->save();
    }

    public function toArray()
    {
        return $this->all();
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

}
