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
        'comment', 'tags', 'display_name', 'username', 'email', 'password', 'profile', 'cover', 'photo_id','photo_id_1', 'location', 'category', 'term', 'year_old', 'two_factor'
    ];
}
