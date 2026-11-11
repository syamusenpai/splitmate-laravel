<?php

namespace App\Services;

use App\Models\Group;

/**
 * Works out who owes whom inside a group.
 *
 * All money is handled in sen (integer cents) so that rounding never
 * leaves a stray RM0.01 behind.
 */
class BalanceCalculator
{
    /**
     * Net balance per member in sen.
     * Positive = the group owes them; negative = they owe the group.
     *
     * @return array<int, int> user id => balance in sen
     */
    public function balances(Group $group): array
    {
        $group->loadMissing(['members', 'expenses.splits', 'settlements']);

        $balances = [];
        foreach ($group->members as $member) {
            $balances[$member->id] = 0;
        }

        $add = function (int $userId, int $sen) use (&$balances) {
            $balances[$userId] = ($balances[$userId] ?? 0) + $sen;
        };

        foreach ($group->expenses as $expense) {
            // The payer is credited the full amount ...
            $add($expense->paid_by, self::toSen($expense->amount));

            // ... and each person is debited their share.
            foreach ($expense->splits as $split) {
                $add($split->user_id, -self::toSen($split->amount));
            }
        }

        foreach ($group->settlements as $settlement) {
            // Paying back a debt raises the payer's balance and lowers the receiver's.
            $add($settlement->from_user, self::toSen($settlement->amount));
            $add($settlement->to_user, -self::toSen($settlement->amount));
        }

        return $balances;
    }

    /**
     * Greedy debt simplification: repeatedly match the largest debtor with the
     * largest creditor. This settles everyone in at most (n - 1) transfers.
     *
     * @param  array<int, int>  $balances  user id => balance in sen
     * @return list<array{from:int, to:int, amount:int}> amounts in sen
     */
    public function settlements(array $balances): array
    {
        $debtors = [];
        $creditors = [];

        foreach ($balances as $userId => $sen) {
            if ($sen < 0) {
                $debtors[] = ['id' => $userId, 'amount' => -$sen];
            } elseif ($sen > 0) {
                $creditors[] = ['id' => $userId, 'amount' => $sen];
            }
        }

        usort($debtors, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        $transfers = [];
        $d = 0;
        $c = 0;

        while ($d < count($debtors) && $c < count($creditors)) {
            $amount = min($debtors[$d]['amount'], $creditors[$c]['amount']);

            $transfers[] = [
                'from' => $debtors[$d]['id'],
                'to' => $creditors[$c]['id'],
                'amount' => $amount,
            ];

            $debtors[$d]['amount'] -= $amount;
            $creditors[$c]['amount'] -= $amount;

            if ($debtors[$d]['amount'] === 0) {
                $d++;
            }
            if ($creditors[$c]['amount'] === 0) {
                $c++;
            }
        }

        return $transfers;
    }

    /**
     * Split an amount equally between people. Leftover sen go to the
     * first people in the list, so the shares always add up exactly.
     *
     * @param  list<int>  $userIds
     * @return array<int, int> user id => share in sen
     */
    public function equalShares(int $totalSen, array $userIds): array
    {
        $count = count($userIds);
        $base = intdiv($totalSen, $count);
        $remainder = $totalSen - $base * $count;

        $shares = [];
        foreach (array_values($userIds) as $i => $userId) {
            $shares[$userId] = $base + ($i < $remainder ? 1 : 0);
        }

        return $shares;
    }

    public static function toSen(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function toRinggit(int $sen): string
    {
        return number_format($sen / 100, 2, '.', '');
    }
}
