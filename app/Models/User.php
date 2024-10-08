<?php

namespace App\Models;

use App\Notifications\DsVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject, LaratrustUser
{
    /**
     * @use HasFactory<\Database\Factories\UserFactory>
     */
    use HasFactory, Notifiable, HasRolesAndPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string>
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role(): Role
    {
        // @phpstan-ignore-next-line
        return $this->roles->first();
    }

    /**
     * @return array<string>
     */
    public function getAllPermissionsNames(): array
    {
        $userPermissions = $this->allPermissions();
        $permissionNames = [];
        foreach ($userPermissions as $permission) {
            // @phpstan-ignore-next-line
            array_push($permissionNames, $permission->name);
        }
        return $permissionNames;
    }

    public function delete(): bool
    {
        if (!parent::delete())
            return false;
        $this->syncRoles([]);
        return true;
    }

    /**
     * This method is overrides the default sendEmailVerificationNotification in order to
     * use the DsVerifyEmail notification. That notification is exactly the same as the
     * one that it is extending, however, it queues the mail.
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new DsVerifyEmail);
    }

    /**
     * Get all of the tokens for the User
     *
     * @return HasMany<Token>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }
}
