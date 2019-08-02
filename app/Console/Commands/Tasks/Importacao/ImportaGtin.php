<?php

namespace App\Console\Commands\Tasks\Importacao;

use App\Console\Commands\Tasks\Importacao\NxImportador;

class ImportaGtin extends NxImportador
{
    protected $signature = 'nextime:importa_gtin';
    protected $description = 'Realiza a importação para a tabela imp_gtin';
    protected $appName = 'importa_gtin';
    protected $appNameAtualiza = 'atualiza_gtin';
    protected $tabela = 'imp_gtin';

    public function handle()
    {
        $this->init();
        $this->process();
        $this->finish();
    }
}
