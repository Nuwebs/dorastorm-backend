<?php

namespace App\Utils\Auth;

use App\Models\Token;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Contracts\Providers\Storage;

class DsTokenBlacklist implements Storage
{
    /**
     * @param  string  $key
     * @param  mixed  $until
     * @param  int  $minutes
     * @return void
     */
    public function add($key, $until, $minutes)
    {
        $add = ceil(abs($minutes));
        $token = Token::where('key', $key)->first();

        if (!$token)
            return;

        $token->update([
            'blacklist_until' => Carbon::parse($until['valid_until'])->addMinutes($add),
            'revoked' => true
        ]);
    }

    /**
     * @param  string  $key
     * @param  mixed  $until
     * @return void
     */
    public function forever($key, $until)
    {
        $token = Token::where('key', $key)->first();

        if (!$token)
            return;

        $token->update([
            'revoked' => true,
        ]);
    }

    /**
     * Checks if the token is revoked
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $token = Token::where('key', $key)->where('revoked', true)->first();

        if (empty($token)) {
            return null;
        }

        if (is_null($token->blacklist_until)) {
            // This is hardcoded because Tymon JWT requires it
            return 'forever';
        }

        return [
            'valid_until' => $token->blacklist_until
        ];
    }

    /**
     * Removes the revoked status of the token
     * @param  string  $key
     * @return bool
     */
    public function destroy($key)
    {
        $token = Token::where('key', $key)->where('revoked', true)->first();

        if (!$token)
            return false;

        $token->update([
            'revoked' => false
        ]);
        return true;
    }

    /**
     * @return void
     */
    public function flush()
    {
    }
}
