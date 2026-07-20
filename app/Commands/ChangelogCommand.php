<?php

namespace ChangelogCLI\Commands;

use ChangelogCLI\Changelog;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ChangelogCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'create';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new changelog file';

    /**
     * Execute the console command.
     *
     * @param Changelog $changelog
     * @return int
     */
    public function handle(Changelog $changelog): int
    {
        $option = $this->openMenu($changelog);

        if (is_null($option)) {
            $this->components->warn('Changelog cancelled.');

            return self::SUCCESS;
        }

        return $this->createAndReport($changelog, $option);
    }

    /**
     * Create the changelog file and report the outcome.
     *
     * @return int
     */
    protected function createAndReport(Changelog $changelog, int $option): int
    {
        $created = $changelog->execute($option);

        if (! $created) {
            $this->components->error('Failed to create changelog file.');

            return self::FAILURE;
        }

        $this->notify('Changelog Cli', "#{$changelog->menuItems[$option]} changelog file successfully created.");
        $this->components->info(sprintf('Changelog [%s] created successfully.', $changelog->filePath()));

        if ($this->output->isVerbose()) {
            $this->components->twoColumnDetail('Category', $changelog->menuItems[$option]);
        }

        return self::SUCCESS;
    }

    /**
     * Open interactive category menu and return selected option key.
     */
    protected function openMenu(Changelog $changelog): ?int
    {
        return $this->menu($changelog->menuName, $changelog->menuItems)
            ->setForegroundColour('green')
            ->setBackgroundColour('black')
            ->open();
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
