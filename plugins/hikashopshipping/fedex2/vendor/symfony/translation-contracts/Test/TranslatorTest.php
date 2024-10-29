<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Symfony\Contracts\Translation\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

class TranslatorTest extends TestCase
{
    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
        \Locale::setDefault('en');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    public function getTranslator(): TranslatorInterface
    {
        return new class() implements TranslatorInterface {
            use TranslatorTrait;
        };
    }

    public function testTrans($expected, $id, $parameters)
    {
        $translator = $this->getTranslator();

        $this->assertEquals($expected, $translator->trans($id, $parameters));
    }

    public function testTransChoiceWithExplicitLocale($expected, $id, $number)
    {
        $translator = $this->getTranslator();

        $this->assertEquals($expected, $translator->trans($id, ['%count%' => $number]));
    }

    public function testTransChoiceWithDefaultLocale($expected, $id, $number)
    {
        $translator = $this->getTranslator();

        $this->assertEquals($expected, $translator->trans($id, ['%count%' => $number]));
    }

    public function testTransChoiceWithEnUsPosix($expected, $id, $number)
    {
        $translator = $this->getTranslator();
        $translator->setLocale('en_US_POSIX');

        $this->assertEquals($expected, $translator->trans($id, ['%count%' => $number]));
    }

    public function testGetSetLocale()
    {
        $translator = $this->getTranslator();

        $this->assertEquals('en', $translator->getLocale());
    }

    public function testGetLocaleReturnsDefaultLocaleIfNotSet()
    {
        $translator = $this->getTranslator();

        \Locale::setDefault('pt_BR');
        $this->assertEquals('pt_BR', $translator->getLocale());

        \Locale::setDefault('en');
        $this->assertEquals('en', $translator->getLocale());
    }

    public static function getTransTests()
    {
        return [
            ['Symfony is great!', 'Symfony is great!', []],
            ['Symfony is awesome!', 'Symfony is %what%!', ['%what%' => 'awesome']],
        ];
    }

    public static function getTransChoiceTests()
    {
        return [
            ['There are no apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['There is one apple', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 1],
            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 10],
            ['There are 0 apples', 'There is 1 apple|There are %count% apples', 0],
            ['There is 1 apple', 'There is 1 apple|There are %count% apples', 1],
            ['There are 10 apples', 'There is 1 apple|There are %count% apples', 10],
            ['There are 2 apples', 'There are 2 apples', 2],
        ];
    }

    public function testInterval($expected, $number, $interval)
    {
        $translator = $this->getTranslator();

        $this->assertEquals($expected, $translator->trans($interval.' foo|[1,Inf[ bar', ['%count%' => $number]));
    }

    public static function getInterval()
    {
        return [
            ['foo', 3, '{1,2, 3 ,4}'],
            ['bar', 10, '{1,2, 3 ,4}'],
            ['bar', 3, '[1,2]'],
            ['foo', 1, '[1,2]'],
            ['foo', 2, '[1,2]'],
            ['bar', 1, ']1,2['],
            ['bar', 2, ']1,2['],
            ['foo', log(0), '[-Inf,2['],
            ['foo', -log(0), '[-2,+Inf]'],
        ];
    }

    public function testChoose($expected, $id, $number, $locale = null)
    {
        $translator = $this->getTranslator();

        $this->assertEquals($expected, $translator->trans($id, ['%count%' => $number], null, $locale));
    }

    public function testReturnMessageIfExactlyOneStandardRuleIsGiven()
    {
        $translator = $this->getTranslator();

        $this->assertEquals('There are two apples', $translator->trans('There are two apples', ['%count%' => 2]));
    }

    public function testThrowExceptionIfMatchingMessageCannotBeFound($id, $number)
    {
        $translator = $this->getTranslator();

        $this->expectException(\InvalidArgumentException::class);

        $translator->trans($id, ['%count%' => $number]);
    }

