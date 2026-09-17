<?php

namespace Tests\Feature\Api\V1;

use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
        ]);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'is_active' => true,
        ]);
    }

    private function createNotification(
        User $user,
        array $overrides = [],
    ): Notification {
        return Notification::query()->create(array_merge([
            'user_id' => $user->id,
            'type' => 'system.test',
            'title' => 'Notifikasi Test',
            'message' => 'Pesan notifikasi test.',
            'data' => [
                'source' => 'phpunit',
            ],
            'sent_at' => now(),
        ], $overrides));
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('phpunit')->plainTextToken;
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this
            ->getJson('/api/v1/notifications')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_own_notifications(): void
    {
        $user = $this->createUser();

        $this->createNotification($user, [
            'title' => 'Notifikasi milik user',
        ]);

        $token = $this->tokenFor($user);

        $this
            ->withToken($token)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Notifikasi milik user');
    }

    public function test_user_cannot_view_another_users_notifications(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $notification = $this->createNotification($userB);

        $token = $this->tokenFor($userA);

        $this
            ->withToken($token)
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'notification',
            ]);
    }

    public function test_user_can_filter_unread_notifications(): void
    {
        $user = $this->createUser();

        $this->createNotification($user, [
            'title' => 'Belum dibaca',
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'title' => 'Sudah dibaca',
            'read_at' => now(),
        ]);

        $token = $this->tokenFor($user);

        $this
            ->withToken($token)
            ->getJson('/api/v1/notifications?unread_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Belum dibaca');
    }

    public function test_user_can_paginate_notifications(): void
    {
        $user = $this->createUser();

        $this->createNotification($user, [
            'title' => 'Notifikasi 1',
        ]);

        $this->createNotification($user, [
            'title' => 'Notifikasi 2',
        ]);

        $this->createNotification($user, [
            'title' => 'Notifikasi 3',
        ]);

        $token = $this->tokenFor($user);

        $this
            ->withToken($token)
            ->getJson('/api/v1/notifications?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_user_can_get_unread_count(): void
    {
        $user = $this->createUser();

        $this->createNotification($user, [
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'read_at' => now(),
        ]);

        $token = $this->tokenFor($user);

        $this
            ->withToken($token)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = $this->createUser();

        $notification = $this->createNotification($user, [
            'read_at' => null,
        ]);

        $token = $this->tokenFor($user);

        $this
            ->withToken($token)
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.read_at', fn ($value) => $value !== null);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull(
            Notification::query()
                ->findOrFail($notification->id)
                ->read_at
        );
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $user = $this->createUser();

        $this->createNotification($user, [
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'read_at' => null,
        ]);

        $this->createNotification($user, [
            'read_at' => now(),
        ]);

        $token = $this->tokenFor($user);

        $this
            ->withToken($token)
            ->postJson('/api/v1/notifications/mark-all-as-read')
            ->assertOk()
            ->assertJsonPath('data.updated_count', 2);

        $this->assertSame(
            0,
            Notification::query()
                ->where('user_id', $user->id)
                ->unread()
                ->count()
        );
    }

    public function test_user_cannot_mark_another_users_notifications_as_read(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $notification = $this->createNotification($userB, [
            'read_at' => null,
        ]);

        $token = $this->tokenFor($userA);

        $this
            ->withToken($token)
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'notification',
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $userB->id,
            'read_at' => null,
        ]);
    }

    public function test_mark_all_as_read_only_affects_authenticated_users_notifications(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $this->createNotification($userA, [
            'read_at' => null,
        ]);

        $notificationB = $this->createNotification($userB, [
            'read_at' => null,
        ]);

        $token = $this->tokenFor($userA);

        $this
            ->withToken($token)
            ->postJson('/api/v1/notifications/mark-all-as-read')
            ->assertOk()
            ->assertJsonPath('data.updated_count', 1);

        $this->assertNotNull(
            Notification::query()
                ->where('user_id', $userA->id)
                ->firstOrFail()
                ->read_at
        );

        $this->assertNull(
            Notification::query()
                ->findOrFail($notificationB->id)
                ->read_at
        );
    }
}
