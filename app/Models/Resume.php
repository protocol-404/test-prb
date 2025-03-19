<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
    ];

    /**
     * Get the user that owns the resume.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the applications that use this resume.
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
