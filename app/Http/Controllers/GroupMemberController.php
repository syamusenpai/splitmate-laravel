<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Http\Request;

class GroupMemberController extends Controller
{
    public function store(Request $request, Group $group)
    {
        $this->ensureMember($request, $group);

        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', strtolower(trim($data['email'])))->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No SplitMate account uses that email. Ask your friend to sign up first.'], 'member');
        }

        if ($group->hasMember($user)) {
            return back()->withErrors(['email' => "{$user->displayName()} is already in this group."], 'member');
        }

        $group->members()->attach($user->id);

        return back()->with('status', "{$user->displayName()} added to the group.");
    }

    public function destroy(Request $request, Group $group, User $user, BalanceCalculator $calculator)
    {
        $this->ensureMember($request, $group);

        $me = $request->user();
        $isOwner = (int) $group->created_by === $me->id;

        // Members may leave on their own; only the creator may remove someone else.
        abort_unless($isOwner || $user->id === $me->id, 403);

        if ($user->id === (int) $group->created_by) {
            return back()->withErrors(['member' => 'The group creator cannot leave. Delete the group instead.']);
        }

        $balance = $calculator->balances($group)[$user->id] ?? 0;

        if ($balance !== 0) {
            return back()->withErrors(['member' => "{$user->displayName()} still has an unsettled balance of RM".BalanceCalculator::toRinggit(abs($balance)).'. Settle up first.']);
        }

        $group->members()->detach($user->id);

        if ($user->id === $me->id) {
            return redirect()->route('dashboard')->with('status', "You left \"{$group->name}\".");
        }

        return back()->with('status', "{$user->displayName()} removed from the group.");
    }
}
