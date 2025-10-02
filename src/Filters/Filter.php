<?php

namespace RubikaBot\Filters;

use RubikaBot\Bot;

class Filter
{
    private $conditions = [];
    private $operator = '&&';
    private bool $isSpamHandler = false;

    public function __construct($condition = null)
    {
        if ($condition !== null) {
            $this->conditions[] = $condition;
        }
    }

    public static function make($condition = null): self
    {
        return new self($condition);
    }

    public function __invoke(Bot $bot): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        if ($this->operator === '&&') {
            foreach ($this->conditions as $condition) {
                if (!$condition($bot)) {
                    return false;
                }
            }
            return true;
        } else {
            foreach ($this->conditions as $condition) {
                if ($condition($bot)) {
                    return true;
                }
            }
            return false;
        }
    }

    public function and($condition): self
    {
        $newFilter = new self();
        $newFilter->conditions = array_merge($this->conditions, [$condition]);
        $newFilter->operator = '&&';
        return $newFilter;
    }

    public function or($condition): self
    {
        $newFilter = new self();
        $newFilter->conditions = array_merge($this->conditions, [$condition]);
        $newFilter->operator = '||';
        return $newFilter;
    }

    public function __toString()
    {
        $parts = [];
        foreach ($this->conditions as $condition) {
            $parts[] = (string)$condition;
        }
        return implode(" {$this->operator} ", $parts);
    }

    public function markAsSpamHandler(): self
    {
        $this->isSpamHandler = true;
        return $this;
    }

    public function isSpamHandler(): bool
    {
        return $this->isSpamHandler;
    }
}
