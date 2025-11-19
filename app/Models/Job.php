<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employer_id',
        'job_title',
        'job_description',
        'job_location',
        'job_type',
        'salary',
        'complete',
        'company_name',
        'company_logo',
        'category',
    ];

    protected $casts = [
        'complete' => 'boolean',
        'salary' => 'decimal:2',
    ];

    /**
     * Get the employer who posted the job
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    /**
     * The users who have saved this job.
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_jobs', 'job_id', 'user_id')
                    ->withTimestamps(); // Include timestamps if you want created_at/updated_at on pivot
    }

    /**
     * Get the applications received for this job.
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }
    
}