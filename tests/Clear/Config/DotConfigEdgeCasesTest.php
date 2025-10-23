<?php

declare(strict_types=1);

namespace Test\Config;

use Clear\Config\DotConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Edge case tests for DotConfig class.
 */
#[CoversClass(DotConfig::class)]
class DotConfigEdgeCasesTest extends TestCase
{
    public function testEmptyArray(): void
    {
        $config = new DotConfig([]);

        $this->assertFalse($config->has('any.key'));
        $this->assertNull($config->get('any.key'));
        $this->assertSame('default', $config->get('any.key', 'default'));
    }

    public function testNullValues(): void
    {
        $data = [
            'null_value' => null,
            'nested' => [
                'null_value' => null,
                'not_null' => 'value'
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('null_value'));
        $this->assertTrue($config->has('nested.null_value'));
        $this->assertTrue($config->has('nested.not_null'));

        $this->assertNull($config->get('null_value'));
        $this->assertNull($config->get('nested.null_value'));
        $this->assertSame('value', $config->get('nested.not_null'));

        $this->assertNull($config->get('null_value', 'default'));
        $this->assertNull($config->get('nested.null_value', 'default'));
    }

    public function testEmptyStringValues(): void
    {
        $data = [
            'empty_string' => '',
            'nested' => [
                'empty_string' => '',
                'not_empty' => 'value'
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('empty_string'));
        $this->assertTrue($config->has('nested.empty_string'));
        $this->assertTrue($config->has('nested.not_empty'));

        $this->assertSame('', $config->get('empty_string'));
        $this->assertSame('', $config->get('nested.empty_string'));
        $this->assertSame('value', $config->get('nested.not_empty'));
    }

    public function testZeroValues(): void
    {
        $data = [
            'zero_int' => 0,
            'zero_float' => 0.0,
            'nested' => [
                'zero_int' => 0,
                'zero_float' => 0.0,
                'not_zero' => 1
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('zero_int'));
        $this->assertTrue($config->has('zero_float'));
        $this->assertTrue($config->has('nested.zero_int'));
        $this->assertTrue($config->has('nested.zero_float'));
        $this->assertTrue($config->has('nested.not_zero'));

        $this->assertSame(0, $config->get('zero_int'));
        $this->assertSame(0.0, $config->get('zero_float'));
        $this->assertSame(0, $config->get('nested.zero_int'));
        $this->assertSame(0.0, $config->get('nested.zero_float'));
        $this->assertSame(1, $config->get('nested.not_zero'));
    }

    public function testFalseValues(): void
    {
        $data = [
            'false_bool' => false,
            'nested' => [
                'false_bool' => false,
                'true_bool' => true
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('false_bool'));
        $this->assertTrue($config->has('nested.false_bool'));
        $this->assertTrue($config->has('nested.true_bool'));

        $this->assertFalse($config->get('false_bool'));
        $this->assertFalse($config->get('nested.false_bool'));
        $this->assertTrue($config->get('nested.true_bool'));
    }

    public function testEmptyArrays(): void
    {
        $data = [
            'empty_array' => [],
            'nested' => [
                'empty_array' => [],
                'not_empty_array' => ['value']
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('empty_array'));
        $this->assertTrue($config->has('nested.empty_array'));
        $this->assertTrue($config->has('nested.not_empty_array'));

        $this->assertIsArray($config->get('empty_array'));
        $this->assertEmpty($config->get('empty_array'));
        $this->assertIsArray($config->get('nested.empty_array'));
        $this->assertEmpty($config->get('nested.empty_array'));
        $this->assertIsArray($config->get('nested.not_empty_array'));
        $this->assertNotEmpty($config->get('nested.not_empty_array'));
    }

    public function testDeepNesting(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => [
                                'deep_value' => 'found'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('level1.level2.level3.level4.level5.deep_value'));
        $this->assertSame('found', $config->get('level1.level2.level3.level4.level5.deep_value'));

        $this->assertFalse($config->has('level1.level2.level3.level4.level5.nonexistent'));
        $this->assertNull($config->get('level1.level2.level3.level4.level5.nonexistent'));
    }

    public function testVeryLongKeys(): void
    {
        $longKey = str_repeat('a', 1000);
        $data = [
            $longKey => 'value',
            'nested' => [
                $longKey => 'nested_value'
            ]
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has($longKey));
        $this->assertSame('value', $config->get($longKey));
        $this->assertTrue($config->has("nested.{$longKey}"));
        $this->assertSame('nested_value', $config->get("nested.{$longKey}"));
    }

    public function testSpecialCharactersInKeys(): void
    {
        $data = [
            'key with spaces' => 'value1',
            'key-with-dashes' => 'value2',
            'key_with_underscores' => 'value3',
            'key_with_dots' => 'value4',
            'key/with/slashes' => 'value5',
            'key\\with\\backslashes' => 'value6',
            'key"with"quotes' => 'value7',
            "key'with'single'quotes" => 'value8',
            'key[with]brackets' => 'value9',
            'key{with}braces' => 'value10',
            'key(with)parentheses' => 'value11',
            'key<with>angles' => 'value12',
            'key&with&ampersands' => 'value13',
            'key=with=equals' => 'value14',
            'key+with+pluses' => 'value15',
            'key*with*asterisks' => 'value16',
            'key#with#hashes' => 'value17',
            'key@with@ats' => 'value18',
            'key!with!exclamations' => 'value19',
            'key?with?questions' => 'value20',
            'key:with:colons' => 'value21',
            'key;with;semicolons' => 'value22',
            'key,with,commas' => 'value23',
            'key|with|pipes' => 'value24',
            'key~with~tildes' => 'value25',
            'key`with`backticks' => 'value26',
            'key^with^carets' => 'value27',
            'key%with%percents' => 'value28',
            'key$with$dollars' => 'value29',
            'key€with€euros' => 'value30',
            'key£with£pounds' => 'value31',
            'key¥with¥yens' => 'value32',
            'key¢with¢cents' => 'value33',
            'key©with©copyrights' => 'value34',
            'key®with®registered' => 'value35',
            'key™with™trademarks' => 'value36',
            'key°with°degrees' => 'value37',
            'key±with±plusminus' => 'value38',
            'key×with×times' => 'value39',
            'key÷with÷divide' => 'value40',
            'key∞with∞infinity' => 'value41',
            'key∑with∑sum' => 'value42',
            'key∏with∏product' => 'value43',
            'key∆with∆delta' => 'value44',
            'keyΩwithΩomega' => 'value45',
            'keyαwithαalpha' => 'value46',
            'keyβwithβbeta' => 'value47',
            'keyγwithγgamma' => 'value48',
            'keyδwithδdelta' => 'value49',
            'keyεwithεepsilon' => 'value50',
            'keyζwithζzeta' => 'value51',
            'keyηwithηeta' => 'value52',
            'keyθwithθtheta' => 'value53',
            'keyιwithιiota' => 'value54',
            'keyκwithκkappa' => 'value55',
            'keyλwithλlambda' => 'value56',
            'keyμwithμmu' => 'value57',
            'keyνwithνnu' => 'value58',
            'keyξwithξxi' => 'value59',
            'keyοwithοomicron' => 'value60',
            'keyπwithπpi' => 'value61',
            'keyρwithρrho' => 'value62',
            'keyσwithσsigma' => 'value63',
            'keyτwithτtau' => 'value64',
            'keyυwithυupsilon' => 'value65',
            'keyφwithφphi' => 'value66',
            'keyχwithχchi' => 'value67',
            'keyψwithψpsi' => 'value68',
            'keyωwithωomega' => 'value69',
            'keyΑwithΑAlpha' => 'value70',
            'keyΒwithΒBeta' => 'value71',
            'keyΓwithΓGamma' => 'value72',
            'keyΔwithΔDelta' => 'value73',
            'keyΕwithΕEpsilon' => 'value74',
            'keyΖwithΖZeta' => 'value75',
            'keyΗwithΗEta' => 'value76',
            'keyΘwithΘTheta' => 'value77',
            'keyΙwithΙIota' => 'value78',
            'keyΚwithΚKappa' => 'value79',
            'keyΛwithΛLambda' => 'value80',
            'keyΜwithΜMu' => 'value81',
            'keyΝwithΝNu' => 'value82',
            'keyΞwithΞXi' => 'value83',
            'keyΟwithΟOmicron' => 'value84',
            'keyΠwithΠPi' => 'value85',
            'keyΡwithΡRho' => 'value86',
            'keyΣwithΣSigma' => 'value87',
            'keyΤwithΤTau' => 'value88',
            'keyΥwithΥUpsilon' => 'value89',
            'keyΦwithΦPhi' => 'value90',
            'keyΧwithΧChi' => 'value91',
            'keyΨwithΨPsi' => 'value92',
            'keyΩwithΩOmega' => 'value93',
            'key中文with中文chinese' => 'value94',
            'key日本語with日本語japanese' => 'value95',
            'key한국어with한국어korean' => 'value96',
            'keyالعربيةwithالعربيةarabic' => 'value97',
            'keyעבריתwithעבריתhebrew' => 'value98',
            'keyрусскийwithрусскийrussian' => 'value99',
            'key😀with😀emoji' => 'value100'
        ];

        $config = new DotConfig($data);

        foreach ($data as $key => $expectedValue) {
            $this->assertTrue($config->has($key), "Key '{$key}' should exist");
            $this->assertSame($expectedValue, $config->get($key), "Value for key '{$key}' should match");
        }
    }

    public function testUnicodeInKeys(): void
    {
        $data = [
            'café' => 'value1',
            'naïve' => 'value2',
            'résumé' => 'value3',
            'piñata' => 'value4',
            'jalapeño' => 'value5',
            'señor' => 'value6',
            'niño' => 'value7',
            'año' => 'value8',
            'mañana' => 'value9',
            'peña' => 'value10',
            'caña' => 'value11',
            'baño' => 'value12',
            'niña' => 'value13',
            'señora' => 'value14',
            'señorita' => 'value15',
            'niños' => 'value16',
            'niñas' => 'value17',
            'años' => 'value18',
            'mañanas' => 'value19',
            'peñas' => 'value20',
            'cañas' => 'value21',
            'baños' => 'value22',
            'señoras' => 'value23',
            'señoritas' => 'value24',
            'cafés' => 'value25',
            'naïves' => 'value26',
            'résumés' => 'value27',
            'piñatas' => 'value28',
            'jalapeños' => 'value29',
            'señores' => 'value30'
        ];

        $config = new DotConfig($data);

        foreach ($data as $key => $expectedValue) {
            $this->assertTrue($config->has($key), "Key '{$key}' should exist");
            $this->assertSame($expectedValue, $config->get($key), "Value for key '{$key}' should match");
        }
    }

    public function testMixedDataTypes(): void
    {
        $data = [
            'string' => 'hello',
            'integer' => 42,
            'float' => 3.14159,
            'boolean_true' => true,
            'boolean_false' => false,
            'null' => null,
            'array' => [1, 2, 3],
            'associative_array' => ['a' => 1, 'b' => 2],
            'nested_mixed' => [
                'string' => 'world',
                'integer' => 24,
                'float' => 2.71828,
                'boolean' => true,
                'null' => null,
                'array' => ['x', 'y', 'z']
            ]
        ];

        $config = new DotConfig($data);

        $this->assertSame('hello', $config->get('string'));
        $this->assertSame(42, $config->get('integer'));
        $this->assertSame(3.14159, $config->get('float'));
        $this->assertTrue($config->get('boolean_true'));
        $this->assertFalse($config->get('boolean_false'));
        $this->assertNull($config->get('null'));
        $this->assertSame([1, 2, 3], $config->get('array'));
        $this->assertSame(['a' => 1, 'b' => 2], $config->get('associative_array'));

        $this->assertSame('world', $config->get('nested_mixed.string'));
        $this->assertSame(24, $config->get('nested_mixed.integer'));
        $this->assertSame(2.71828, $config->get('nested_mixed.float'));
        $this->assertTrue($config->get('nested_mixed.boolean'));
        $this->assertNull($config->get('nested_mixed.null'));
        $this->assertSame(['x', 'y', 'z'], $config->get('nested_mixed.array'));
    }

    public function testVeryDeepNesting(): void
    {
        $data = [];
        $current = &$data;
        $depth = 100;

        for ($i = 0; $i < $depth; $i++) {
            $current['level' . $i] = [];
            $current = &$current['level' . $i];
        }
        $current['final_value'] = 'deep_value';

        $config = new DotConfig($data);

        $key = 'level0';
        for ($i = 1; $i < $depth; $i++) {
            $key .= '.level' . $i;
        }
        $key .= '.final_value';

        $this->assertTrue($config->has($key));
        $this->assertSame('deep_value', $config->get($key));
    }

    public function testLargeArray(): void
    {
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data['key' . $i] = 'value' . $i;
        }

        $config = new DotConfig($data);

        for ($i = 0; $i < 1000; $i++) {
            $key = 'key' . $i;
            $this->assertTrue($config->has($key), "Key '{$key}' should exist");
            $this->assertSame('value' . $i, $config->get($key), "Value for key '{$key}' should match");
        }
    }

    public function testNumericKeys(): void
    {
        $data = [
            '0' => 'zero',
            '1' => 'one',
            '2' => 'two',
            '10' => 'ten',
            '100' => 'hundred',
            'nested' => [
                '0' => 'nested_zero',
                '1' => 'nested_one',
                '2' => 'nested_two'
            ]
        ];

        $config = new DotConfig($data);

        $this->assertSame('zero', $config->get('0'));
        $this->assertSame('one', $config->get('1'));
        $this->assertSame('two', $config->get('2'));
        $this->assertSame('ten', $config->get('10'));
        $this->assertSame('hundred', $config->get('100'));

        $this->assertSame('nested_zero', $config->get('nested.0'));
        $this->assertSame('nested_one', $config->get('nested.1'));
        $this->assertSame('nested_two', $config->get('nested.2'));
    }

    public function testEmptyKey(): void
    {
        $data = [
            '' => 'empty_key_value',
            'normal' => 'normal_value'
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has(''));
        $this->assertSame('empty_key_value', $config->get(''));
        $this->assertTrue($config->has('normal'));
        $this->assertSame('normal_value', $config->get('normal'));
    }

    public function testKeyWithOnlyDots(): void
    {
        $data = [
            'dots' => 'dots_value',
            'normal' => 'normal_value'
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('dots'));
        $this->assertSame('dots_value', $config->get('dots'));
        $this->assertTrue($config->has('normal'));
        $this->assertSame('normal_value', $config->get('normal'));
    }

    public function testKeyWithLeadingAndTrailingDots(): void
    {
        $data = [
            'leading' => 'leading_dot_value',
            'trailing' => 'trailing_dot_value',
            'both' => 'both_dots_value',
            'normal' => 'normal_value'
        ];

        $config = new DotConfig($data);

        $this->assertTrue($config->has('leading'));
        $this->assertSame('leading_dot_value', $config->get('leading'));
        $this->assertTrue($config->has('trailing'));
        $this->assertSame('trailing_dot_value', $config->get('trailing'));
        $this->assertTrue($config->has('both'));
        $this->assertSame('both_dots_value', $config->get('both'));
        $this->assertTrue($config->has('normal'));
        $this->assertSame('normal_value', $config->get('normal'));
    }
}
