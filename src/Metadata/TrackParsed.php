<?php

namespace RubikaBot\Metadata;

class TrackParsed {
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

    function html2md(string $src): string {
        $src = preg_replace('/<i>(.*?)<\/i>/s', '||$1||', $src);
        $src = preg_replace('/<span class="spoiler">(.*?)<\/span>/s', '||$1||', $src);

        $replacements = [
            '/<b>(.*?)<\/b>/s'       => '**$1**',
            '/<strong>(.*?)<\/strong>/s' => '**$1**',
            '/<u>(.*?)<\/u>/s'       => '__$1__',
            '/<s>(.*?)<\/s>/s'       => '~~$1~~',
            '/<i>(.*?)<\/i>/s'       => '*$1*',
            '/<em>(.*?)<\/em>/s'     => '*$1*',
            '/<code>(.*?)<\/code>/s' => '`$1`',
            '/<pre>(.*?)<\/pre>/s'   => "```\n$1\n```",
            '/<a href="(.*?)">(.*?)<\/a>/s' => '[$2]($1)',
            '/<br\s*\/?>/i'          => "\n",
            '/<[^>]+>/'              => '',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $src = preg_replace($pattern, $replacement, $src);
        }

        $src = str_replace('@@SPOILER@@', '||', $src);
        return trim($src);
    }

    public function transcribe($src, $mode = "MarkdownMode") {
        if ($mode === "HTML") {
            $src = $this->html2md($src);
        }

        if ($mode === "MarkdownMode") {
            $markdown = new Markdown();
            return $markdown->markdown_mode($src);
        }

        $payloadParts = [];
        $normalizedText = $src;
        $byteOffset = 0;
        $charOffset = 0;

        preg_match_all(self::$pattern, $src, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $whole = $m[0][0];
            $start = $m[0][1];
            $end = $start + strlen($whole);

            $adjFrom = self::utf16Len(substr($src, 0, $start)) - $byteOffset;
            $adjCharFrom = $start - $charOffset;

            $gname = null;
            foreach (self::$typeMap as $key => $type) {
                if (!empty($m[$key][0])) {
                    $gname = $key;
                    break;
                }
            }
            if (!$gname) continue;

            $inner = "";
            $linkHref = null;
            if ($gname === "link") {
                $inner = $m["link_text"][0] ?? "";
                $linkHref = $m["link_url"][0] ?? "";
            } else {
                $inner = $m["{$gname}_c"][0] ?? "";
            }

            if ($gname === "quote") {
                $innerMeta = $this->transcribe($inner, "MARKDOWN");
                $inner = $innerMeta["text"];
                if (!empty($innerMeta["metadata"]["meta_data_parts"])) {
                    foreach ($innerMeta["metadata"]["meta_data_parts"] as $part) {
                        $part["from_index"] += $adjFrom;
                        $payloadParts[] = $part;
                    }
                }
            }

            if ($inner === "") continue;

            $contentLen = self::utf16Len($inner);
            $part = [
                "type" => self::$typeMap[$gname] ?? "Unknown",
                "from_index" => $adjFrom,
                "length" => $contentLen,
            ];
            if ($linkHref) {
                $part["link_url"] = $linkHref;
            }
            $payloadParts[] = $part;

            $normalizedText = substr($normalizedText, 0, $adjCharFrom) . $inner . substr($normalizedText, $end - $charOffset);
            $byteOffset += self::utf16Len($whole) - $contentLen;
            $charOffset += strlen($whole) - strlen($inner);
        }

        $result = ["text" => trim($normalizedText)];
        if (!empty($payloadParts)) {
            $result["metadata"] = ["meta_data_parts" => $payloadParts];
        }
        
        return $result;
    }

    public function parse($text, $mode = "MarkdownMode") {
        return $this->transcribe($text, $mode);
    }
}
