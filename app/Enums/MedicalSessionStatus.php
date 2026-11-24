<?php

namespace App\Enums;

enum MedicalSessionStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programada',
            self::Active => 'Activa',
            self::Paused => 'Pausada',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function acceptsBookings(): bool
    {
        return in_array($this, [self::Scheduled, self::Active], true);
    }
}
