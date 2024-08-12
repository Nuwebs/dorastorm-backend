<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{

    protected $guarded = [];

    /**
     * Get the user that owns the Token
     *
     * @return BelongsTo<User, Token>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
