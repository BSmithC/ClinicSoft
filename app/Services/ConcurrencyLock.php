<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * MySQL advisory locks serialize conflicting writes. SQLite (test suite)
 * ignores the lock and relies on transactions instead.
 */
final class ConcurrencyLock
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     */
    public function run(string $key, callable $callback): mixed
    {
        return $this->runMany([$key], $callback);
    }

    /**
     * Acquires every lock in a deterministic order so two requests can never
     * deadlock while booking the same patient and doctor from different flows.
     *
     * @param array<int, string> $keys
     * @template T
     *
     * @param callable(): T $callback
     * @return T
     */
    public function runMany(array $keys, callable $callback): mixed
    {
        $keys = array_values(array_unique($keys));
        sort($keys, SORT_STRING);

        if (DB::getDriverName() !== 'mysql') {
            return $callback();
        }

        $held = [];

        try {
            foreach ($keys as $key) {
                $name = substr('gcd:' . $key, 0, 64);
                $acquired = DB::select('SELECT GET_LOCK(?, 10) AS acquired', [$name])[0]->acquired ?? 0;

                if ((int) $acquired !== 1) {
                    throw new \RuntimeException('No se pudo adquirir el bloqueo de reserva. Intente nuevamente.');
                }

                $held[] = $name;
            }

            return $callback();
        } finally {
            foreach (array_reverse($held) as $name) {
                DB::select('SELECT RELEASE_LOCK(?) AS released', [$name]);
            }
        }
    }
}
