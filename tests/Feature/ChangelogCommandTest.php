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
        $exitCode = $this->invokeCreateAndReport($command, 0);

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
        $exitCode = $this->invokeCreateAndReport($command, 0);

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
        $this->invokeCreateAndReport($command, 1);

        Storage::assertExists('changelogs/unreleased/'.self::BRANCH.'.md');

        $content = Storage::get('changelogs/unreleased/'.self::BRANCH.'.md');
        $this->assertStringContainsString('### Fixed', $content);
    }

    public function testItPrintsErrorAndReturnsFailureWhenFileWriteFails(): void
    {
        Storage::shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('put')->andReturn(false);

        [$command, $buffer] = $this->makeCommand();
        $exitCode = $this->invokeCreateAndReport($command, 0);

        $output = $buffer->fetch();

        $this->assertStringContainsString('Failed to create changelog file.', $output);
        $this->assertSame(1, $exitCode);
    }

    public function testItRunsFullHandleFlowAndReportsCreatedFile(): void
    {
        $command = $this->makeControllableCommand(option: 0);

        $exitCode = $command->run(new ArrayInput([]), $this->makeOutput());

        $this->assertSame(0, $exitCode);
        Storage::assertExists('changelogs/unreleased/'.self::BRANCH.'.md');
    }

    public function testItReportsCancellationAndReturnsSuccessWhenMenuIsClosed(): void
    {
        $command = $this->makeControllableCommand(option: null);

        $buffer = new BufferedOutput();
        $style = new OutputStyle(new ArrayInput([]), $buffer);

        $exitCode = $command->run(new ArrayInput([]), $style);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Changelog cancelled.', $buffer->fetch());
        Storage::assertDirectoryEmpty('changelogs/unreleased');
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

        $this->attachComponentsFactory($command, $style);

        return [$command, $buffer];
    }

    private function invokeCreateAndReport(ChangelogCommand $command, int $option): int
    {
        $reflection = new \ReflectionMethod(ChangelogCommand::class, 'createAndReport');
        $reflection->setAccessible(true);

        return $reflection->invoke($command, $this->app->make(Changelog::class), $option);
    }

    private function makeControllableCommand(?int $option): ControllableChangelogCommand
    {
        $command = new ControllableChangelogCommand($option);
        $command->setLaravel($this->app);

        return $command;
    }

    private function makeOutput(): BufferedOutput
    {
        $buffer = new BufferedOutput();
        $style = new OutputStyle(new ArrayInput([]), $buffer);
        $style->setVerbosity(OutputInterface::VERBOSITY_NORMAL);

        $this->attachComponentsFactory($this->app->make(ChangelogCommand::class), $style);

        return $buffer;
    }

    /**
     * Attach a Components Factory to a command via reflection.
     *
     * Laravel's console command normally builds its $components instance
     * inside Command::execute(). Tests call the command directly, bypassing
     * execute(), so the factory never gets initialised and every
     * $this->components->... call would fail with a null reference. Reflection
     * is a pragmatic workaround to keep the public surface of ChangelogCommand
     * untouched. If Laravel ever renames the property or relocates the wiring,
     * this is the only line that needs updating.
     */
    private function attachComponentsFactory(ChangelogCommand $command, OutputStyle $style): void
    {
        $components = new Factory($style);
        $reflection = new \ReflectionProperty(Command::class, 'components');
        $reflection->setValue($command, $components);
    }
}
