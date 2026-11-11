<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Stop anyone who is not in the group from seeing or changing it. */
    protected function ensureMember(Request $request, Group $group): void
    {
        abort_unless($group->hasMember($request->user()), 403, 'You are not a member of this group.');
    }

    /** Only the person who created the group may rename or delete it. */
    protected function ensureOwner(Request $request, Group $group): void
    {
        abort_unless((int) $group->created_by === $request->user()->id, 403, 'Only the group creator can do this.');
    }
}
