<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Notification extends Model
{
    use HasTranslations;

    protected $fillable = ['title', 'body', 'image', 'is_general'];

    public $translatable = ['title', 'body'];
    protected $casts = [
        'is_general' => 'boolean'
    ];
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }
}
