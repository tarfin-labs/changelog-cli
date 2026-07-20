<?php

namespace ChangelogCLI\Tests\Feature;

use ChangelogCLI\Tests\TestCase;
use Illuminate\Support\Facades\File;

class PublishChangelogCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/changelog-cli-publish-'.uniqid();
        File::ensureDirectoryExists($this->tempDir);

        config(['app.structure.unreleased' => $this->tempDir.'/unreleased']);
        config(['app.structure.main' => $this->tempDir.'/CHANGELOG.md']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testItPublishesAllUnreleasedChangelogsAndPrintsMakeStyleOutput(): void
    {
        $unreleasedDir = config('app.structure.unreleased');
        $mainFile = config('app.structure.main');

        File::ensureDirectoryExists($unreleasedDir);
        File::put($unreleasedDir.'/feature-a.md', "---\nauthor: Faruk Can\ndate: 2026-07-09\n---\n\n### Added\n- New widget\n");
        File::put($unreleasedDir.'/bugfix-b.md', "---\nauthor: Faruk Can\ndate: 2026-07-09\n---\n\n### Fixed\n- Crash on launch\n");
        File::put($mainFile, "# Changelog\n\n## [Unreleased]\n\n## [0.4.0] - 0.4.0\n- Old entry\n");

        $this->artisan('publish')
            ->expectsOutputToContain('Changelogs published successfully.')
            ->expectsOutputToContain('feature-a.md')
            ->expectsOutputToContain('bugfix-b.md')
            ->expectsOutputToContain($mainFile)
            ->assertExitCode(0);
    }

    public function testItWarnsWhenNoUnreleasedChangelogExists(): void
    {
        File::ensureDirectoryExists(config('app.structure.unreleased'));
        File::cleanDirectory(config('app.structure.unreleased'));

        $this->artisan('publish')
            ->expectsOutputToContain('No unreleased changelogs to publish.')
            ->assertExitCode(0);
    }

    public function testItWarnsWhenUnreleasedDirectoryIsMissing(): void
    {
        File::deleteDirectory(config('app.structure.unreleased'));

        $this->artisan('publish')
            ->expectsOutputToContain('No unreleased changelogs to publish.')
            ->assertExitCode(0);
    }
}
