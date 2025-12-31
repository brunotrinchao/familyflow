<?php

namespace App\DTO;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class ImportedInvoiceDTO
{
    /**
     * @param Collection<int, InvoiceTransactionDTO> $transactions
     */
    public function __construct(
       // Dados do Cartão
        public string $name,             // Ex: "PicPay Card"
        public ?string $brand = null,            // Ex: "Mastercard"
        public string $lastFourDigits,
        public int $closingDay,          // Extraído do dia da data_fechamento
        public int $dueDay,              // Extraído do dia da data_vencimento
        public int $limit = 0,             // vindo de encargos_e_limites.limites_disponiveis.total
        public int $used = 0,
        public Carbon $dueDateInvoice,

        // Dados da Conta (Emitente/Beneficiário)
        public ?string $bankName = null,
        public ?string $bankCnpj = null,

        public Collection $transactions,
        public float $totalAmount,
    )
    {
    }

}
