<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TransactionHistoryFormatter
{
    /**
     * Formata linhas do histórico de transações.
     *
     * @param Collection $rows
     * @return Collection
     */
    public static function format(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $amount = ($row->amount > 0 ? $row->amount : $row->amount * -1);
            $item = [
                'id' => $row->id,
                'type' => $row->type,
                'created_at' => $row->created_at,
                'amount' => (float) $amount ,
            ];

            if (!is_null($row->recipient)) {
                $item['recipient'] = $row->recipient;
            }
            if (!is_null($row->sender)) {
                $item['sender'] = $row->sender;
            }

            return $item;
        })->values();
    }
}
