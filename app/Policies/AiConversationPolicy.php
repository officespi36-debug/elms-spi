<?php

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

class AiConversationPolicy
{
    public function view(User $user, AiConversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $user->id;
    }

    public function update(User $user, AiConversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $user->id;
    }

    public function delete(User $user, AiConversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $user->id;
    }
}
