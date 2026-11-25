<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_ADMISSIONS_STAFF = 'admissions_staff';
    public const ROLE_ADMISSIONS_ADMIN = 'admissions_admin';
    public const ROLE_PRISM_ENCODER = 'prism_encoder';
    public const ROLE_SYSTEM_ADMIN = 'system_admin';
    public const ROLE_LEGACY_ADMIN = 'admin';

    public const STAFF_ROLES = [
        self::ROLE_ADMISSIONS_STAFF,
        self::ROLE_ADMISSIONS_ADMIN,
        self::ROLE_PRISM_ENCODER,
        self::ROLE_SYSTEM_ADMIN,
        self::ROLE_LEGACY_ADMIN,
    ];

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function canReviewRequirements(): bool
    {
        return $this->hasRole(self::ROLE_ADMISSIONS_STAFF, self::ROLE_ADMISSIONS_ADMIN, self::ROLE_SYSTEM_ADMIN, self::ROLE_LEGACY_ADMIN);
    }

    public function canRecordEvaluation(): bool
    {
        return $this->hasRole(self::ROLE_ADMISSIONS_ADMIN, self::ROLE_SYSTEM_ADMIN, self::ROLE_LEGACY_ADMIN);
    }

    public function canEncodePrism(): bool
    {
        return $this->hasRole(self::ROLE_PRISM_ENCODER, self::ROLE_ADMISSIONS_ADMIN, self::ROLE_SYSTEM_ADMIN, self::ROLE_LEGACY_ADMIN);
    }

    public function canManageSystem(): bool
    {
        return $this->hasRole(self::ROLE_SYSTEM_ADMIN, self::ROLE_LEGACY_ADMIN);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ApplicationStatusLog::class, 'actor_user_id');
    }
}
