<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use ValueError;

enum Unit: string implements HasLabel
{
    case Piece = 'pcs';
    case Pack = 'pack';
    case Gram = 'g';
    case Kilogram = 'kg';
    case Milliliter = 'ml';
    case Liter = 'l';
    case Bunch = 'bunch';

    public function getLabel(): string
    {
        return match ($this) {
            self::Piece => 'шт',
            self::Pack => 'уп',
            self::Gram => 'г',
            self::Kilogram => 'кг',
            self::Milliliter => 'мл',
            self::Liter => 'л',
            self::Bunch => 'пучок',
        };
    }

    /**
     * Шаг кнопок «−/+» в форме заявки.
     */
    public function step(): float
    {
        return match ($this) {
            self::Gram, self::Milliliter => 100,
            self::Kilogram, self::Liter => 0.5,
            default => 1,
        };
    }

    /**
     * Единица по подписи из справочника: «кг», «пучок»…
     */
    public static function fromLabel(string $label): self
    {
        $label = mb_strtolower(trim($label));

        foreach (self::cases() as $unit) {
            if ($unit->getLabel() === $label) {
                return $unit;
            }
        }

        throw new ValueError("Неизвестная единица: {$label}");
    }

    /**
     * Подписи для выпадающих списков: ['kg' => 'кг', …].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $unit) => [$unit->value => $unit->getLabel()])
            ->all();
    }
}
