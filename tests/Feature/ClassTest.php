<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\EducationLevel;
use App\Models\ClassSchedule;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\EducationLevelSeeder;

class ClassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed education levels before running tests
        $this->seed(EducationLevelSeeder::class);
    }

    /**
     * Test creating a new class.
     *
     * @return void
     */
    public function test_teacher_can_create_class()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a teacher
        $teacher = User::factory()->create([
            'role' => 'guru',
            'email_verified_at' => now(),
        ]);

        // Create a subject
        $subject = Subject::factory()->create();

        // Get an existing education level
        $educationLevel = EducationLevel::first();

        // Login as teacher
        $loginResponse = $this->postJson('/api/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $classData = [
            'name' => 'Test Class',
            'education_level_id' => $educationLevel->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'pending',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/class', $classData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'education_level_id',
                    'subject_id',
                    'teacher_id',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('classes', [
            'name' => 'Test Class',
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'education_level_id' => $educationLevel->id,
        ]);
    }

    /**
     * Test getting classes by user.
     *
     * @return void
     */
    public function test_user_can_get_own_classes()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a teacher
        $teacher = User::factory()->create([
            'role' => 'guru',
            'email_verified_at' => now(),
        ]);

        // Create classes for the teacher
        $classes = ClassModel::factory()->count(3)->create([
            'teacher_id' => $teacher->id,
        ]);

        // Login as teacher
        $loginResponse = $this->postJson('/api/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/class');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'education_level_id',
                        'subject_id',
                        'teacher_id',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test getting all classes.
     *
     * @return void
     */
    public function test_user_can_get_all_classes()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create some classes
        $classes = ClassModel::factory()->count(5)->create();

        // Login as user
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/class/all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'education_level_id',
                        'subject_id',
                        'teacher_id',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test getting upcoming classes.
     *
     * @return void
     */
    public function test_user_can_get_upcoming_classes()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create some classes
        $classes = ClassModel::factory()->count(3)->create();

        // Login as user
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/class/upcoming');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'education_level_id',
                        'subject_id',
                        'teacher_id',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test class creation validation.
     *
     * @return void
     */
    public function test_class_creation_validation()
    {
        // Suppress sending the verification email
        \Illuminate\Support\Facades\Notification::fake();

        // Create a teacher
        $teacher = User::factory()->create([
            'role' => 'guru',
            'email_verified_at' => now(),
        ]);

        // Login as teacher
        $loginResponse = $this->postJson('/api/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        // Try to create class without required fields
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/class', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'education_level_id', 'subject_id']);
    }
} 