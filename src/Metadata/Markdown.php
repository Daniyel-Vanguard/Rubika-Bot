<?php

namespace RubikaBot\Metadata;

class Markdown {
    private static $pattern = '/
        (?P<pre>```(?P<pre_c>[\s\S]*?)```)|
        (?P<bold>\*\*(?P<bold_c>.*?)\*\*)|
        (?P<mono>`(?P<mono_c>.*?)`)|
        (?P<italic>__(?P<italic_c>.*?)__)|
        (?P<underline>--(?P<underline_c>.*?)--)|
        (?P<link>\[(?P<link_text>.*?)\]\((?P<link_url>\S+?)\))|
        (?P<quote>\#\#(?P<quote_c>[\s\S]*?)\#\#)|
        (?P<strike>~~(?P<strike_c>.*?)~~)|
        (?P<spoiler>\|\|(?P<spoiler_c>.*?)\|\|)
    /x';

    private static $typeMap = [
        "pre" => "Pre",
        "bold" => "Bold",
        "mono" => "Mono",
        "italic" => "Italic",
        "underline" => "Underline",
        "strike" => "Strike",
        "spoiler" => "Spoiler",
        "quote" => "Quote",
        "link" => "Link",
    ];

    private static function utf16Len($str) {
        return strlen(mb_convert_encoding($str, 'UTF-16BE', 'UTF-8')) / 2;
    }

    public function markdown_pre($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            (?<!`)`(?!`)           |   # حذف ` که بخشی از ``` نیست
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            \#\#([\s\S]*?)\#\#         # حذف جفت‌های ##...##
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["pre"][0])) continue;
            $gname = "pre";

            preg_match('/```([\s\S]*?)```/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_pre($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/```([\s\S]*?)```/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_bold($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            `([^`]*)`              |   # حذف جفت‌های `...`
            ```([\s\S]*?)```       |   # حذف جفت‌های ```...``` چندخطی
            \#\#([\s\S]*?)\#\#         # حذف جفت‌های ##...##
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["bold"][0])) continue;
            $gname = "bold";

            preg_match('/\*\*([\s\S]*?)\*\*/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_bold($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/\*\*([\s\S]*?)\*\*/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_mono($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```       |   # حذف جفت‌های ```...``` چندخطی
            \#\#([\s\S]*?)\#\#         # حذف جفت‌های ##...##
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["mono"][0])) continue;
            $gname = "mono";

            $rawInner = $m["mono_c"][0] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_mono($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/`([\s\S]*?)`/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_italic($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            \#\#([\s\S]*?)\#\#     |   # حذف جفت‌های ##...##
            `([^`]*)`              |   # حذف جفت‌های `...`
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```           # حذف جفت‌های ```...```
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["italic"][0])) continue;
            $gname = "italic";

            preg_match('/__([\s\S]*?)__/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_italic($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/__([\s\S]*?)__/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_underline($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            \#\#([\s\S]*?)\#\#     |   # حذف جفت‌های ##...##
            `([^`]*)`              |   # حذف جفت‌های `...`
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```           # حذف جفت‌های ```...```
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["underline"][0])) continue;
            $gname = "underline";

            preg_match('/--([\s\S]*?)--/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_underline($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/--([\s\S]*?)--/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_strike($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            \#\#([\s\S]*?)\#\#     |   # حذف جفت‌های ##...##
            `([^`]*)`              |   # حذف جفت‌های `...`
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```           # حذف جفت‌های ```...```
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["strike"][0])) continue;
            $gname = "strike";

            preg_match('/~~([\s\S]*?)~~/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_strike($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/~~([\s\S]*?)~~/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_spoiler($src) {
        $payloadParts = [];

        $pattern_strip = '/
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            \#\#([\s\S]*?)\#\#     |   # حذف جفت‌های ##...##
            `([^`]*)`              |   # حذف جفت‌های `...`
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```           # حذف جفت‌های ```...```
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["spoiler"][0])) continue;
            $gname = "spoiler";

            preg_match('/\|\|([\s\S]*?)\|\|/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_spoiler($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/\|\|([\s\S]*?)\|\|/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_link($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            \#\#([\s\S]*?)\#\#     |   # حذف جفت‌های ##...##
            `([^`]*)`              |   # حذف جفت‌های `...`
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```           # حذف جفت‌های ```...```
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7$8', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["link"][0])) continue;
            $gname = "link";

            $inner = $m["link_text"][0] ?? '';
            $linkHref = $m["link_url"][0] ?? '';
            if ($inner === '') continue;

            $innerMeta = $this->markdown_link($inner);
            $innerText = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($inner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
                "link_url" => $linkHref,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $innerText . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($innerText);
            $charOffset += mb_strlen($whole) - mb_strlen($innerText);
        }

        $finalText = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_quote($src) {
        $payloadParts = [];

        $pattern_strip = '/
            \|\|([\s\S]*?)\|\|     |   # حذف جفت‌های ||...||
            ~~([\s\S]*?)~~         |   # حذف جفت‌های ~~...~~
            --([\s\S]*?)--         |   # حذف جفت‌های --...--
            __([\s\S]*?)__         |   # حذف جفت‌های __...__ 
            `([^`]*)`              |   # حذف جفت‌های `...`
            \*\*([\s\S]*?)\*\*     |   # حذف جفت‌های **...**
            ```([\s\S]*?)```           # حذف جفت‌های ```...```
        /x';

        $cleanedSrc = $src;
        do {
            $newSrc = preg_replace($pattern_strip, '$1$2$3$4$5$6$7', $cleanedSrc);
            $changed = ($newSrc !== $cleanedSrc);
            $cleanedSrc = $newSrc;
        } while ($changed);

        $cleanedSrc = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $cleanedSrc);

        $normalizedText = $cleanedSrc;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $cleanedSrc, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + mb_strlen($whole);

            $adjFrom = self::utf16Len(substr($cleanedSrc, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            if (empty($m["quote"][0])) continue;
            $gname = "quote";

            preg_match('/\#\#([\s\S]*?)\#\#/', $whole, $innerMatch);
            $rawInner = $innerMatch[1] ?? '';
            if ($rawInner === '') continue;

            $innerMeta = $this->markdown_quote($rawInner);
            $inner = $innerMeta["text"];
            if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                    $part["from_index"] += $adjFrom;
                    $payloadParts[] = $part;
                }
            }

            $length_text = self::utf16Len($rawInner);

            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $length_text,
            ];
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - self::utf16Len($inner);
            $charOffset += mb_strlen($whole) - mb_strlen($inner);
        }

        $finalText = preg_replace('/\#\#([\s\S]*?)\#\#/', '$1', $src);

        $result = ["text" => $finalText];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }

        return $result;
    }

    public function markdown_mode($src) {
        $metadataParts = [];

        if (preg_match('/##.*?##/s', $src)) {
            $markdown_quote = $this->markdown_quote($src);
            $src = $markdown_quote['text'];
            if (!empty($markdown_quote['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_quote['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/```.*?```/s', $src)) {
            $markdown_pre = $this->markdown_pre($src);
            $src = $markdown_pre['text'];
            if (!empty($markdown_pre['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_pre['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/`.*?`/s', $src)) {
            $markdown_mono = $this->markdown_mono($src);
            $src = $markdown_mono['text'];
            if (!empty($markdown_mono['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_mono['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/__.*?__/s', $src)) {
            $markdown_italic = $this->markdown_italic($src);
            $src = $markdown_italic['text'];
            if (!empty($markdown_italic['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_italic['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/\*\*.*?\*\*/s', $src)) {
            $markdown_bold = $this->markdown_bold($src);
            $src = $markdown_bold['text'];
            if (!empty($markdown_bold['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_bold['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/--.*?--/s', $src)) {
            $markdown_underline = $this->markdown_underline($src);
            $src = $markdown_underline['text'];
            if (!empty($markdown_underline['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_underline['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/~~.*?~~/s', $src)) {
            $markdown_strike = $this->markdown_strike($src);
            $src = $markdown_strike['text'];
            if (!empty($markdown_strike['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_strike['metadata']['meta_data_parts']);
            }
        }

        if (preg_match('/\|\|.*?\|\|/s', $src)) {
            $markdown_spoiler = $this->markdown_spoiler($src);
            $src = $markdown_spoiler['text'];
            if (!empty($markdown_spoiler['metadata']['meta_data_parts'])) {
                $metadataParts = array_merge($metadataParts, $markdown_spoiler['metadata']['meta_data_parts']);
            }
        }

        $markdown_link = $this->markdown_link($src);
        $src = $markdown_link['text'];
        if (!empty($markdown_link['metadata']['meta_data_parts'])) {
            $metadataParts = array_merge($metadataParts, $markdown_link['metadata']['meta_data_parts']);
        }

        $result = ["text" => $src];
        if (!empty($metadataParts)) {
            $result["metadata"] = ["meta_data_parts" => $metadataParts];
        }
        return $result;
    }
}
