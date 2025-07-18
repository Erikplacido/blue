<?php
namespace Src\Services;

class ContractCalculator
{
    /**
     * Calcula o total de execuções (contract_length)
     * 
     * @param string $recurrence  'one-time', 'weekly', 'fortnightly' ou 'monthly'
     * @param int    $months      número de meses (3, 6 ou 12), ou 1 para one-time
     * @return int
     */
    public static function calculate(string $recurrence, int $months): int
    {
        // quantas vezes por mês
        $map = [
            'one-time'    => 1,
            'weekly'      => 4,
            'fortnightly' => 2,
            'monthly'     => 1,
        ];

        $perMonth = $map[$recurrence] ?? 1;

        // one-time sempre 1 execução
        if ($recurrence === 'one-time') {
            return 1;
        }

        return $perMonth * $months;
    }
}