<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting current user profile.
     *
     * @return void
     */
    public function test_user_can_get_own_profile()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create and login user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'name',
                    'email',
                    'role',
                    'updated_at',
                    'created_at',
                    'id'
                ]
            ]);
    }

    /**
     * Test updating user profile.
     *
     * @return void
     */
    public function test_user_can_update_own_profile()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create and login user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '1234567890',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/me', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '1234567890',
        ]);
    }

    /**
     * Test getting teachers list.
     *
     * @return void
     */
    public function test_user_can_get_teachers_list()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create teachers
        $teachers = User::factory()->count(3)->create([
            'role' => 'guru',
            'email_verified_at' => now(),
        ]);

        // Create and login user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/teachers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test getting students list.
     *
     * @return void
     */
    public function test_user_can_get_students_list()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create students
        $students = User::factory()->count(3)->create([
            'role' => 'murid',
            'email_verified_at' => now(),
        ]);

        // Create and login user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test unauthorized access to protected endpoints.
     *
     * @return void
     */
    public function test_unauthorized_access_to_protected_endpoints()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
} 