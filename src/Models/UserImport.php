<?php

namespace Intranet\Modules\UserImport\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserImport extends Model
{
    protected $fillable = [
        'user_id',
        'filename',
        'status',
        'total_rows',
        'created_count',
        'skipped_count',
        'error_message',
    ];

    /**
     * Der Administrator, der diesen Import ausgelöst hat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
