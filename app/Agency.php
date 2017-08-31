<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
  /**
  * set custom timestamp column names
  */
  const CREATED_AT = 'date_rec';
  const UPDATED_AT = 'date_mod';
    
  /**
  * set DB table to: agency
  *
  * @var string
  */
  protected $table = 'agencies';

  /**
  * The attributes that are mass assignable.
  *
  * @var array
  */
  protected $fillable = [
    'mod_id', 'mod_user',
    'name', 'addr', 'addr2', 'city', 'state_id', 'zip', 'tel',
    'spiff', 'residual',
    'active'
  ];
}
