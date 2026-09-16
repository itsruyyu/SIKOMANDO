<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function create(
        User $recipient,
        string $type,
        string $title,
        string $message,
        ?string $entityType = null,
        ?string $entityId = null,
        array $data = [],
        ?string $requestId = null,
    ): Notification {
        if (trim($type) === '') {
            throw ValidationException::withMessages([
                'type' => ['Tipe notifikasi wajib diisi.'],
            ]);
        }

        if (trim($title) === '') {
            throw ValidationException::withMessages([
                'title' => ['Judul notifikasi wajib diisi.'],
            ]);
        }

        if (trim($message) === '') {
            throw ValidationException::withMessages([
                'message' => ['Pesan notifikasi wajib diisi.'],
            ]);
        }

        return DB::transaction(function () use (
            $recipient,
            $type,
            $title,
            $message,
            $entityType,
            $entityId,
            $data,
            $requestId,
        ): Notification {
            return Notification::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $recipient->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'data' => array_merge($data, [
                    'request_id' => $requestId,
                ]),
                'sent_at' => now(),
            ]);
        });
    }

    /**
     * @param  Collection<int, User>|array<int, User>  $recipients
     */
    public function createForMany(
        Collection|array $recipients,
        string $type,
        string $title,
        string $message,
        ?string $entityType = null,
        ?string $entityId = null,
        array $data = [],
        ?string $requestId = null,
    ): int {
        $created = 0;

        DB::transaction(function () use (
            $recipients,
            $type,
            $title,
            $message,
            $entityType,
            $entityId,
            $data,
            $requestId,
            &$created,
        ): void {
            foreach ($recipients as $recipient) {
                if (! $recipient instanceof User) {
                    continue;
                }

                $this->create(
                    recipient: $recipient,
                    type: $type,
                    title: $title,
                    message: $message,
                    entityType: $entityType,
                    entityId: $entityId,
                    data: $data,
                    requestId: $requestId,
                );

                $created++;
            }
        });

        return $created;
    }

    public function getForUser(
        User $user,
        int $perPage = 15,
        bool $unreadOnly = false,
    ): LengthAwarePaginator {
        $query = Notification::query()
            ->where('user_id', $user->id)
            ->latest('created_at');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->count();
    }

    public function markAsRead(
        Notification $notification,
        User $user,
    ): Notification {
        $this->assertOwner($notification, $user);

        $notification->markAsRead();

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function assertOwner(
        Notification $notification,
        User $user,
    ): void {
        if ($notification->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'notification' => [
                    'Notifikasi tidak dapat diakses oleh pengguna lain.',
                ],
            ]);
        }
    }
}
