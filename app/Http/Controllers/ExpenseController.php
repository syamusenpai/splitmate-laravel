<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Services\BalanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function __construct(private BalanceCalculator $calculator) {}

    public function store(Request $request, Group $group)
    {
        $this->ensureMember($request, $group);

        [$data, $shares] = $this->validateExpense($request, $group);

        DB::transaction(function () use ($group, $data, $shares) {
            $expense = $group->expenses()->create($data);
            $this->saveSplits($expense, $shares);
        });

        return redirect()->route('groups.show', $group)->with('status', "Expense \"{$data['description']}\" added.");
    }

    public function edit(Request $request, Expense $expense)
    {
        $group = $expense->group;
        $this->ensureMember($request, $group);

        $expense->load('splits');

        return view('expenses.edit', [
            'expense' => $expense,
            'group' => $group->load('members'),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $group = $expense->group;
        $this->ensureMember($request, $group);

        [$data, $shares] = $this->validateExpense($request, $group);

        DB::transaction(function () use ($expense, $data, $shares) {
            $expense->update($data);
            $expense->splits()->delete();
            $this->saveSplits($expense, $shares);
        });

        return redirect()->route('groups.show', $group)->with('status', 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $group = $expense->group;
        $this->ensureMember($request, $group);

        $expense->delete();

        return redirect()->route('groups.show', $group)->with('status', "Expense \"{$expense->description}\" deleted.");
    }

    /**
     * Validate the form and work out each person's share in sen.
     *
     * @return array{0: array<string, mixed>, 1: array<int, int>}
     */
    private function validateExpense(Request $request, Group $group): array
    {
        $memberIds = $group->members()->pluck('users.id')->all();

        $data = $request->validate([
            'description' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'paid_by' => ['required', 'integer', Rule::in($memberIds)],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'split_type' => ['required', Rule::in(['equal', 'custom'])],
            'participants' => ['required_if:split_type,equal', 'array'],
            'participants.*' => ['integer', Rule::in($memberIds)],
            'custom' => ['required_if:split_type,custom', 'array'],
            'custom.*' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ], [
            'participants.required_if' => 'Choose at least one person to share this expense.',
            'expense_date.before_or_equal' => 'The date cannot be in the future.',
        ]);

        $totalSen = BalanceCalculator::toSen($data['amount']);

        if ($data['split_type'] === 'equal') {
            $people = array_values(array_unique(array_map('intval', $data['participants'] ?? [])));
            if ($people === []) {
                throw ValidationException::withMessages(['participants' => 'Choose at least one person to share this expense.']);
            }
            $shares = $this->calculator->equalShares($totalSen, $people);
        } else {
            $shares = [];
            foreach ($data['custom'] ?? [] as $userId => $value) {
                $sen = BalanceCalculator::toSen($value ?? 0);
                if ($sen > 0 && in_array((int) $userId, $memberIds, true)) {
                    $shares[(int) $userId] = $sen;
                }
            }

            $sum = array_sum($shares);
            if ($sum !== $totalSen) {
                throw ValidationException::withMessages([
                    'custom' => 'Custom amounts add up to RM'.BalanceCalculator::toRinggit($sum)
                        .' but the expense is RM'.BalanceCalculator::toRinggit($totalSen).'.',
                ]);
            }
        }

        $data['description'] = trim($data['description']);
        unset($data['participants'], $data['custom']);

        return [$data, $shares];
    }

    /** @param array<int, int> $shares */
    private function saveSplits(Expense $expense, array $shares): void
    {
        foreach ($shares as $userId => $sen) {
            $expense->splits()->create([
                'user_id' => $userId,
                'amount' => BalanceCalculator::toRinggit($sen),
            ]);
        }
    }
}
