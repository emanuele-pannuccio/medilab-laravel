<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('doctors.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});