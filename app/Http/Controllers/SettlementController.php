<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettlementController extends Controller
{
    public function store(Request $request, Group $group)
    {
        $this->ensureMember($request, $group);

        $memberIds = $group->members()->pluck('users.id')->all();

        $data = $request->validate([
            'from_user' => ['required', 'integer', Rule::in($memberIds)],
            'to_user' => ['required', 'integer', 'different:from_user', Rule::in($memberIds)],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
        ]);

        // Only the person paying or the person receiving may record a payment.
        $me = $request->user()->id;
        abort_unless(in_array($me, [(int) $data['from_user'], (int) $data['to_user']], true), 403, 'Only the payer or the receiver can record this payment.');

        $group->settlements()->create($data + ['settled_at' => now()]);

        $from = User::find($data['from_user'])->displayName();
        $to = User::find($data['to_user'])->displayName();

        return back()->with('status', "Recorded: {$from} paid {$to} RM".BalanceCalculator::toRinggit(BalanceCalculator::toSen($data['amount'])).'.');
    }

    public function destroy(Request $request, Settlement $settlement)
    {
        $this->ensureMember($request, $settlement->group);

        $settlement->delete();

        return back()->with('status', 'Payment record removed. Balances updated.');
    }
}
