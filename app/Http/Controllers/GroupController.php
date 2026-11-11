<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\BalanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $group = DB::transaction(function () use ($data, $request) {
            $group = Group::create([
                'name' => trim($data['name']),
                'created_by' => $request->user()->id,
            ]);
            $group->members()->attach($request->user()->id);

            return $group;
        });

        return redirect()->route('groups.show', $group)->with('status', 'Group created. Add your friends to start splitting.');
    }

    public function show(Request $request, Group $group, BalanceCalculator $calculator)
    {
        $this->ensureMember($request, $group);

        $group->load([
            'members',
            'creator',
            'expenses' => fn ($q) => $q->latest('expense_date')->latest('id'),
            'expenses.payer',
            'expenses.splits.user',
            'settlements' => fn ($q) => $q->latest('settled_at'),
            'settlements.payer',
            'settlements.receiver',
        ]);

        $balances = $calculator->balances($group);
        $suggested = $calculator->settlements($balances);
        $members = $group->members->keyBy('id');

        return view('groups.show', [
            'group' => $group,
            'members' => $members,
            'balances' => $balances,
            'suggested' => $suggested,
            'isOwner' => (int) $group->created_by === $request->user()->id,
            'totalSpent' => $group->expenses->sum(fn ($e) => BalanceCalculator::toSen($e->amount)),
        ]);
    }

    public function update(Request $request, Group $group)
    {
        $this->ensureOwner($request, $group);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $group->update(['name' => trim($data['name'])]);

        return back()->with('status', 'Group renamed.');
    }

    public function destroy(Request $request, Group $group)
    {
        $this->ensureOwner($request, $group);

        // Expenses, splits, members and settlements are removed by cascading foreign keys.
        $group->delete();

        return redirect()->route('dashboard')->with('status', "Group \"{$group->name}\" deleted.");
    }
}
