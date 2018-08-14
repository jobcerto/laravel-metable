<?php
namespace Jobcerto\Metable\Models;

use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    /**
     * Fields that can be mass assigned.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'value',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    public $casts = [
        'value' => 'array',
    ];

    /**
     * Metable Relation.
     *
     * @return MorphTo
     */
    public function subject()
    {
        return $this->morphTo('subject');
    }
}
