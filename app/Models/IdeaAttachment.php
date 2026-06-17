<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaAttachment extends Model
{
    protected $table = 'sfi_mysql_tb_t_idea_attachments';

    protected $fillable = [
        'idea_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'is_video',
        'is_compressed',
        'compression_ratio',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }
}
