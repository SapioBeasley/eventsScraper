<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Job extends Model {

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'source_id',
    'name',
    'is_enabled',
    'last_run_at',
    'url',
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
    'source_id' => 'integer',
    'is_enabled' => 'boolean',
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

  public function source()
  {
    return $this->belongsTo('App\Source');
  }

  public function results()
  {
    return $this->hasMany('App\Result');
  }

}
