<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaTeamMember extends Model
{
    protected $table = 'sfi_mysql_tb_t_idea_team_members';

    protected $fillable = [
        'idea_id',
        'name',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }
}
