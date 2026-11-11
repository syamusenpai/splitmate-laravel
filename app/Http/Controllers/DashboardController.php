<?php

namespace App\Http\Controllers;

use App\Services\BalanceCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BalanceCalculator $calculator)
    {
        $user = $request->user();

        $groups = $user->groups()
            ->with(['members', 'expenses.splits', 'settlements'])
            ->latest('groups.created_at')
            ->get();

        $youOwe = 0;
        $youAreOwed = 0;

        foreach ($groups as $group) {
            $mine = $calculator->balances($group)[$user->id] ?? 0;
            $group->my_balance = $mine;
            $group->total_spent = $group->expenses->sum(fn ($e) => BalanceCalculator::toSen($e->amount));

            if ($mine < 0) {
                $youOwe += -$mine;
            } else {
                $youAreOwed += $mine;
            }
        }

        return view('dashboard', [
            'groups' => $groups,
            'youOwe' => $youOwe,
            'youAreOwed' => $youAreOwed,
            'net' => $youAreOwed - $youOwe,
        ]);
    }
}
