<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\IdeaTeamMember;
use App\Models\IdeaAttachment;

class Idea extends Model
{
    protected $table = 'sfi_mysql_tb_t_ideas';

    protected $fillable = [
        'submission_code',
        'user_id',
        'type_of_improvement',
        'month',
        'year',
        'title',
        'description',
        'problem_description',
        'solution_description',
        'area_of_application',
        'implementation_date',
        'status', 
        'completion_percentage',
        'current_reviewer_role',
        'technical_pic',
        'managerial_decision_summary',
        'reward_request_type',
        'reward_request_amount',
        'reward_request_notes',
        'reward_processing_status',
        'reward_processed_at',
        'opw_type',
        'opw_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function score(): HasOne
    {
        return $this->hasOne(IdeaScore::class, 'idea_id');
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class, 'idea_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(IdeaTeamMember::class, 'idea_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IdeaAttachment::class, 'idea_id');
    }
}