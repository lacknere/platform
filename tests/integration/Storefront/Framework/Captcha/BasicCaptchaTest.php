<?php declare(strict_types=1);

namespace Shopware\Tests\Integration\Storefront\Framework\Captcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Storefront\Framework\Captcha\BasicCaptcha;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
class BasicCaptchaTest extends TestCase
{
    use KernelTestBehaviour;

    private const IS_VALID = true;
    private const IS_INVALID = false;
    private const BASIC_CAPTCHA_SESSION = 'kyln';

    private BasicCaptcha $captcha;

    protected function setUp(): void
    {
        $this->captcha = static::getContainer()->get(BasicCaptcha::class);
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        static::getContainer()->get('request_stack')->push($request);

        $request->getSession()->set('basic_captcha_session', self::BASIC_CAPTCHA_SESSION);
    }

    #[DataProvider('requestDataProvider')]
    public function testIsValid(Request $request, bool $shouldBeValid): void
    {
        if ($shouldBeValid) {
            static::assertTrue($this->captcha->isValid($request, []));
        } else {
            static::assertFalse($this->captcha->isValid($request, []));
        }
    }

    /**
     * @return array<int, array<int, bool|Request>>
     */
    public static function requestDataProvider(): array
    {
        return [
            [
                self::getRequest(),
                self::IS_INVALID,
            ],
            [
                self::getRequest([
                    BasicCaptcha::CAPTCHA_REQUEST_PARAMETER => null,
                ]),
                self::IS_INVALID,
            ],
            [
                self::getRequest([
                    BasicCaptcha::CAPTCHA_REQUEST_PARAMETER => '',
                ]),
                self::IS_INVALID,
            ],
            [
                self::getRequest([
                    BasicCaptcha::CAPTCHA_REQUEST_PARAMETER => 'notkyln',
                ]),
                self::IS_INVALID,
            ],
            [
                self::getRequest([
                    BasicCaptcha::CAPTCHA_REQUEST_PARAMETER => self::BASIC_CAPTCHA_SESSION,
                ]),
                self::IS_VALID,
            ],
        ];
    }

    /**
     * @param array<string, string|null> $data
     */
    private static function getRequest(array $data = []): Request
    {
        return new Request([], $data, [], [], [], [], null);
    }
}
