<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitMateTest extends TestCase
{
    use RefreshDatabase;

    private function groupWith(int $count): array
    {
        $users = User::factory()->count($count)->create();
        $group = Group::create(['name' => 'House', 'created_by' => $users[0]->id]);
        $group->members()->attach($users->pluck('id'));

        return [$group, $users];
    }

    public function test_equal_split_shares_add_up_exactly(): void
    {
        $shares = (new BalanceCalculator)->equalShares(10000, [1, 2, 3]);

        $this->assertSame([1 => 3334, 2 => 3333, 3 => 3333], $shares);
        $this->assertSame(10000, array_sum($shares));
    }

    public function test_settlements_use_fewest_transfers(): void
    {
        // A is owed 90, B owes 30, C owes 60
        $transfers = (new BalanceCalculator)->settlements([1 => 9000, 2 => -3000, 3 => -6000]);

        $this->assertSame([
            ['from' => 3, 'to' => 1, 'amount' => 6000],
            ['from' => 2, 'to' => 1, 'amount' => 3000],
        ], $transfers);
    }

    public function test_full_flow_create_group_add_expense_and_settle(): void
    {
        [$group, $u] = $this->groupWith(3);

        $this->actingAs($u[0])->post(route('expenses.store', $group), [
            'description' => 'Groceries',
            'amount' => '90.00',
            'paid_by' => $u[0]->id,
            'expense_date' => now()->toDateString(),
            'split_type' => 'equal',
            'participants' => $u->pluck('id')->all(),
        ])->assertRedirect(route('groups.show', $group));

        $balances = (new BalanceCalculator)->balances($group->fresh());
        $this->assertSame([$u[0]->id => 6000, $u[1]->id => -3000, $u[2]->id => -3000], $balances);

        $this->actingAs($u[1])->post(route('settlements.store', $group), [
            'from_user' => $u[1]->id, 'to_user' => $u[0]->id, 'amount' => '30.00',
        ])->assertSessionHasNoErrors();

        $balances = (new BalanceCalculator)->balances($group->fresh());
        $this->assertSame(0, $balances[$u[1]->id]);
        $this->assertSame(3000, $balances[$u[0]->id]);

        $this->actingAs($u[0])->get(route('groups.show', $group))->assertOk()->assertSee('Groceries');
        $this->actingAs($u[0])->get(route('dashboard'))->assertOk()->assertSee('House');
    }

    public function test_custom_split_must_match_total(): void
    {
        [$group, $u] = $this->groupWith(2);

        $this->actingAs($u[0])->post(route('expenses.store', $group), [
            'description' => 'Dinner',
            'amount' => '50.00',
            'paid_by' => $u[0]->id,
            'expense_date' => now()->toDateString(),
            'split_type' => 'custom',
            'custom' => [$u[0]->id => '20.00', $u[1]->id => '20.00'],
        ])->assertSessionHasErrors('custom');

        $this->assertSame(0, Expense::count());
    }

    public function test_expense_can_be_edited_and_deleted(): void
    {
        [$group, $u] = $this->groupWith(2);
        $expense = $group->expenses()->create([
            'description' => 'Old', 'amount' => 10, 'paid_by' => $u[0]->id, 'split_type' => 'equal', 'expense_date' => now(),
        ]);

        $this->actingAs($u[1])->put(route('expenses.update', $expense), [
            'description' => 'New', 'amount' => '40', 'paid_by' => $u[1]->id, 'expense_date' => now()->toDateString(),
            'split_type' => 'custom', 'custom' => [$u[0]->id => '40'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('New', $expense->fresh()->description);
        $this->assertSame(1, $expense->splits()->count());

        $this->actingAs($u[0])->delete(route('expenses.destroy', $expense))->assertRedirect();
        $this->assertModelMissing($expense);
    }

    public function test_outsiders_cannot_see_or_change_a_group(): void
    {
        [$group] = $this->groupWith(2);
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('groups.show', $group))->assertForbidden();
        $this->actingAs($outsider)->delete(route('groups.destroy', $group))->assertForbidden();
    }

    public function test_member_with_balance_cannot_be_removed_but_settled_one_can(): void
    {
        [$group, $u] = $this->groupWith(3);
        $expense = $group->expenses()->create([
            'description' => 'Wifi', 'amount' => 20, 'paid_by' => $u[0]->id, 'split_type' => 'equal', 'expense_date' => now(),
        ]);
        $expense->splits()->create(['user_id' => $u[1]->id, 'amount' => 20]);

        $this->actingAs($u[0])->delete(route('members.destroy', [$group, $u[1]]))->assertSessionHasErrors('member');
        $this->actingAs($u[0])->delete(route('members.destroy', [$group, $u[2]]))->assertSessionHasNoErrors();

        $this->assertFalse($group->hasMember($u[2]));
    }

    public function test_add_member_by_email_and_owner_can_rename_and_delete(): void
    {
        [$group, $u] = $this->groupWith(1);
        $friend = User::factory()->create(['email' => 'friend@example.com']);

        $this->actingAs($u[0])->post(route('members.store', $group), ['email' => 'friend@example.com'])->assertSessionHasNoErrors();
        $this->assertTrue($group->hasMember($friend));

        $this->actingAs($friend)->patch(route('groups.update', $group), ['name' => 'Hacked'])->assertForbidden();
        $this->actingAs($u[0])->patch(route('groups.update', $group), ['name' => 'Renamed']);
        $this->assertSame('Renamed', $group->fresh()->name);

        $this->actingAs($u[0])->delete(route('groups.destroy', $group))->assertRedirect(route('dashboard'));
        $this->assertModelMissing($group);
    }
}
