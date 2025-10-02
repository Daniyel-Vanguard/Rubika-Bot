<?php

namespace RubikaBot\Types;

enum ChatType: string
{
    case USER = 'User';
    case GROUP = 'Group';
    case CHANNEL = 'Channel';
    case BOT = 'Bot';
}
