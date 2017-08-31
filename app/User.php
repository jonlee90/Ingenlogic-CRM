<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
  use Notifiable;
    
  /**
  * custom table name to use for user login
  * without the following variable, the Laravel uses 'users' by default
  *
  * @var string
  */
  protected $table = 'login_users';

  /**
  * The attributes that are mass assignable.
  *
  * @var array
  */
  protected $fillable = [
    // should match with DB field-names
    'mod_id', 'mod_user', 'email', 'password', 'access_lv', 'fname', 'lname', 'active'
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
  * Send the password reset notification.
  *
  * @param  string  $token
  * @return void
  */
  /*
  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPasswordNotification($token));
  }
  */
}
