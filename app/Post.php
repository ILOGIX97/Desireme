<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
     
    /**
     * The attributes that are mass assignable.
     *
     * @var array
    */
    protected $fillable = [
        'comment', 'tags', 'publish', 'schedule_at', 'add_to_album'
    ];

    public function user()
    {
        return $this->belongsToMany('App\User');
    }

    
}
