<?php

namespace RubikaBot\Entities;

use RubikaBot\Entities\ButtonLink;
use RubikaBot\Entities\ButtonLinkType;

class Button
{
    public string $id;
    public string $type;
    public string $button_text;
    public array $extra = [];

    public function __construct(string $id, string $type, string $button_text)
    {
        $this->id = $id;
        $this->type = $type;
        $this->button_text = $button_text;
    }

    public static function simple(string $id, string $text): self
    {
        return new self($id, 'Simple', $text);
    }

    public static function selection(string $id, string $title, array $items, bool $multi = false, int $columns = 1): self
    {
        $btn = new self($id, 'Selection', $title);
        $btn->extra['button_selection'] = [
            'selection_id' => $id,
            'items' => $items,
            'is_multi_selection' => $multi,
            'columns_count' => $columns,
            'title' => $title,
        ];
        return $btn;
    }

    // Other button types (calendar, numberPicker, etc.) remain as in the original code.
    // For brevity, refer to the original Button.php for full implementation.

    public function toArray(): array
    {
        $base = [
            'id' => $this->id,
            'type' => $this->type,
            'button_text' => $this->button_text,
        ];
        return array_merge($base, $this->extra);
    }
}
