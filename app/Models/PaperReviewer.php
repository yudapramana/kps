<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PaperReviewer extends Pivot
{
    protected $table = 'paper_reviewer';

    protected $fillable = [
        'paper_id',
        'user_id',
        'review_order',
        'score',
        'notes',
        'assigned_at',
        'reviewed_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'assigned_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}