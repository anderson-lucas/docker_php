<?php

namespace App\Console\Commands\Tasks\Importacao;

use App\Console\Commands\Tasks\Importacao\NxImportador;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// use NexTime\Jobs\AtualizaJob;
// use NexTime\Jobs\InsertCnpjFilaJob;
use NexTime\Modules\Filas\Filas\Model\Filas;

class ImportaPedidoSumario extends NxImportador
{
    protected $signature = 'nextime:importa_pedido_sumario';
    protected $description = 'Realiza a importação para a tabela imp_pedido_sumario';
    protected $appName = 'importa_pedido_sumario';
    protected $appNameAtualiza = 'atualiza_pedido_sumario';
    protected $tabela = 'imp_pedido_sumario';

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

                        if (isset($item['data_hora_emissao_pedido'])) {
                            $item['data_hora_emissao_pedido'] = Carbon::parse($item['data_hora_emissao_pedido'])->format('Y-m-d H:i:s');
                        }

                        if (isset($item['data_previsao_entrega'])) {
                            $item['data_previsao_entrega'] = Carbon::parse($item['data_previsao_entrega'])->format('Y-m-d H:i:s');
                        }

                        if (isset($item['data_hora_criacao'])) {
                            $item['data_hora_criacao'] = Carbon::parse($item['data_hora_criacao'])->format('Y-m-d H:i:s');
                        }

                        if (isset($item['data_hora_atualizacao'])) {
                            $item['data_hora_atualizacao'] = Carbon::parse($item['data_hora_atualizacao'])->format('Y-m-d H:i:s');
                        }

                        $this->addQueryToBuffer($item);
                    }

                    $job = (new InsertCnpjFilaJob($this->tabela, ['cnpj_filial_entrega', 'cnpj_fornecedor']))->onQueue(Filas::getQueue(Filas::TIPO_INTEGRACAO_CNPJ));
                    dispatch($job);

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
