<?php

declare(strict_types=1);

namespace Clear\Transformations;

/**
 * Slugifier - translates a text to a slug.
 */
final class Slug
{
    public static function fromText(string $string)
    {
        $transliterate = array(
            // Latin
            'š' => 's',  'đ' => 'dj', 'ž' => 'z',  'č' => 'c',  'ć' => 'c',
            'à' => 'a',  'á' => 'a',  'â' => 'a',  'ã' => 'a',  'ä' => 'a',
            'å' => 'a',  'æ' => 'a',  'ç' => 'c',  'è' => 'e',  'é' => 'e',
            'ê' => 'e',  'ë' => 'e',  'ì' => 'i',  'í' => 'i',  'î' => 'i',
            'ï' => 'i',  'ð' => 'o',  'ñ' => 'n',  'ò' => 'o',  'ó' => 'o',
            'ô' => 'o',  'õ' => 'o',  'ö' => 'o',  'ø' => 'o',  'ù' => 'u',
            'ú' => 'u',  'û' => 'u',  'ý' => 'y',  'þ' => 'b',  'ÿ' => 'y',
            'ŕ' => 'r',  'ß' => 'ss',
            // Double Cyrillic
            'кс'=> 'x',
            // Cyrillic
            'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
            'е' => 'e',  'ж' => 'j',  'з' => 'z',  'и' => 'i',  'й' => 'y',
            'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',  'о' => 'o',
            'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',  'у' => 'u',
            'ф' => 'f',  'х' => 'h',  'ц' => 'c',  'ч' => 'ch', 'ш' => 'sh',
            'щ' => 'sht','ъ' => 'a',  'ь' => 'y',  'ю' => 'yu', 'я' => 'ya',
            'ё' => 'yo', 'ы' => 'y',  'э' => 'e',
            // Ukrainian
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
            // Greek
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
        );

        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $string = mb_convert_encoding((string)$string, 'UTF-8', mb_list_encodings());

        // no upper case letters
        $string = mb_strtolower($string, 'UTF-8');

        // transliterate Cyrillic and special letters
        $string = strtr($string, $transliterate);

        // leave only lower case Latin letters and digits
        $string = preg_replace('/[^a-z0-9-]+/', '-', $string);

        // remove duplicated '-'
        $string = preg_replace('/\-{2,}/', '-', $string);

        // trim
        $string = trim($string, '-');

       return $string;
    }
}
