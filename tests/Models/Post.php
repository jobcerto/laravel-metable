<?php

namespace Jobcerto\Metable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Jobcerto\Metable\Traits\Metable;

class Post extends Model
{
    use Metable;

    protected $guarded = [];
}
