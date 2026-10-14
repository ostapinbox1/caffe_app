<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Cafe;
use App\Models\Position;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Завести человека или сбросить ему пароль из консоли сервера.
 * Печатает «логин пароль» — пароль больше нигде не хранится.
 */
class CreateCafeUser extends Command
{
    protected $signature = 'cafe:user
        {email : логин (почта)}
        {--first= : имя}
        {--last= : фамилия}
        {--middle= : отчество}
        {--role=staff : owner, manager или staff}
        {--position= : должность, например «Бариста» (заводится, если её нет)}
        {--cafe= : номер кофейни, по умолчанию первая}';

    protected $description = 'Завести сотрудника или сбросить ему пароль';

    public function handle(): int
    {
        $validator = validator($this->arguments() + $this->options(), [
            'email' => ['required', 'email', 'max:255'],
            'first' => ['required', 'string', 'max:100'],
            'last' => ['nullable', 'string', 'max:100'],
            'middle' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $cafe = $this->option('cafe')
            ? Cafe::query()->findOrFail($this->option('cafe'))
            : Cafe::query()->orderBy('id')->firstOrFail();

        $position = $this->option('position')
            ? Position::withoutGlobalScopes()->firstOrCreate(['cafe_id' => $cafe->id, 'name' => $this->option('position')])
            : null;

        $password = Str::password(14, symbols: false);

        $user = User::query()->firstOrNew(['email' => Str::lower($this->argument('email'))]);
        $user->forceFill([
            'cafe_id' => $cafe->id,
            'first_name' => $this->option('first'),
            'last_name' => $this->option('last'),
            'middle_name' => $this->option('middle'),
            'role' => UserRole::from($this->option('role')),
            'position_id' => $position?->id ?? $user->position_id,
            'is_active' => true,
            'password' => $password,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->line("{$user->email} {$password}");

        return self::SUCCESS;
    }
}
