<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    class UserNotification extends Model
    {
        protected $fillable = ['notification_id', 'is_read', 'notifiable_id', 'notifiable_type'];

        protected $casts = [
            'is_read' => 'boolean',
        ];

        public function notifiable()
        {
            return $this->morphTo();
        }

        public function notification()
        {
            return $this->belongsTo(Notification::class);
        }
    }
