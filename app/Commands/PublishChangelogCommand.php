<?php

namespace ChangelogCLI\Commands;

use ChangelogCLI\Changelog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class PublishChangelogCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'publish';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Publish all unreleased changelogs.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Changelog $changelog)
    {
        $files = File::allFiles(config('app.structure.unreleased'));

        if (empty($files)) {
            $this->components->warn('No unreleased changelogs to publish.');

            return;
        }

        $changelog->appendCategories();

        foreach ($files as $file) {
            $filePath = config('app.structure.unreleased') . DIRECTORY_SEPARATOR . $file->getRelativePathname();

            $changelog->publishFileContent($filePath);
        }

        $changelog->removeEmptyCategories();

        $this->components->info('Changelogs published successfully.');

        foreach ($files as $file) {
            $this->components->twoColumnDetail('Published', $file->getRelativePathname());
        }

        $this->components->twoColumnDetail('Target', config('app.structure.main'));
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