    public static function getNonMatchingMessages()
    {
        return [
            ['{0} There are no apples|{1} There is one apple', 2],
            ['{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['{1} There is one apple|]2,Inf] There are %count% apples', 2],
            ['{0} There are no apples|There is one apple', 2],
        ];
    }

    public static function getChooseTests()
    {
        return [
            ['There are no apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['There are no apples', '{0}     There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['There are no apples', '{0}There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 0],

            ['There is one apple', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 1],

            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples', 10],
            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf]There are %count% apples', 10],
            ['There are 10 apples', '{0} There are no apples|{1} There is one apple|]1,Inf]     There are %count% apples', 10],

            ['There are 0 apples', 'There is one apple|There are %count% apples', 0],
            ['There is one apple', 'There is one apple|There are %count% apples', 1],
            ['There are 10 apples', 'There is one apple|There are %count% apples', 10],

            ['There are 0 apples', 'one: There is one apple|more: There are %count% apples', 0],
            ['There is one apple', 'one: There is one apple|more: There are %count% apples', 1],
            ['There are 10 apples', 'one: There is one apple|more: There are %count% apples', 10],

            ['There are no apples', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 0],
            ['There is one apple', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 1],
            ['There are 10 apples', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 10],

            ['', '{0}|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['', '{0} There are no apples|{1}|]1,Inf] There are %count% apples', 1],

            ['There are 0 apples', 'There is one apple|There are %count% apples', 0],
            ['There is one apple', 'There is one apple|There are %count% apples', 1],
            ['There are 2 apples', 'There is one apple|There are %count% apples', 2],

            ['There is almost one apple', '{0} There are no apples|]0,1[ There is almost one apple|{1} There is one apple|[1,Inf] There is more than one apple', 0.7],
            ['There is one apple', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 1],
            ['There is more than one apple', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 1.7],
            ['There are no apples', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 0],
            ['There are no apples', '{0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 0.0],
            ['There are no apples', '{0.0} There are no apples|]0,1[There are %count% apples|{1} There is one apple|[1,Inf] There is more than one apple', 0],

            ["This is a text with a\n            new-line in it. Selector = 0.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 0],
            ["This is a text with a\n            new-line in it. Selector = 1.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 1],
            ["This is a text with a\n            new-line in it. Selector > 1.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 5],
            ['This is a text with a
            new-line in it. Selector = 1.', '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 1],
            ['This is a text with a
            new-line in it. Selector > 1.', '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 5],
            ['This is a text with a\nnew-line in it. Selector = 0.', '{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.', 0],
            ["This is a text with a\nnew-line in it. Selector = 1.", "{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.", 1],
            ['This is a text with | in it. Selector = 0.', '{0}This is a text with || in it. Selector = 0.|{1}This is a text with || in it. Selector = 1.', 0],
            ['', '|', 1],
            ['', '||', 1],

            ['1.5 liters', '%count% liter|%count% liters', 1.5],
            ['1.5 litre', '%count% litre|%count% litres', 1.5, 'fr'],

            ['-1 degree', '%count% degree|%count% degrees', -1],
            ['-1 degré', '%count% degré|%count% degrés', -1],
            ['-1.5 degrees', '%count% degree|%count% degrees', -1.5],
            ['-1.5 degré', '%count% degré|%count% degrés', -1.5, 'fr'],
            ['-2 degrees', '%count% degree|%count% degrees', -2],
            ['-2 degrés', '%count% degré|%count% degrés', -2],
        ];
    }

    public function testFailedLangcodes($nplural, $langCodes)
    {
        $matrix = $this->generateTestData($langCodes);
        $this->validateMatrix($nplural, $matrix, false);
    }

    public function testLangcodes($nplural, $langCodes)
    {
        $matrix = $this->generateTestData($langCodes);
        $this->validateMatrix($nplural, $matrix);
    }

    public static function successLangcodes(): array
    {
        return [
            ['1', ['ay', 'bo', 'cgg', 'dz', 'id', 'ja', 'jbo', 'ka', 'kk', 'km', 'ko', 'ky']],
            ['2', ['nl', 'fr', 'en', 'de', 'de_GE', 'hy', 'hy_AM', 'en_US_POSIX']],
            ['3', ['be', 'bs', 'cs', 'hr']],
            ['4', ['cy', 'mt', 'sl']],
            ['6', ['ar']],
        ];
    }

    public static function failingLangcodes(): array
    {
        return [
            ['1', ['fa']],
            ['2', ['jbo']],
            ['3', ['cbs']],
            ['4', ['gd', 'kw']],
            ['5', ['ga']],
        ];
    }

    protected function validateMatrix(string $nplural, array $matrix, bool $expectSuccess = true)
    {
        foreach ($matrix as $langCode => $data) {
            $indexes = array_flip($data);
            if ($expectSuccess) {
                $this->assertCount($nplural, $indexes, "Langcode '$langCode' has '$nplural' plural forms.");
            } else {
                $this->assertNotEquals((int) $nplural, \count($indexes), "Langcode '$langCode' has '$nplural' plural forms.");
            }
        }
    }

    protected function generateTestData($langCodes)
    {
        $translator = new class() {
            use TranslatorTrait {
                getPluralizationRule as public;
            }
        };

        $matrix = [];
        foreach ($langCodes as $langCode) {
            for ($count = 0; $count < 200; ++$count) {
                $plural = $translator->getPluralizationRule($count, $langCode);
                $matrix[$langCode][$count] = $plural;
            }
        }

        return $matrix;
    }
}
