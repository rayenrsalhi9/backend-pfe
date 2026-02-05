<?php

namespace App\Broadcasting;

use App\Models\Users;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Output\ConsoleOutput;

class conversationChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Models\Users  $user
     * @return array|bool
     */
    public function join(Users $user)
    {
        return true;
        /* return Auth::check() && (int) $user->id == Auth::id(); */
    }
}
