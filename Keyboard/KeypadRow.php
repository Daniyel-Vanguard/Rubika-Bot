<?php

namespace RubikaBot\Keyboard;

class KeypadRow
{
    private array $buttons = [];

    public function add(Button $button): self
    {
        $this->buttons[] = $button;
        return $this;
    }

    public function toArray(): array
    {
        $arr = [];
        foreach ($this->buttons as $button) {
            $arr[] = $button->toArray();
        }
        return ['buttons' => $arr];
    }
}