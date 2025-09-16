<?php

namespace App\Libraries\Firebase;

use App\Models\FcmToken;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class FirebaseNotification
{
    private array $title = [];
    private array $body = [];
    private ?string $icon = null;
    private array $tokens = [];
    private Collection $users;
    private array $data = [];
    private string $projectId;
    private string $credentialsFilePath;
    private ?string $topic = null; // <-- جديد

    public function __construct(string $projectId, string $credentialsFilePath)
    {
        $this->projectId = $projectId;
        $this->credentialsFilePath = $credentialsFilePath;
        $this->users = collect();
    }

    public function setTitle(array $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setBody(array $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function setUsers(array|int $userIds, string $userClass): self
    {
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }
        $this->users = $userClass::whereIn('id', $userIds)->get();

        if ($this->users->isEmpty()) {
            $this->tokens = [];
            return $this;
        }

        $this->tokens = FcmToken::query()
            ->whereIn('tokenable_id', $this->users->pluck('id'))
            ->where('tokenable_type', $userClass)
            ->pluck('token')
            ->filter()
            ->unique()
            ->toArray();

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    public function push(): bool
    {
        AccessToken::initialize($this->credentialsFilePath, $this->projectId);
        $sent = false;

        if ($this->topic) {
            // إرسال إلى topic
            $payload = $this->buildTopicPayload($this->topic);
            $sent = $this->send($payload);
        } elseif (!empty($this->tokens)) {
            // إرسال إلى tokens
            foreach ($this->tokens as $token) {
                $payload = $this->buildTokenPayload($token);
                if ($this->send($payload)) {
                    $sent = true;
                } else {
                    Log::error('FirebaseNotification: Sending to token failed.', [
                        'token' => $token,
                        'payload' => $payload,
                    ]);
                }
            }
        } else {
            Log::warning('FirebaseNotification: No FCM tokens or topic found. Notification will still be saved.', [
                'title' => $this->title,
                'users' => $this->users->pluck('id'),
            ]);
        }

        $this->saveNotification();
        return $sent;
    }

    private function buildTokenPayload(string $token): array
    {
        return [
            'message' => array_merge($this->baseMessage(), [
                'token' => $token
            ])
        ];
    }

    private function buildTopicPayload(string $topic): array
    {
        return [
            'message' => array_merge($this->baseMessage(), [
                'topic' => $topic
            ])
        ];
    }

    private function baseMessage(): array
    {
        return [
            'notification' => [
                'title' => $this->title['ar'] ?? '',
                'body' => $this->body['ar'] ?? '',
            ],
            'data' => [
                'title_en' => $this->title['en'] ?? '',
                'body_en' => $this->body['en'] ?? '',
                'title_ar' => $this->title['ar'] ?? '',
                'body_ar' => $this->body['ar'] ?? '',
                'image' => $this->icon ?? '',
                'additional_data' => json_encode($this->data),
            ],
            'android' => [
                'notification' => [
                    'image' => $this->icon ?? '',
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'mutable-content' => 1
                    ]
                ],
                'fcm_options' => [
                    'image' => $this->icon ?? ''
                ]
            ],
        ];
    }

    private function send(array $payload): bool
    {
        $apiUrl = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';
        $accessToken = AccessToken::getToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($apiUrl, $payload);

        if ($response->failed()) {
            Log::error('FirebaseNotification: Failed to send notification.', [
                'status' => $response->status(),
                'response' => $response->json(),
                'payload' => $payload
            ]);
            return false;
        }
        return true;
    }

    private function saveNotification(): void
    {
        $notification = new Notification();
        $notification->setTranslations('title', $this->title);
        $notification->setTranslations('body', $this->body);
        $notification->is_general = $this->topic !== null;

        $notification->save();

        if (!$this->topic && $this->users->isNotEmpty()) {
            $userNotifications = $this->users->map(function ($user) use ($notification) {
                return [
                    'notification_id' => $notification->id,
                    'notifiable_id' => $user->id,
                    'notifiable_type' => get_class($user),
                ];
            })->toArray();

            UserNotification::insert($userNotifications);
        }
    }
}
