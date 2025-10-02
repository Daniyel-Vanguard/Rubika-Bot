<?php

namespace RubikaBot\Keyboard;

use RubikaBot\Keyboard\ButtonLink;

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

    public static function calendar(string $id, string $title, string $calendarType, ?string $min = '1360', ?string $max = '1404'): self
    {
        $btn = new self($id, 'Calendar', $title);
        $btn->extra['button_calendar'] = [
            'type' => $calendarType,
            'min_year' => $min,
            'max_year' => $max,
            'title' => $title,
        ];
        return $btn;
    }

    public static function numberPicker(string $id, string $title, int $min, int $max, ?int $default = null): self
    {
        $btn = new self($id, 'NumberPicker', $title);
        $btn->extra['button_number_picker'] = [
            'min_value' => $min,
            'max_value' => $max,
            'default_value' => $default,
            'title' => $title,
        ];
        return $btn;
    }

    public static function stringPicker(string $id, string $title, array $items, ?string $default = null): self
    {
        $btn = new self($id, 'StringPicker', $title);
        $btn->extra['button_string_picker'] = [
            'items' => $items,
            'default_value' => $default,
            'title' => $title,
        ];
        return $btn;
    }

    public static function location(string $id, string $title, string $type = 'Picker'): self
    {
        $btn = new self($id, 'Location', $title);
        $btn->extra['button_location'] = [
            'type' => $type,
            'title' => $title,
        ];
        return $btn;
    }

    public static function payment(string $id, string $title): self
    {
        return new self($id, 'Payment', $title);
    }

    public static function cameraImage(string $id, string $title): self
    {
        return new self($id, 'CameraImage', $title);
    }

    public static function cameraVideo(string $id, string $title): self
    {
        return new self($id, 'CameraVideo', $title);
    }

    public static function galleryImage(string $id, string $title): self
    {
        return new self($id, 'GalleryImage', $title);
    }

    public static function galleryVideo(string $id, string $title): self
    {
        return new self($id, 'GalleryVideo', $title);
    }

    public static function file(string $id, string $title): self
    {
        return new self($id, 'File', $title);
    }

    public static function audio(string $id, string $title): self
    {
        return new self($id, 'Audio', $title);
    }

    public static function recordAudio(string $id, string $title): self
    {
        return new self($id, 'RecordAudio', $title);
    }

    public static function myPhoneNumber(string $id, string $title): self
    {
        return new self($id, 'MyPhoneNumber', $title);
    }

    public static function myLocation(string $id, string $title): self
    {
        return new self($id, 'MyLocation', $title);
    }

    public static function textBox(string $id, string $title, string $lineType = 'SingleLine', string $keypadType = 'String'): self
    {
        $btn = new self($id, 'TextBox', $title);
        $btn->extra['button_textbox'] = [
            'type_line' => $lineType,
            'type_keypad' => $keypadType,
            'title' => $title,
        ];
        return $btn;
    }

    public static function link(string $id, string $title, string $type, ButtonLink $link): self
    {
        $btn = new self($id, 'Link', $title);
        if ($type === \RubikaBot\Types\ButtonLinkType::URL) {
            $btn->extra['button_link'] = [
                'type' => $type,
                'link_url' => $link->link_url
            ];
        } elseif ($type === \RubikaBot\Types\ButtonLinkType::JoinChannel) {
            $btn->extra['button_link'] = [
                'type' => $type,
                'joinchannel_data' => $link->joinchannel_data ? [
                    'username' => $link->joinchannel_data->username,
                    'ask_join' => $link->joinchannel_data->ask_join
                ] : null
            ];
        }
        return $btn;
    }

    public static function activityPhoneNumber(string $id, string $title): self
    {
        return new self($id, 'ActivityPhoneNumber', $title);
    }

    public static function asMLocation(string $id, string $title): self
    {
        return new self($id, 'AsMLocation', $title);
    }

    public static function barcode(string $id, string $title): self
    {
        return new self($id, 'Barcode', $title);
    }

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
