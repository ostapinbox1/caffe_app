<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Пункт чек-листа сотрудника: отмечен ли и когда.
 *
 * @property int $id
 * @property int $checklist_run_id
 * @property string $section
 * @property string $text
 * @property string|null $hint
 * @property int $sort
 * @property Carbon|null $checked_at
 */
#[Fillable(['checklist_run_id', 'section', 'text', 'hint', 'sort', 'checked_at'])]
class ChecklistRunItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ChecklistRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ChecklistRun::class, 'checklist_run_id');
    }
}
