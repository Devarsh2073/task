<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/tasks', [
                             'title' => 'Test Task',
                             'description' => 'Description',
                             'status' => 'pending',
                             'due_date' => now()->addDay()->toDateString(),
                             'tags' => ['tag1', 'tag2'],
                         ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Test Task']);
        $this->assertDatabaseHas('tags', ['name' => 'tag1']);
    }

    public function test_admin_can_view_all_tasks()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin, 'sanctum')
                         ->getJson('/api/tasks');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}