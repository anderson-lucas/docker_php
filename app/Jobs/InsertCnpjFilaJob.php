<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NexTime\Modules\Core\Util\Util;

class InsertCnpjFilaJob implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, Queueable;

    private $tabela_imp = '';
    private $campos_cnpj = [];

    public function __construct(String $tabela_imp, array $campos_cnpj)
    {
        $this->tabela_imp = $tabela_imp;
        $this->campos_cnpj = $campos_cnpj;
    }

    public function handle()
    {
        try {
            echo "[Inicio] InsertCnpjFilaJob: $this->tabela_imp" . PHP_EOL;

            $cnpjs_inseridos = 0;
            foreach ($this->campos_cnpj as $campo) {
                $cnpjotas = DB::select("SELECT $campo AS cnpj
                            FROM $this->tabela_imp
                            WHERE NOT EXISTS (SELECT 1 FROM nt_filial WHERE cnpj = $campo)
                            AND NOT EXISTS (SELECT 1 FROM nt_fila_cnpj WHERE cnpj = $campo)
                            GROUP BY $campo");

                if (count($cnpjotas) > 0) {
                    foreach ($cnpjotas as $key => $value) {
                        $cnpjotas[$key] = (array) $value;
                        if (!Util::cnpjValido($value->cnpj)) {
                            unset($cnpjotas[$key]);
                        }

                    }

                    DB::table('nt_fila_cnpj')->insert($cnpjotas);
                    $cnpjs_inseridos += count($cnpjotas);
                }
            }
            echo $cnpjs_inseridos . ' cnpjs inseridos na fila', PHP_EOL;
            echo "[Fim] InsertCnpjFilaJob: $this->tabela_imp" . PHP_EOL;
        } catch (\Exception $e) {
            Log::error("InsertCnpjFilaJob: " . $e->getMessage());
        }
    }
}
