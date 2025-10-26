<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'description',
        'is_admin',
        'is_employer',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_employer' => 'boolean',
    ];

    /**
     * Get the jobs saved by the user
     */
    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'saved_jobs', 'user_id', 'job_id')
                    ->withTimestamps();
    }

    /**
     * Get the jobs posted by the user (if employer)
     */
    public function postedJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'employer_id');
    }

    /**
     * Get the URL for the profile photo
     */
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo) {
            // Return the full URL to the stored image
            return asset('storage/' . $this->profile_photo);
        }
        
        // Return a default avatar if no profile photo
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePhoto()
    {
        if ($this->profile_photo) {
            Storage::delete('public/' . $this->profile_photo);
            $this->update(['profile_photo' => null]);
        }
    }

    /**
     * Get the job applications submitted by the user.
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'user_id');
    }

}