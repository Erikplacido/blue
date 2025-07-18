<?php
namespace Src\Services;

class BreakFeeCalculator
{
    /**
     * @param array $booking  resultado de Booking::getById()
     * @return float         valor da multa em unidade monetária
     */
    public static function calculate(array $booking): float
    {
        // 1) Quantas execuções ainda faltam
        $remaining = (int) $booking['remaining_executions'];

        // 2) Valor base por execução
        $pricePerExec = (float) $booking['total_price'];

        // 3) Percentual de multa
        $rate = (float) $booking['break_fee_rate'];

        // 4) Cálculo da multa proporcional
        $fee = $remaining * $pricePerExec * ($rate / 100.0);

        return round($fee, 2);
    }
}