<?php

namespace RubikaBot\Metadata;

class Utils {
    public static function Bold($text){
        return "**$text**";
    }

    public static function Hyperlink($text, $link){
        return "[" . $text . "](" . trim($link) . ")";
    }

    public static function Italic($text){
        return "__" . $text . "__";
    }

    public static function Underline($text){
        return "--" . $text . "--";
    }

    public static function Mono($text){
        return "`" . $text . "`";
    }

    public static function Strike($text){
        return "~~" . $text . "~~";
    }

    public static function Spoiler($text){
        return "||" . $text . "||";
    }

    public static function Code($text){
        return "```" . $text . "```";
    }

    public static function Quote($text){
        return "##" . $text . "##";
    }
}
