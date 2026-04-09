<?php

namespace App\Console\Commands;

use Database\Seeders\EventSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate application data and seed users/events only';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Resetting application data...');

        $tables = array_filter(
            Schema::getTableListing(),
            fn (string $table): bool => $table !== 'migrations',
        );

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->call('db:seed', ['--class' => UserSeeder::class, '--no-interaction' => true]);
        $this->call('db:seed', ['--class' => EventSeeder::class, '--no-interaction' => true]);

        $this->components->info('Application data reset complete.');

        return self::SUCCESS;
    }
}
