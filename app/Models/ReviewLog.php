<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLog extends Model
{
    protected $table = 'sfi_mysql_tb_t_review_logs';

    protected $fillable = [
        'idea_id',
        'reviewer_id',
        'action',
        'comments',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}