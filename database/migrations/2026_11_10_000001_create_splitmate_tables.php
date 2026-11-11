<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->unique()->after('name');
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->primary(['group_id', 'user_id']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('description', 120);
            $table->decimal('amount', 10, 2);
            $table->foreignId('paid_by')->constrained('users')->cascadeOnDelete();
            $table->enum('split_type', ['equal', 'custom'])->default('equal');
            $table->date('expense_date');
            $table->timestamps();
        });

        Schema::create('expense_splits', function (Blueprint $table) {
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->primary(['expense_id', 'user_id']);
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamp('settled_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('expense_splits');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
