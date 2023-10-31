<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat', function ($user) {
    return Auth::check();
});

Broadcast::channel('privatechat.{receiverId}', function ($user, $receiverId) {
    return Auth::check();
});

Broadcast::channel('activeUser', function ($user) {
    if(Auth::check())
    {
        return $user;
    }
});

Broadcast::channel('request.{receiverId}', function ($user, $receiverId) {
    return Auth::check();
});

Broadcast::channel('requestAccept.{receiverId}', function ($user, $receiverId) {
    return Auth::check();
});

Broadcast::channel('deleteFriend.{receiverId}', function ($user, $receiverId) {
    return Auth::check();
});
