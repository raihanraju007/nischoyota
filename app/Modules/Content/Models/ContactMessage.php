<?php

namespace App\Modules\Content\Models;

use App\Modules\Account\Models\User;
use App\Modules\Content\Enums\ContactMessageStatus;
use App\Modules\Content\Enums\ContactSubject;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A storefront contact form message (F-020). user_id is set when the sender was signed in.
 */
#[Fillable(['user_id', 'name', 'phone', 'email', 'subject', 'message', 'status'])]
#[UseFactory(ContactMessageFactory::class)]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => ContactSubject::class,
            'status' => ContactMessageStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
