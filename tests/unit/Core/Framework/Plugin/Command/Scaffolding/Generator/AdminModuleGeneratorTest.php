<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\Plugin\Command\Scaffolding\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Plugin\Command\Scaffolding\Generator\AdminModuleGenerator;
use Shopware\Core\Framework\Plugin\Command\Scaffolding\PluginScaffoldConfiguration;
use Shopware\Core\Framework\Plugin\Command\Scaffolding\StubCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[CoversClass(AdminModuleGenerator::class)]
class AdminModuleGeneratorTest extends TestCase
{
    public function testCommandOptions(): void
    {
        $generator = new AdminModuleGenerator();

        static::assertTrue($generator->hasCommandOption());
        static::assertNotEmpty($generator->getCommandOptionName());
        static::assertNotEmpty($generator->getCommandOptionDescription());
    }

    #[DataProvider('addScaffoldConfigProvider')]
    public function testAddScaffoldConfig(
        bool $getOptionResponse,
        bool $confirmResponse,
        bool $expectedHasOption
    ): void {
        $configuration = $this->getConfig();

        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->willReturn($getOptionResponse);

        $io = $this->createMock(SymfonyStyle::class);
        $io->method('confirm')->willReturn($confirmResponse);

        (new AdminModuleGenerator())
            ->addScaffoldConfig($configuration, $input, $io);

        static::assertEquals($expectedHasOption, $configuration->hasOption(AdminModuleGenerator::OPTION_NAME));
    }

    public static function addScaffoldConfigProvider(): \Generator
    {
        yield 'with command option and with confirm' => [
            'getOptionResponse' => true,
            'confirmResponse' => true,
            'expectedHasOption' => true,
        ];

        yield 'with command option and without confirm' => [
            'getOptionResponse' => true,
            'confirmResponse' => false,
            'expectedHasOption' => true,
        ];

        yield 'without command option and with confirm' => [
            'getOptionResponse' => false,
            'confirmResponse' => true,
            'expectedHasOption' => true,
        ];

        yield 'without command option and without confirm' => [
            'getOptionResponse' => false,
            'confirmResponse' => false,
            'expectedHasOption' => false,
        ];
    }

    /**
     * @param array<int, string> $expected
     */
    #[DataProvider('generateProvider')]
    public function testGenerate(PluginScaffoldConfiguration $config, array $expected): void
    {
        $stubs = new StubCollection();

        (new AdminModuleGenerator())
            ->generateStubs($config, $stubs);

        static::assertCount(\count($expected), $stubs);

        foreach ($expected as $stub) {
            static::assertTrue($stubs->has($stub));
        }
    }

    public static function generateProvider(): \Generator
    {
        yield 'No option, no stubs' => [
            'config' => self::getConfig(),
            'expected' => [],
        ];

        yield 'Option false, no stubs' => [
            'config' => self::getConfig([AdminModuleGenerator::OPTION_NAME => false]),
            'expected' => [],
        ];

        yield 'Option true, stubs' => [
            'config' => self::getConfig([AdminModuleGenerator::OPTION_NAME => true]),
            'expected' => [
                'src/Resources/app/administration/src/module/swag-example/index.js',
                'src/Resources/app/administration/src/main.js',
                'src/Resources/app/administration/src/snippet/en-GB.json',
                'src/Resources/app/administration/src/snippet/de-DE.json',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function getConfig(array $options = []): PluginScaffoldConfiguration
    {
        return new PluginScaffoldConfiguration('TestPlugin', 'MyNamespace', '/path/to/directory', $options);
    }
}
