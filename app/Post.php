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
        'comment', 'tags', 'publish','media', 'schedule_at', 'add_to_album'
    ];

    public function users()
    {
        return $this->belongsToMany('App\User', 'post_user', 'post_id', 'user_id');
    }

    
}
