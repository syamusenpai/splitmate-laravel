<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members')->withPivot('joined_at');
    }

    /** Name shown on cards and lists. */
    public function displayName(): string
    {
        return $this->name ?: ($this->username ?: strstr($this->email, '@', true));
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->displayName()));
        $letters = mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($letters ?: '?');
    }
}
