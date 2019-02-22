<?php

namespace Jobcerto\Metable;

use Jobcerto\Metable\MetableFactory;
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
        return new MetableFactory($this);
    }

    public function getDefaultModel()
    {
        if (property_exists($this, 'metableModel')) {
            return $this->metableModel;
        }

        return config('metable.meta_model');
    }
}
