<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentStatusHistory extends Model
{
    use HasFactory;

         protected $table = 'shipment_status_history';

    public $timestamps = false; 
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'shipment_id',
        'status',
        'changed_by',
        'note',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}