<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int|string $id): bool {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}.services', function (User $user, int|string $id): bool {
    return (int) $user->id === (int) $id;
});
