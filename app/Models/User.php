<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function instructorCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

        /**
     * Preinscriptions cannot be directly related to users via user_id
     * because the table identifies participants by CI and email, not by user FK.
     * Use Preinscription::where('email', $user->email) to look up a user's records.
     */
    public function preinscriptionsByEmail(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Preinscription::where('email', $this->email);
    }


    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verificado_por');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('admin');
        }

        if ($panel->getId() === 'instructor') {
            return $this->hasRole('instructor') || $this->hasRole('admin');
        }

        return false;
    }
}
