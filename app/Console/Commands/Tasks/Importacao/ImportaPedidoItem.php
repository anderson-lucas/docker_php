<?php

namespace App\Console\Commands\Tasks\Importacao;

use App\Console\Commands\Tasks\Importacao\NxImportador;

class ImportaPedidoItem extends NxImportador
{
    protected $signature = 'nextime:importa_pedido_item';
    protected $description = 'Realiza a importação para a tabela imp_pedido_item';
    protected $appName = 'importa_pedido_item';
    protected $appNameAtualiza = 'atualiza_pedido_item';
    protected $tabela = 'imp_pedido_item';

    public function handle()
    {
        $this->init();
        $this->process();
        $this->finish();
    }
}
