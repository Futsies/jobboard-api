<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'job_application_id',
        'employer_id',
        'title',
        'scheduled_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    /**
     * Get the job application this interview is for.
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get the employer/admin who scheduled the interview.
     */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    // --- Convenience relationships ---
    public function applicant() { return $this->jobApplication->user(); }
    public function job() { return $this->jobApplication->job(); }
}