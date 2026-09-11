<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;


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

    public function preinscriptions(): HasMany
    {
        return $this->hasMany(Preinscription::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
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

