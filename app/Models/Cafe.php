<?php

namespace App\Models;

use Database\Factories\CafeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Cafe extends Model
{
    /** @use HasFactory<CafeFactory> */
    use HasFactory;

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<RequestSection, $this>
     */
    public function requestSections(): HasMany
    {
        return $this->hasMany(RequestSection::class);
    }

    /**
     * Кто получает уведомления о новых заявках.
     *
     * @return HasMany<User, $this>
     */
    public function managers(): HasMany
    {
        return $this->users()->managers();
    }
}
