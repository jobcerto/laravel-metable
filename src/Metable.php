<?php

namespace Jobcerto\Metable;

trait Metable
{
    /**
     * Relationship to the `Meta` model.
     *
     * @return MorphMany
     */
    public function meta()
    {
        return $this->morphMany(config('metable.model'), 'subject');
    }

    /**
     * Get the given meta
     *
     * @param  string $key
     * @return null|Meta
     */
    public function getMeta($key)
    {
        // digamos que eu receba algo como item.teste

        if (str_contains($key, '.')) {
            return data_get($this->meta->where('key', str_before($key, '.'))->first()->value, str_after($key, '.'));
        }

        // preciso retornar o key item com os valores de teste
        if ($meta = $this->hasMeta($key)) {
            return $this->meta->where('key', $key)->first()->value;
        }

        return null;
    }

    /**
     * Seta meta attributes
     *
     * @param string $key
     * @param Meta
     */
    public function setMeta($key, $value)
    {
        if ($this->hasMeta($key)) {
            throw new \Exception('You can\'t assign a new value to an existing meta with setMeta');
        }

        return $this->meta()->create(['key' => $key, 'value' => $value]);
    }

    public function hasMeta($key)
    {
        return !  ! $this->meta->where('key', $key)->count();
    }

/**
 * Update meta attributes
 *
 * @param  string $keys  accept dot notation
 * @param  mixed $value
 * @return Meta
 */
    public function updateMeta(string $keys, $value)
    {
        $meta = $this->meta
            ->where('key', $this->getFirstKey($keys))
            ->first();
        str_contains($keys, '.')
        ? $this->updateNestedMeta($keys, $value, $meta)
        : $this->updateSingleMeta($meta, $value);

        return $meta;
    }

    public function updateNestedMeta($keys, $value, $meta)
    {
        $this->replaceDotsWithArrows($keys);

        $meta->update([
            $this->qualifiedValueName($keys) => $value,
        ]);
    }

    public function updateSingleMeta($meta, $value)
    {
        if (is_array($value)) {
            return $meta->update(['value' => array_replace_recursive(array_wrap($meta->value), $value)]);
        }

        return $meta->update(['value' => $value]);
    }

    public function qualifiedValueName($keys)
    {
        return 'value->' . $this->getUpdatableAttributes($keys);
    }

    public function getUpdatableAttributes($keys)
    {
        return str_after($this->replaceDotsWithArrows($keys), '->');
    }

    public function getFirstKey($keys)
    {
        return str_before($this->replaceDotsWithArrows($keys), '->');
    }

    public function replaceDotsWithArrows($keys)
    {
        return preg_replace('/\./', '->', $keys);
    }
}
