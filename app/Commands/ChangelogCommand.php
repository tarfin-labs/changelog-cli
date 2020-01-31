<?php

namespace App\Commands;

use App\Changelog;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ChangelogCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'changelog:create';

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
    public function handle(Changelog $changelog)
    {
        $option = $this->menu($changelog->menuName, $changelog->menuItems)
            ->setForegroundColour('black')
            ->open();

        $changelog->execute($option);

        $this->info("#{$changelog->menuItems[$option]} changelog file successfully created.");
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
