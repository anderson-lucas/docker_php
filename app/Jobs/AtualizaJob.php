<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use NexTime\Modules\Core\Entidade\Model\Entidade;

class AtualizaJob implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, Queueable;

    private $appNameAtualiza;
    private $entidade;

    public function __construct($appNameAtualiza, Entidade $entidade)
    {
        $this->appNameAtualiza = $appNameAtualiza;
        $this->entidade = $entidade;
    }

    public function handle()
    {
        try {
            echo $this->entidade->nome . "-> Atualizar Job - Command: $this->appNameAtualiza" . PHP_EOL;
            Artisan::call('nextime:' . $this->appNameAtualiza, [
                'id_entidade' => $this->entidade->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Atualiza Job Exception: " . $e->getMessage() . "Entidade: " . $this->entidade->nome);
        }
    }
}
