<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

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
    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    /**
     * Get users who saved this job
     */
    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_jobs');
    }
}