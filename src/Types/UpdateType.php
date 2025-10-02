<?php

namespace RubikaBot\Types;

class UpdateType
{
    public const MESSAGE = 'UpdateNewMessage';
    public const EDIT_MESSAGE = 'UpdateEditMessage';
    public const DELETE_MESSAGE = 'UpdateDeleteMessage';
    public const CALLBACK_QUERY = 'UpdateCallbackQuery';
    public const INLINE_QUERY = 'UpdateInlineQuery';
    public const CHAT_JOIN = 'UpdateChatJoin';
    public const CHAT_LEAVE = 'UpdateChatLeave';
}
