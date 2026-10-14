<?php

namespace Database\Seeders;

use App\Enums\ChecklistDeadline;
use App\Models\Cafe;
use App\Models\ChecklistTemplate;
use App\Models\Position;
use Illuminate\Database\Seeder;

/**
 * Чек-листы от управляющего. Первый — «Закрытие смены бариста»
 * (бумажный лист «Чек-лист закрытия смены бариста», октябрь 2026).
 */
class ChecklistSeeder extends Seeder
{
    /**
     * Раздел => [пункт или [пункт, подсказка]].
     */
    public const BARISTA_CLOSING = [
        'Оборудование' => [
            'Кофемашина выключена',
            'Основная кофемолка выключена',
            'Кофемолка для декафа выключена',
            'Кассовая смена закрыта, касса выключена',
            'Су-шефы выключены',
            'Вытяжки выключены',
            'Плита и все конфорки выключены',
            'Фритюрница выключена',
            'Телевизоры выключены',
        ],
        'Безопасность' => [
            'На плитах и во фритюрнице ничего не оставлено',
            'Нет запаха гари, дыма и признаков перегрева',
            'Водяные краны закрыты',
            'Окна закрыты',
        ],
        'Освещение' => [
            'Свет в витринах выключен',
            ['Свет в холодильниках выключен', 'Только свет — холодильное оборудование не отключать'],
            'Весь свет в зале выключен',
            'Свет на кухне выключен',
            'Свет на летнике выключен',
            'Свет в туалетах выключен',
            ['Свет в серверной выключен', 'Серверное оборудование не отключать'],
            'Свет на складе выключен',
            'Свет в помещении «Авиафлота» выключен',
        ],
        'Финальная проверка' => [
            'Летник проверен',
            'Все гости и сотрудники покинули помещение',
            'Входные двери закрыты',
            'Помещение поставлено на охрану',
        ],
    ];

    public function run(Cafe $cafe): void
    {
        $template = ChecklistTemplate::withoutGlobalScopes()->firstOrCreate(
            ['cafe_id' => $cafe->id, 'name' => 'Закрытие смены бариста'],
            [
                'description' => 'Заполняется ежедневно перед постановкой помещения на охрану. '
                    .'Холодильное и серверное оборудование не выключать.',
                'deadline_type' => ChecklistDeadline::ShiftEnd,
                'sort' => 10,
            ],
        );

        if ($template->sections()->exists()) {
            return;
        }

        foreach (array_keys(self::BARISTA_CLOSING) as $sectionSort => $title) {
            $section = $template->sections()->create(['title' => $title, 'sort' => $sectionSort + 1]);

            foreach (self::BARISTA_CLOSING[$title] as $itemSort => $item) {
                [$text, $hint] = is_array($item) ? $item : [$item, null];
                $section->items()->create(['text' => $text, 'hint' => $hint, 'sort' => $itemSort + 1]);
            }
        }

        $barista = Position::withoutGlobalScopes()->where(['cafe_id' => $cafe->id, 'name' => 'Бариста'])->first();

        if ($barista !== null) {
            $template->positions()->syncWithoutDetaching([$barista->id]);
        }
    }
}
