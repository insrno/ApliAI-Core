<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'resume_id',
        'job_description_id',
        'score',
        'feedback',
        'strengths',
        'weaknesses',
    ];

    protected $casts = [
        'strengths' => 'array',
        'weaknesses' => 'array',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class);
    }
}
