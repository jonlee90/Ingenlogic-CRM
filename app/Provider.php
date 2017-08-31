<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
  /**
  * set custom timestamp column names
  */
  const CREATED_AT = 'date_rec';
  const UPDATED_AT = 'date_mod';
    
  /**
  * set DB table to: agents
  *
  * @var string
  */
  protected $table = 'providers';

  /**
  * The attributes that are mass assignable.
  *
  * @var array
  */
  protected $fillable = [
    'mod_id', 'mod_user', 'name', 'addr','addr2','city','state_id','zip','tel',
    'default_term',
    'default_spiff','default_residual',
    'active'
  ];
}
