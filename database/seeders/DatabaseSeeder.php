<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data for presentations. Every demo account uses the password "password".
     */
    public function run(): void
    {
        $make = fn (string $name, string $email) => User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $hisyam = $make('Hisyam Rosli', 'hisyam@splitmate.test');
        $aina = $make('Aina Sofea', 'aina@splitmate.test');
        $ben = $make('Ben Tan', 'ben@splitmate.test');
        $chong = $make('Chong Wei', 'chong@splitmate.test');

        $calc = new BalanceCalculator;

        $addExpense = function (Group $group, string $desc, float $amount, User $payer, array $people, string $date, ?array $custom = null) use ($calc) {
            $expense = $group->expenses()->create([
                'description' => $desc,
                'amount' => $amount,
                'paid_by' => $payer->id,
                'split_type' => $custom ? 'custom' : 'equal',
                'expense_date' => $date,
            ]);

            $shares = $custom
                ? collect($custom)->mapWithKeys(fn ($rm, $id) => [$id => BalanceCalculator::toSen($rm)])->all()
                : $calc->equalShares(BalanceCalculator::toSen($amount), array_map(fn ($u) => $u->id, $people));

            foreach ($shares as $userId => $sen) {
                $expense->splits()->create(['user_id' => $userId, 'amount' => BalanceCalculator::toRinggit($sen)]);
            }
        };

        // Group 1: a shared rented house
        $house = Group::create(['name' => 'Rumah Sewa Seksyen 7', 'created_by' => $hisyam->id]);
        $house->members()->attach([$hisyam->id, $aina->id, $ben->id, $chong->id]);

        $everyone = [$hisyam, $aina, $ben, $chong];
        $addExpense($house, 'Electricity bill (TNB)', 120.00, $hisyam, $everyone, '2026-11-01');
        $addExpense($house, 'Unifi Wi-Fi', 99.00, $ben, $everyone, '2026-11-02');
        $addExpense($house, 'Groceries at Lotus\'s', 54.00, $aina, [$hisyam, $aina, $chong], '2026-11-05');
        $addExpense($house, 'Dinner at Nasi Kandar', 86.00, $chong, $everyone, '2026-11-07', [
            $hisyam->id => 30.00, $aina->id => 20.00, $ben->id => 18.00, $chong->id => 18.00,
        ]);

        $house->settlements()->create([
            'from_user' => $aina->id, 'to_user' => $hisyam->id, 'amount' => 20.00, 'settled_at' => '2026-11-08 21:15:00',
        ]);

        // Group 2: a weekend trip
        $trip = Group::create(['name' => 'Trip Cameron Highlands', 'created_by' => $aina->id]);
        $trip->members()->attach([$aina->id, $hisyam->id, $ben->id]);
        $addExpense($trip, 'Homestay (2 nights)', 360.00, $aina, [$aina, $hisyam, $ben], '2026-10-18');
        $addExpense($trip, 'Petrol and toll', 95.40, $hisyam, [$aina, $hisyam, $ben], '2026-10-18');
    }
}
