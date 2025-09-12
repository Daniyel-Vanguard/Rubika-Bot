<?php

namespace RubikaBot\Entities;

class Keypad
{
    private array $rows = [];
    private bool $resize_keyboard = true;
    private bool $on_time_keyboard = false;

    public static function make(): self
    {
        return new self();
    }

    public function addRow(KeypadRow $row): self
    {
        $this->rows[] = $row;
        return $this;
    }

    public function row(): KeypadRow
    {
        $row = new KeypadRow();
        $this->rows[] = $row;
        return $row;
    }

    public function setResize(bool $resize): self
    {
        $this->resize_keyboard = $resize;
        return $this;
    }

    public function setOnetime(bool $onetime): self
    {
        $this->on_time_keyboard = $onetime;
        return $this;
    }

    public function toArray(): array
    {
        $rowsArr = [];
        foreach ($this->rows as $row) {
            $rowsArr[] = $row->toArray();
        }
        return [
            'rows' => $rowsArr,
            'resize_keyboard' => $this->resize_keyboard,
            'on_time_keyboard' => $this->on_time_keyboard,
        ];
    }
}