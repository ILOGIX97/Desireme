<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class comment_comment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
    */
    protected $fillable = [
        'comment_id','user_id','comment'
    ];
}
