<?php

namespace App\Console;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Tasks\Conferencia::class,
        Commands\Tasks\ImportaGtin::class,
        Commands\Tasks\ImportaPedidoChave::class,
        Commands\Tasks\ImportaPedidoItem::class,
        Commands\Tasks\ImportaPedidoSumario::class,
        Commands\Tasks\ImportaReferenciaFornecedor::class,
        Commands\Tasks\NexTimeCriaDanfeSefaz::class,
        Commands\Tasks\NexTimeDownloadSefaz::class,
        Commands\Tasks\NexTimeEmailReader::class,
        Commands\Tasks\NexTimeIntegracao::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedulers = DB::table('nt_scheduler_config')->where('ativo', true)->get();
        foreach ($schedulers as $config) {
            if ($config->without_overlapping) {
                $command = $schedule->command($config->command)->cron($config->cron)->withoutOverlapping();
            } else {
                $command = $schedule->command($config->command)->cron($config->cron);
            }

            $command->after(function () use ($config) {
                DB::table('nt_scheduler_config')->where('id', $config->id)->update(['ultima_execucao' => Carbon::now()]);
            });
        }
    }
}
