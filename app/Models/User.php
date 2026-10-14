<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property int|null $cafe_id
 * @property string $name «Имя Фамилия» — собирается из частей при сохранении
 * @property string|null $last_name
 * @property string|null $first_name
 * @property string|null $middle_name
 * @property string|null $avatar_path
 * @property string $email
 * @property UserRole $role
 * @property int|null $position_id
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'cafe_id', 'name', 'last_name', 'first_name', 'middle_name', 'avatar_path',
    'email', 'password', 'role', 'position_id', 'is_active',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        // name — для списков, инициалов и стартового набора; заполняется из ФИО.
        static::saving(function (self $user): void {
            $display = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

            if ($display !== '') {
                $user->name = $display;
            }
        });
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
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Cafe, $this>
     */
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return HasMany<PurchaseRequest, $this>
     */
    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class, 'author_id');
    }

    /**
     * @return HasMany<WorkShift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(WorkShift::class);
    }

    /**
     * Текущая открытая смена.
     *
     * @return HasOne<WorkShift, $this>
     */
    public function openShift(): HasOne
    {
        return $this->hasOne(WorkShift::class)->whereNull('ended_at')->latestOfMany('started_at');
    }

    /**
     * Активные управляющие и владельцы.
     *
     * @param  Builder<self>  $query
     */
    public function scopeManagers(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereIn('role', [UserRole::Manager, UserRole::Owner]);
    }

    public function isManager(): bool
    {
        return $this->role->canManage();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->isManager();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatarUrl();
    }

    /**
     * «Остапенко Владислав Сергеевич» — для документов и админки.
     */
    public function fullName(): string
    {
        $full = trim(implode(' ', array_filter([$this->last_name, $this->first_name, $this->middle_name])));

        return $full !== '' ? $full : $this->name;
    }

    /**
     * Как обращаться: «Владислав».
     */
    public function greetingName(): string
    {
        return $this->first_name ?: Str::before($this->name, ' ');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * «Владислав О.» — для списков и уведомлений.
     */
    public function shortName(): string
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name.' '.Str::substr($this->last_name, 0, 1).'.';
        }

        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];

        return count($parts) < 2 ? $this->name : $parts[0].' '.Str::substr($parts[1], 0, 1).'.';
    }
}
