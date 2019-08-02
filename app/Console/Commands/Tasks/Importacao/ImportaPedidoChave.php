<?php

namespace App\Console\Commands\Tasks\Importacao;

use App\Console\Commands\Tasks\Importacao\NxImportador;
use App\Jobs\AtualizaJob;
use App\Utils\Filas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportaPedidoChave extends NxImportador
{
    protected $signature = 'nextime:importa_pedido_chave';
    protected $description = 'Realiza a importação para a tabela imp_pedido_chave';
    protected $appName = 'importa_pedido_chave';
    protected $appNameAtualiza = 'atualiza_pedido_chave';
    protected $tabela = 'imp_pedido_chave';

    public function handle()
    {
        $this->init();
        $this->process();
        $this->finish();
    }

    public function process()
    {
        try {
            foreach ($this->entidades as $entidade) {
                echo $entidade->nome . PHP_EOL;
                $this->entidadeAtual = $entidade->nome;
                $response = $this->getData($entidade->link_integracao, $entidade->sql);
                echo 'Importando ' . count($response) . ' registros.', PHP_EOL;

                if (count($response) > 0) {
                    DB::table($this->tabela)->where('id_entidade', $entidade->id)->delete();

                    foreach ($response as $item) {
                        $item['id_entidade'] = $entidade->id;

                        if (isset($item['data_hora_emissao_nota'])) {
                            $item['data_hora_emissao_nota'] = Carbon::parse($item['data_hora_emissao_nota'])->format('Y-m-d H:i:s');
                        }

                        if (isset($item['data_hora_recebimento_nota'])) {
                            $item['data_hora_recebimento_nota'] = Carbon::parse($item['data_hora_recebimento_nota'])->format('Y-m-d H:i:s');
                        }

                        if (isset($item['data_hora_emissao_pedido'])) {
                            $item['data_hora_emissao_pedido'] = Carbon::parse($item['data_hora_emissao_pedido'])->format('Y-m-d H:i:s');
                        }

                        if (isset($item['data_hora_prev_pedido'])) {
                            $item['data_hora_prev_pedido'] = Carbon::parse($item['data_hora_prev_pedido'])->format('Y-m-d H:i:s');
                        }

                        $this->addQueryToBuffer($item);
                    }

                    $job = (new AtualizaJob($this->appNameAtualiza, $entidade))->onQueue(Filas::getQueue(Filas::TIPO_INTEGRACAO_IMP));
                    dispatch($job);
                }
            }
        } catch (\Exception $e) {
            Log::error("\nComando: $this->signature\nEntidade: $this->entidadeAtual\nErro process NxImportador: " . $e->getMessage());
        }
        $this->flushBulk();
    }
}
