<?php
namespace NexTime\Console\Commands\Tasks\Importacao;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kozz\Laravel\Facades\Guzzle;
use NexTime\Modules\Core\Entidade\Model\Entidade;
use NexTime\Modules\Core\Scheduler\SchedulerConfig\Model\SchedulerConfig;
use NexTime\Jobs\AtualizaJob;
use NexTime\Modules\Core\Base\NxResponse;
use NexTime\Modules\Filas\Filas\Model\Filas;
use Carbon\Carbon;

class NxImportador extends Command
{
    protected $accumulator = 0;
    protected $bulkCount = 0;
    protected $queryBuffer = [];
    protected $queryBulkLimit = 50;
    protected $entidades;
    protected $command;
    protected $entidadeAtual;

    protected function init()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        echo "[" . date('H:i:s') . "] Inicio tarefa" . PHP_EOL;

        $this->entidades = Entidade::where('cliente', 'S')
                            ->whereExists(function ($query) {
                                $query->select(DB::raw(1))
                                    ->from('nt_configuracoes')
                                    ->whereRaw('nt_configuracoes.id_entidade = nt_entidade.id')
                                    ->where('chave', $this->appName.'_sql');
                            })
                            ->whereExists(function ($query) {
                                $query->select(DB::raw(1))
                                    ->from('nt_configuracoes')
                                    ->whereRaw('nt_configuracoes.id_entidade = nt_entidade.id')
                                    ->where('chave', 'link_integracao');
                            })
                            ->orderBy('nome')
                            ->get();

        foreach ($this->entidades as $entidade) {
            $entidade->sql = $this->getConfigValue($entidade->id, $this->appName.'_sql');
            $entidade->link_integracao = $this->getConfigValue($entidade->id, 'link_integracao');
        }
    }

    public function process()
    {
        try {
            foreach ($this->entidades as $entidade) {
                echo $entidade->nome, PHP_EOL;
                $this->entidadeAtual = $entidade->nome;
                $response = $this->getData($entidade->link_integracao, $entidade->sql);
                echo 'Importando '.count($response).' registros.', PHP_EOL;

                if (count($response) > 0) {
                    DB::table($this->tabela)->where('id_entidade', $entidade->id)->delete();

                    foreach ($response as $item) {
                        $item['id_entidade'] = $entidade->id;
                        $this->addQueryToBuffer($item);
                    }

                    $job = (new AtualizaJob($this->appNameAtualiza, $entidade))->onQueue(Filas::getQueue(Filas::TIPO_INTEGRACAO_IMP));
                    dispatch($job);
                }
            }
        } catch (\Exception $e) {
            Log::error("\nComando: $this->signature\nEntidade: $this->entidadeAtual\nErro process NxImportador: ".$e->getMessage());
        }
        $this->flushBulk();
    }

    protected function addQueryToBuffer($obj)
    {
        $this->queryBuffer[] = $obj;
        $this->accumulator++;
        if ($this->accumulator >= $this->queryBulkLimit) {
            $this->flushBulk();
        }
    }

    protected function flushBulk()
    {
        $values = [];
        foreach ($this->queryBuffer as $key => $item) {
            $values[] = array_change_key_case((array) $item, CASE_LOWER);
        }

        if (!count($values)) return;

        try {
            DB::table($this->tabela)->insert($values);
        } catch (\Exception $e) {
            Log::error("\nComando: $this->signature\nEntidade: $this->entidadeAtual\nErro flushBulk NxImportador: ".$e->getMessage());
        }

        $this->accumulator = 0;
        $this->queryBuffer = [];
        $this->bulkCount++;
    }

    public function apiProcess(Array $post, Entidade $entidade)
    {
        return 'ok';
        // $response = $post['data'];
        // $nxResponse = new NxResponse();
        
        // if (count($response) == 0) return $nxResponse->ResponseXml(['erro' => 'Empty data']);

        // try {
        //     //VAI LIMPAR ANTES DE INSERIR?
        //     foreach ($response as $k => $item) {
        //         $response[$k]['id_entidade'] = $entidade->id;
        //     }
        //     DB::table($this->tabela)->insert($response);
        //     dispatch(new AtualizaJob($this->tabela, $this->tabela_atualiza, $this->campos_atualiza, $entidade));
        //     return $nxResponse->ResponseXml(['successo' => count($response)." registros importados"]);
        // } catch (\Exception $e) {
        //     Log::error("\nImportação via api: $this->signature\nEntidade: $entidade->nome\nErro: ".$e->getMessage());
        //     return $nxResponse->ResponseXml(['erro' => 'Erro na importação']);
        // }
    }

    protected function getConfigValue($id_entidade, $chave)
    {
        $config =  DB::table('nt_configuracoes')
                ->where('id_entidade', $id_entidade)
                ->where('chave', $chave)
                ->select('valor')
                ->first();
        return $config->valor;
    }

    protected function getData($url, $sql)
    {   
        try {
            $request = Guzzle::request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Nxll B17Gl0r1aAD3Ux&P4u10Gu3D3S==',
                    'Content-Type' => 'application/json'
                ],
                'json' => ['sql' => $sql]
            ]);
            $response = json_decode((string)$request->getBody(), true);
        } catch (\Exception $e) {
            $response = [];
            Log::error("\nComando: $this->signature\nEntidade: $this->entidadeAtual\nErro getData NxImportador: ".$e->getMessage());
        }

        return is_array($response) ? $response : [];
    }

    public function finish()
    {
        echo "[" . date('H:i:s') . "] Fim tarefa" . PHP_EOL;
    }
}
