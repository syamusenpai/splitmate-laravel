<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
    }

    private function fakeGoogle(string $id, string $email, string $name = 'Aina Sofea'): void
    {
        $user = (new GoogleUser)->map(['id' => $id, 'email' => $email, 'name' => $name]);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_button_shows_only_when_google_is_configured(): void
    {
        $this->get('/login')->assertSee('Continue with Google');
        $this->get('/register')->assertSee('Sign up with Google');

        config(['services.google.client_id' => null]);
        $this->get('/login')->assertDontSee('Continue with Google');
        $this->get('/auth/google')->assertNotFound();
    }

    public function test_new_google_user_is_registered_and_logged_in(): void
    {
        $this->fakeGoogle('g-123', 'Aina@Gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $user = User::where('email', 'aina@gmail.com')->firstOrFail();
        $this->assertSame('g-123', $user->google_id);
        $this->assertSame('Aina Sofea', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_email_account_is_linked_not_duplicated(): void
    {
        $existing = User::factory()->create(['email' => 'ben@gmail.com']);
        $this->fakeGoogle('g-456', 'ben@gmail.com', 'Ben Tan');

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::count());
        $this->assertSame('g-456', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_cancelled_google_login_returns_to_login_page(): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andThrow(new \RuntimeException('denied'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
