<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BankEnum: string implements HasLabel
{
    // O valor do case é o Código COMPE/ISPB (o que é mais comum em sistemas)
    case BANCO_DO_BRASIL = '001';
    case CAIXA_ECONOMICA = '104';
    case BRADESCO = '237';
    case ITAU_UNIBANCO = '341';
    case SANTANDER = '033';
    case NUBANK = '260';
    case INTER = '077';
    case C6_BANK = '336';

    /**
     * Retorna o nome completo e formal do banco.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BANCO_DO_BRASIL => 'Banco do Brasil S.A.',
            self::CAIXA_ECONOMICA => 'Caixa Econômica Federal',
            self::BRADESCO => 'Banco Bradesco S.A.',
            self::ITAU_UNIBANCO => 'Itaú Unibanco S.A.',
            self::SANTANDER => 'Banco Santander (Brasil) S.A.',
            self::NUBANK => 'Nu Pagamentos S.A.',
            self::INTER => 'Banco Inter S.A.',
            self::C6_BANK => 'C6 Bank S.A.',
        };
    }

    /**
     * Retorna o código do banco (o valor de backing do Enum).
     */
    public function codigo(): string
    {
        return $this->value; // Retorna o '001', '104', etc.
    }

    /**
     * Retorna um identificador amigável (slug) para uso em URLs ou classes CSS.
     */
    public function slug(): string
    {
        return match ($this) {
            self::BANCO_DO_BRASIL => 'banco-do-brasil',
            self::CAIXA_ECONOMICA => 'caixa-economica',
            self::BRADESCO => 'bradesco',
            self::ITAU_UNIBANCO => 'itau',
            self::SANTANDER => 'santander',
            self::NUBANK => 'nubank',
            self::INTER => 'inter',
            self::C6_BANK => 'c6-bank',
        };
    }
}
