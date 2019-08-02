<?php

namespace NexTime\Console\Commands\Tasks\Importacao;

use NexTime\Console\Commands\Tasks\Importacao\NxImportador;

class ImportaReferenciaFornecedor extends NxImportador
{
    protected $signature = 'nextime:importa_referencia_fornecedor';
    protected $description = 'Realiza a importação para a tabela imp_referencia_fornecedor';
    protected $appName = 'importa_referencia_fornecedor';
    protected $appNameAtualiza = 'atualiza_referencia_fornecedor';
    protected $tabela = 'imp_referencia_fornecedor';

    public function handle()
    {
        $this->init();
        $this->process();
        $this->finish();
    }
}
