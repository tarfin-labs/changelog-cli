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
     * @return mixed
     */
    public function handle(Changelog $changelog): void
    {
        $option = $this->menu($changelog->menuName, $changelog->menuItems)
            ->setForegroundColour('green')
            ->setBackgroundColour('black')
            ->open();

        if (is_null($option)) {
            $this->info('Changelog closed!');
        } else {
            $changelog->execute($option);
            $this->notify("Changelog Cli", "#{$changelog->menuItems[$option]} changelog file successfully created.");
            $this->info("#{$changelog->menuItems[$option]} changelog file successfully created.");
        }
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
