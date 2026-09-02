<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // بس created_at، ما في updated_at

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array', // jsonb يتحول تلقائياً لـ array بـ PHP
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}