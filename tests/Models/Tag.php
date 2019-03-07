<?php

namespace Jobcerto\Metable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Jobcerto\Metable\Tests\Models\TagMeta;
use Jobcerto\Metable\Traits\Metable;

class Tag extends Model
{
    use Metable;

    protected $metableModel = TagMeta::class;

    /**
     * I'm not a child.
     *
     * @var array
     */
    protected $guarded = [];
}
