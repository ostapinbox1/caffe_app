<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Раздел шаблона: «Оборудование», «Освещение»…
 *
 * @property int $id
 * @property int $checklist_template_id
 * @property string $title
 * @property int $sort
 */
#[Fillable(['checklist_template_id', 'title', 'sort'])]
class ChecklistTemplateSection extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<ChecklistTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    /**
     * @return HasMany<ChecklistTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class)->orderBy('sort');
    }
}
