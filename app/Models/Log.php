<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends Model
{
  use HasFactory;

  protected $table = 'logs';

  public $timestamps = false; // Only created_at exists in schema

  protected $fillable = [
    'user_id',
    'type',
    'description',
    'created_at',
  ];

  /**
   * Helper to set created_at automatically if not handled by DB default
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      if (empty($model->created_at)) {
        $model->created_at = now();
      }
    });
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
