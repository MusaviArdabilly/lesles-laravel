<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration.
     *
     * @return void
     */
    public function test_user_can_register()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        $userData = [
            "name" => "Test User",
            "email" => "test@example.com",
            "password" => "password",
            "password_confirmation" => "password",
            "role" => "murid",
            "phone" => "1234567890"
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'name',
                        'email',
                        'role',
                        'phone',
                        'updated_at',
                        'created_at',
                        'id'
                    ],
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test user login with valid credentials.
     *
     * @return void
     */
    public function test_user_can_login_with_valid_credentials()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(), // Mark as verified for login
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'name',
                        'email',
                        'role',
                        'updated_at',
                        'created_at',
                        'id'
                    ],
                    'token'
                ]
            ]);
    }

    /**
     * Test user login with invalid credentials.
     *
     * @return void
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password',
            ]);
    }

    /**
     * Test user logout.
     *
     * @return void
     */
    public function test_user_can_logout()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Login to get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
    }

    /**
     * Test registration validation errors.
     *
     * @return void
     */
    public function test_registration_validation_errors()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test registration with duplicate email.
     *
     * @return void
     */
    public function test_registration_with_duplicate_email()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create first user
        User::factory()->create(['email' => 'test@example.com']);

        // Try to register with same email
        $userData = [
            "name" => "Test User 2",
            "email" => "test@example.com",
            "password" => "password",
            "password_confirmation" => "password",
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
} 