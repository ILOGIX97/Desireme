<?php

namespace App;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Lang;
use Laravel\Passport\HasApiTokens;
//use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens,HasRoles; //

    protected $guard_name = 'api';

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'display_name', 'username', 'email', 'password', 'profile', 'cover', 'photo_id','photo_id_1', 'location', 'category', 'term', 'year_old', 'two_factor','contact'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token) {
        // The trick is first to instantiate the notification itself
        $linktoken = $token;
        $notification = new ResetPassword($token);
        // Then use the createUrlUsing method for pass custom url
        $notification->createUrlUsing(function ($token) use ($linktoken) {
            return config('app.forgotPassword').'?token='.$linktoken.'&email='.$token->email;
        });

        //enable it when use the custom template
        /*$notification->toMailUsing(function ($token) use ($linktoken){
            $url = config('app.forgotPassword').'?token='.$linktoken.'&email='.$token->email;
            return (new MailMessage)
                ->subject(Lang::get('Reset Password Notification'))
                ->markdown('mail.resetPassword', ['url' => $url, "data" => $token]);
        });*/

        // Then you pass the notification
        $this->notify($notification);
    }

    public function posts()
    {
        return $this->belongsToMany('App\Post', 'post_user', 'user_id', 'post_id');
    }

    
}
