<?php

namespace Jobcerto\Metable\Traits;

use Jobcerto\Metable\Factory;
use Jobcerto\Metable\Models\Meta;

trait Metable
{
    /**
     * Relationship to the `Meta` model.
     *
     * @return MorphMany
     */
    public function metable()
    {
        return $this->morphMany($this->getDefaultModel(), 'subject');
    }

    public function getMetaAttribute()
    {
        return new Factory($this);
    }

    public function getDefaultModel()
    {
        if (property_exists($this, 'metableModel')) {
            return $this->metableModel;
        }

        return config('metable.meta_model');
    }
}
