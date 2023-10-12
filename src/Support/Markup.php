<?php

namespace Alisa\Support;

use Alisa\Support\Asset;

use function Alisa\Support\Helpers\plural;

class Markup
{
    public static function pause(array $markup): array
    {
        return [
            'text' => preg_replace('/{\s?pause:(.+?)}/iu', '', $markup['text']),
            'tts' => preg_replace_callback('/{\s?pause:(.+?)}/iu', function ($match) {
                return 'sil <[' . self::variant($match[1]) . ']>';
            }, $markup['tts']),
        ];
    }

    public static function text(array $markup): array
    {
        return [
            'text' => preg_replace_callback('/{\s?text:(.+?)}/iu', function ($match) {
                return self::variant($match[1]);
            }, $markup['text']),
            'tts' => preg_replace('/{\s?text:(.+?)}/iu', '', $markup['tts']),
        ];
    }

    public static function tts(array $markup): array
    {
        return [
            'text' => preg_replace('/{\s?tts:(.+?)}/iu', '', $markup['text']),
            'tts' => preg_replace_callback('/{\s?tts:(.+?)}/iu', function ($match) {
                return self::variant($match[1]);
            }, $markup['tts']),
        ];
    }

    public static function space(array $markup): array
    {
        return [
            'text' => preg_replace('/{\s?space\s?}/iu', '', $markup['text']),
            'tts' => preg_replace('/{\s?space\s?}/iu', ' ', $markup['tts']),
        ];
    }

    public static function br(array $markup): array
    {
        return [
            'text' => preg_replace_callback('/{\s?br:(.+?)}/iu', function ($match) {
                return str_repeat("\n", self::variant($match[1]));
            }, preg_replace('/{\s?br\s?}/iu', "\n", $markup['text'])),
            'tts' => preg_replace('/{\s?br:(.+?)}/iu', '', preg_replace('/{\s?br\s?}/iu', '', $markup['tts'])),
        ];
    }

    public static function effect(array $markup): array
    {
        return [
            'text' => preg_replace('/{\s?effect:(.+?)}/iu', '', preg_replace('/{\s?\/\s?effect\s?}/iu', '', $markup['text'])),
            'tts' => preg_replace_callback('/{\s?effect:(.+?)}/iu', function ($match) {
                return '<speaker effect="' . self::variant($match[1]) . '">';
            }, preg_replace('/{\s?\/\s?effect\s?}/iu', '<speaker effect="-">', $markup['tts'])),
        ];
    }

    public static function audio(array $markup): array
    {
        return [
            'text' => preg_replace('/{\s?audio:(.+?)}/iu', '', preg_replace('/{\s?\/\s?audio\s?}/iu', '', $markup['text'])),
            'tts' => preg_replace_callback('/{\s?audio:(.+?)}/iu', function ($match) {
                $variant = self::variant($match[1]);
                $variant = Asset::get($variant) ?? $variant;

                if (!str_ends_with($variant, '.opus')) {
                    $variant .= '.opus';
                }

                return '<speaker audio="' . $variant . '">';
            }, $markup['tts']),
        ];
    }

    public static function plural(array $markup): array
    {
        $pluralize = function (string $text) {
            return preg_replace_callback('/{\s?(\d):(.+?)}/iu', function ($match) {
                return plural(
                    $match[1],
                    array_map([self::class, 'variant'], array_filter(array_map('trim', explode(',', $match[2]))))
                );
            }, $text);
        };

        return [
            'text' => $pluralize($markup['text']),
            'tts' => $pluralize($markup['tts']),
        ];
    }

    public static function rand(array $markup): array
    {
        $randomize = function (string $text) {
            return preg_replace_callback('/{\s?rand:(.+?)}/iu', function ($match) {
                return self::variant($match[1]);
            }, $text);
        };

        return [
            'text' => $randomize($markup['text']),
            'tts' => $randomize($markup['tts']),
        ];
    }

    public static function textTts(array $markup): array
    {
        return [
            'text' => preg_replace_callback('/{\s?{(.+?)}\s?,\s?{(.+?)}\s?}/iu', function ($match) {
                return $match[1];
            }, $markup['text']),
            'tts' => preg_replace_callback('/{\s?{(.+?)}\s?,\s?{(.+?)}\s?}/iu', function ($match) {
                return $match[2];
            }, $markup['tts']),
        ];
    }

    public static function accent(array $markup): array
    {
        return [
            'text' => preg_replace('/\+(?=[a-zA-Zа-яА-Яё])/iu', '', $markup['text']),
            'tts' => $markup['tts'],
        ];
    }

    public static function trim(array $markup): array
    {
        return [
            'text' => self::trimWhitespace($markup['text']),
            'tts' => self::trimWhitespace($markup['tts']),
        ];
    }

    public static function quotes(array $markup): array
    {
        $markup['text'] = str_replace('<<<', '«', $markup['text']);
        $markup['text'] = str_replace('>>>', '»', $markup['text']);

        $markup['tts'] = str_replace('<<<', '«', $markup['tts']);
        $markup['tts'] = str_replace('>>>', '»', $markup['tts']);

        return [
            'text' => $markup['text'],
            'tts' => $markup['tts'],
        ];
    }

    public static function variant(array|string $variants): string
    {
        if (is_string($variants)) {
            $variants = explode('|', $variants);
        }

        $variants = array_filter(array_map('trim', $variants));

        return $variants[array_rand($variants)];
    }

    public static function pipe(array $markup, array $methods): array
    {
        foreach ($methods as $method) {
            $markup = self::$method($markup);
        }

        return $markup;
    }

    public static function process(array $markup): array
    {
        $methods = [
            'pause', 'text', 'tts', 'space', 'br', 'audio', 'effect',
            'plural', 'rand', 'textTts', 'accent', 'quotes', 'trim',
        ];

        return self::pipe($markup, $methods);
    }

    public static function trimWhitespace(string $str): string
    {
        return trim(implode("\n", array_map('trim', explode("\n", preg_replace('/ {2,}/', ' ', $str)))));
    }
}