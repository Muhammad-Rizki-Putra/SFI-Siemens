<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaScore extends Model
{
    protected $table = 'sfi_mysql_tb_t_idea_scores';

    protected $fillable = [
        'idea_id',
        'category',
        'cost_savings',
        'reward_percent',
        'voucher_reward',
        'appraisal',
        'factor_a1',
        'factor_a2',
        'factor_a3',
        'factor_b',
        'implementation_factor',
        'factor_c',
        'suggestion_factor',
        'total_points',
        'calculated_reward',
        'final_adjusted_reward',
        'remark',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }
}