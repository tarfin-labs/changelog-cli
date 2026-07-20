<?php

namespace ChangelogCLI\Tests\Feature;

use ChangelogCLI\Changelog;
use ChangelogCLI\Commands\ChangelogCommand;
use ChangelogCLI\Tests\TestCase;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ChangelogCommandTest extends TestCase
{
    private const BRANCH = 'feature/test-output';

    protected function setUp(): void
    {
        parent::setUp();

        Process::fake([
            'git config user.name' => 'Faruk Can',
            'git symbolic-ref --short HEAD' => self::BRANCH,
        ]);

        Storage::fake();
    }

    public function testItPrintsCreatedFilePathInMakeStyleOutput(): void
    {
        [$command, $buffer] = $this->makeCommand();
        $exitCode = $command->present($this->app->make(Changelog::class), 0);

        $output = $buffer->fetch();

        $this->assertMatchesRegularExpression(
            '/INFO\s+Changelog \[changelogs\/unreleased\/'.preg_quote(self::BRANCH, '/').'\.md\] created successfully\./',
            $output
        );
        $this->assertStringNotContainsString('Category', $output);
        $this->assertSame(0, $exitCode);
    }

    public function testItPrintsAdditionalDetailsInVerboseMode(): void
    {
        [$command, $buffer] = $this->makeCommand(OutputInterface::VERBOSITY_VERBOSE);
        $exitCode = $command->present($this->app->make(Changelog::class), 0);

        $output = $buffer->fetch();

        $this->assertMatchesRegularExpression(
            '/INFO\s+Changelog \[changelogs\/unreleased\/'.preg_quote(self::BRANCH, '/').'\.md\] created successfully\./',
            $output
        );
        $this->assertStringContainsString('Category', $output);
        $this->assertStringContainsString('New feature', $output);
        $this->assertSame(0, $exitCode);
    }

    public function testItPersistsTheChangelogFileWithSelectedCategory(): void
    {
        [$command] = $this->makeCommand();
        $command->present($this->app->make(Changelog::class), 1);

        Storage::assertExists('changelogs/unreleased/'.self::BRANCH.'.md');

        $content = Storage::get('changelogs/unreleased/'.self::BRANCH.'.md');
        $this->assertStringContainsString('### Fixed', $content);
    }

    public function testItPrintsErrorAndReturnsFailureWhenFileWriteFails(): void
    {
        Storage::shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('put')->andReturn(false);

        [$command, $buffer] = $this->makeCommand();
        $exitCode = $command->present($this->app->make(Changelog::class), 0);

        $output = $buffer->fetch();

        $this->assertStringContainsString('Failed to create changelog file.', $output);
        $this->assertSame(1, $exitCode);
    }

    /**
     * @return array{0: ChangelogCommand, 1: BufferedOutput}
     */
    private function makeCommand(int $verbosity = OutputInterface::VERBOSITY_NORMAL): array
    {
        $buffer = new BufferedOutput();
        $style = new OutputStyle(new ArrayInput([]), $buffer);
        $style->setVerbosity($verbosity);

        $command = $this->app->make(ChangelogCommand::class);
        $command->setOutput($style);
        $command->setLaravel($this->app);

        $components = new Factory($style);
        $reflection = new \ReflectionProperty(Command::class, 'components');
        $reflection->setValue($command, $components);

        return [$command, $buffer];
    }
}
