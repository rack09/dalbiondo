<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const SESSION_ACCESS_VERSION = 'staff_access_version';

    protected $attributes = [
        'is_active' => true,
        'staff_access_version' => 0,
    ];

    public function changePassword(#[\SensitiveParameter] string $password): bool
    {
        $changes = [
            'password' => Hash::make($password),
            'remember_token' => null,
            'staff_access_version' => $this->staff_access_version + 1,
        ];

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('role', $this->role)
            ->where('password', $this->getAuthPassword())
            ->where('staff_access_version', $this->staff_access_version)
            ->update($changes);

        if ($updated !== 1) {
            return false;
        }

        $this->forceFill($changes)->syncOriginalAttributes(array_keys($changes));

        return true;
    }

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
            'is_active' => 'boolean',
            'staff_access_version' => 'integer',
        ];
    }
}
