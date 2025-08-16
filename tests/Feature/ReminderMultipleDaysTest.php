<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Reminder;
use App\Models\Remedy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReminderMultipleDaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reminder_with_multiple_days()
    {
        $user = User::factory()->create();
        $remedy = Remedy::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/mobile/reminders', [
                'element_type' => 'remedy',
                'element_id' => $remedy->id,
                'days' => ['monday', 'wednesday', 'friday'],
                'time' => '09:00'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'days' => ['monday', 'wednesday', 'friday'],
                    'day_names' => 'Monday, Wednesday, Friday'
                ]
            ]);

        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'element_type' => 'remedy',
            'element_id' => $remedy->id,
            'days' => json_encode(['monday', 'wednesday', 'friday']),
            'time' => '09:00:00'
        ]);
    }

    public function test_can_create_reminder_for_all_days()
    {
        $user = User::factory()->create();
        $remedy = Remedy::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/mobile/reminders', [
                'element_type' => 'remedy',
                'element_id' => $remedy->id,
                'days' => null,
                'time' => '09:00'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'days' => null,
                    'day_names' => 'All Days'
                ]
            ]);
    }

    public function test_can_update_reminder_days()
    {
        $user = User::factory()->create();
        $remedy = Remedy::factory()->create();
        
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'element_type' => 'remedy',
            'element_id' => $remedy->id,
            'days' => ['monday'],
            'time' => '09:00',
            'is_active' => true
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/mobile/reminders/{$reminder->id}", [
                'days' => ['tuesday', 'thursday'],
                'time' => '10:00'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'days' => ['tuesday', 'thursday'],
                    'day_names' => 'Tuesday, Thursday'
                ]
            ]);
    }

    public function test_validation_rejects_invalid_days()
    {
        $user = User::factory()->create();
        $remedy = Remedy::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/mobile/reminders', [
                'element_type' => 'remedy',
                'element_id' => $remedy->id,
                'days' => ['invalid_day'],
                'time' => '09:00'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days.0']);
    }
} 