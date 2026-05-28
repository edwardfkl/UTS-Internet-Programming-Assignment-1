<?php

namespace Tests\Unit;

use App\Support\AdminLocales;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminLocalesTest extends TestCase
{
    public function test_allowed_locales_match_storefront_coverage(): void
    {
        $this->assertSame(['en', 'zh_TW', 'ja', 'ko'], AdminLocales::ALLOWED);
    }

    #[DataProvider('htmlLangProvider')]
    public function test_html_lang(string $locale, string $expected): void
    {
        $this->assertSame($expected, AdminLocales::htmlLang($locale));
    }

    public static function htmlLangProvider(): array
    {
        return [
            'english' => ['en', 'en'],
            'traditional chinese' => ['zh_TW', 'zh-Hant'],
            'japanese' => ['ja', 'ja'],
            'korean' => ['ko', 'ko'],
            'unknown falls back to english' => ['fr', 'en'],
        ];
    }

    #[DataProvider('intlLocaleProvider')]
    public function test_intl_locale(string $locale, string $expected): void
    {
        $this->assertSame($expected, AdminLocales::intlLocale($locale));
    }

    public static function intlLocaleProvider(): array
    {
        return [
            'english uses en-AU' => ['en', 'en-AU'],
            'traditional chinese' => ['zh_TW', 'zh-TW'],
            'japanese' => ['ja', 'ja-JP'],
            'korean' => ['ko', 'ko-KR'],
            'unknown falls back to en-AU' => ['de', 'en-AU'],
        ];
    }
}
