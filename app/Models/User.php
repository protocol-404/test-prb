<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the skills associated with the user.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    /**
     * Get the job offers created by the recruiter.
     */
    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class, 'recruiter_id');
    }

    /**
     * Get the applications submitted by the user.
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get the resumes uploaded by the user.
     */
    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    /**
     * Check if user is a recruiter.
     *
     * @return bool
     */
    public function isRecruiter()
    {
        return $this->role === 'recruiter' || $this->role === 'admin';
    }

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
