<?php

namespace App\Utils;

use Illuminate\Support\Facades\DB;

class Filas
{
    const TIPO_CONFERENCIA = 1;
    const TIPO_INTEGRACAO_IMP = 2;
    const TIPO_INTEGRACAO_CNPJ = 3;
    const TIPO_INTEGRACAO_SEFAZ = 4;
    const TIPO_DOWNLOAD_SEFAZ = 5;
    const TIPO_ENVIA_DANFE = 6;
    const TIPO_LEITURA_EMAIL = 7;

    public static function getQueue(int $tipo)
    {
        return DB::table('nt_filas as f')
            ->selectRaw('f.queue, (SELECT COUNT(1) FROM nt_fila_execucao AS fe WHERE fe.queue = f.queue)')
            ->where('f.tipo', $tipo)
            ->orderByRaw(2)
            ->value('queue');
    }
}
