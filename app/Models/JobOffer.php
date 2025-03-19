<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recruiter_id',
        'title',
        'description',
        'category',
        'location',
        'contract_type',
        'status',
    ];

    /**
     * Get the recruiter that owns the job offer.
     */
    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    /**
     * Get the applications for the job offer.
     */
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Scope a query to only include active job offers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $location
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Scope a query to filter by contract type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $contractType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByContractType($query, $contractType)
    {
        return $query->where('contract_type', $contractType);
    }
}
