<?php

namespace App\Utils;

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
        Token::create([
            'key' => $key,
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
        Token::create([
            'key' => $key,
            'revoked' => true,
        ]);
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $token = Token::where('key', $key)->first();

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
     * @param  string  $key
     * @return bool
     */
    public function destroy($key)
    {
        $token = Token::where('key', $key)->first();
        if (empty($token))
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
