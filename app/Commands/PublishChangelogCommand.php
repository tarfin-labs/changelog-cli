<?php

namespace App\Commands;

use App\Changelog;
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

        if ($files) {
            $changelog->appendCategories();
        }

        foreach ($files as $file) {
            $filePath = config('app.structure.unreleased') . DIRECTORY_SEPARATOR . $file->getFilename();

            $changelog->publishFileContent($filePath);
        }

        $this->info('Changelogs successfully published to unreleased.');
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
