<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Пункт шаблона: «Кофемашина выключена» + подсказка мелким шрифтом.
 *
 * @property int $id
 * @property int $checklist_template_section_id
 * @property string $text
 * @property string|null $hint
 * @property int $sort
 */
#[Fillable(['checklist_template_section_id', 'text', 'hint', 'sort'])]
class ChecklistTemplateItem extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<ChecklistTemplateSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateSection::class, 'checklist_template_section_id');
    }
}
