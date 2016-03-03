<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'job_id',
    'identifier',
    'start_date',
    'end_date',
    'timezone',
    'processed_at',
    'processed_status',
    'result',
  ];

  /**
   * The attributes excluded from the model's JSON form.
   *
   * @var array
   */
  protected $hidden = [
    'updated_at',
  ];

  /**
   * The attributes that should be casted to native types.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'integer',
  ];

  /**
   * The validation rules for this model
   *
   * @var array
   */
  public $rules = [
  ];

  /**
   * Model Query Filters
   *
   * @var array
   */
  public $filters = [

    'id' => [
      'match' => '='
    ],

  ];

  /**
   * Default values for fields template
   * the defaults for newly created events
   *
   * @var array
   */
  public $defaults = [

    // ***************************************
    // Meta
    // ***************************************
    /*
    'meta' => [
    ],
    */
  ];

  public function job()
  {
    return $this->belongsTo('App\Job');
  }

}
