<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\ScalableIcon;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;
use JaOcero\RadioDeck\Contracts\HasDescriptions;
use JaOcero\RadioDeck\Contracts\HasIcons;
use Filafly\Icons\Iconoir\Enums\Iconoir;

enum CategoryIconEnum: string implements HasIcons, HasIcon, ScalableIcon
{
    // --- RECEITAS / RENDA ---
    case Salary = Iconoir::Bank->value;                   // Icone de Banco para Salário
    case Bonus = Iconoir::LotOfCash->value;               // Icone de Dinheiro para Bônus
    case OtherIncome = Iconoir::Coins->value;             // Icone de Moedas para Outras Receitas
    case AssetSale = Iconoir::HomeSale->value;            // Icone de Venda de Imóvel/Ativo

    // --- MORADIA / HABITAÇÃO ---
    case Rent = Iconoir::Home->value;                     // Icone de Casa para Moradia/Aluguel
    case Electricity = Iconoir::LightBulbOn->value;       // Icone de Lâmpada para Eletricidade
    case Water = Iconoir::Droplet->value;                 // Icone de Gota para Água
    case Internet = Iconoir::Antenna->value;              // Icone de Antena para Internet
    case Waste = Iconoir::Trash->value;                   // Icone de Lixo para Taxas/Lixo

    // --- ALIMENTAÇÃO ---
    case Groceries = Iconoir::Cart->value;                // Carrinho para Supermercado
    case Restaurants = Iconoir::Cutlery->value;           // Talheres para Restaurantes
    case Coffee = Iconoir::CoffeeCup->value;              // Xícara para Cafeteria

    // --- TRANSPORTE / VEÍCULOS ---
    case VehiclePayment = Iconoir::Car->value;            // Carro para Pagamento
    case Fuel = Iconoir::Gas->value;                      // Bomba de Gás para Combustível
    case PublicTransport = Iconoir::DeliveryTruck->value; // Caminhão para Transporte Público
    case Travel = Iconoir::Globe->value;                  // Globo para Viagens

    // --- SAÚDE / PESSOAL ---
    case HealthInsurance = Iconoir::HealthShield->value;  // Escudo para Seguro Saúde
    case Doctor = Iconoir::Hospital->value;               // Hospital para Consultas
    case Pharmacy = Iconoir::PharmacyCrossTag->value;     // Cruz para Farmácia
    case Clothing = Iconoir::Shirt->value;                // Camisa para Vestuário
    case Beauty = Iconoir::Scissor->value;                // Tesoura para Estética

    // --- EDUCAÇÃO / DÍVIDAS ---
    case Tuition = Iconoir::GraduationCap->value;         // Chapéu para Mensalidades
    case Loans = Iconoir::Percentage->value;              // Porcentagem para Juros/Empréstimos
    case Investments = Iconoir::PiggyBank->value;         // Cofrinho para Investimentos
    case CreditCardPayment = Iconoir::CreditCard->value;  // Cartão para Pagamento de Fatura

    // --- LAZER / DIVERSOS ---
    case Entertainment = Iconoir::Movie->value;           // Filme para Streaming/Cinema
    case Fitness = Iconoir::Gym->value;                   // Ginásio para Academia
    case GeneralMisc = Iconoir::Cube->value;              // Cubo para Diversos
    case Unknown = Iconoir::QuestionMark->value;          // Interrogação para Não Classificado

    case Notes = Iconoir::Notes->value;

    case CrediCards = Iconoir::CreditCards->value;

    case ShoppingBag = Iconoir::ShoppingBag->value;

    case Transfer = Iconoir::DataTransferBoth->value;

    case TransferDown = Iconoir::ReceiveDollars->value;

    case TransferUp = Iconoir::SendDollars->value;


    // ----------------------------------------------------------------------
    // Implementação das Interfaces Filament
    // ----------------------------------------------------------------------

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Salary => 'Salário/Renda',
            self::Bonus => 'Bônus/Renda Extra',
            self::OtherIncome => 'Outras Receitas',
            self::AssetSale => 'Venda de Ativos',
            self::Rent => 'Aluguel/Moradia',
            self::Electricity => 'Eletricidade',
            self::Water => 'Água/Saneamento',
            self::Internet => 'Internet/TV',
            self::Waste => 'Taxas/Lixo',
            self::Groceries => 'Supermercado',
            self::Restaurants => 'Restaurantes',
            self::Coffee => 'Cafeteria/Lanches',
            self::VehiclePayment => 'Automóvel/Parcela',
            self::Fuel => 'Combustível',
            self::PublicTransport => 'Transporte Público',
            self::Travel => 'Viagens/Turismo',
            self::HealthInsurance => 'Seguro Saúde',
            self::Doctor => 'Consultas Médicas',
            self::Pharmacy => 'Farmácia/Remédios',
            self::Clothing => 'Vestuário',
            self::Beauty => 'Estética/Beleza',
            self::Tuition => 'Educação/Mensalidades',
            self::Loans => 'Juros/Empréstimos',
            self::Investments => 'Investimentos',
            self::CreditCardPayment => 'Pagamento de Fatura',
            self::Entertainment => 'Streaming/Lazer',
            self::Fitness => 'Academia/Fitness',
            self::GeneralMisc => 'Diversos',
            self::Unknown => 'Não Classificado',
            self::Notes => 'Faturas',
            self::CrediCards => 'Saldo devedor',
            self::ShoppingBag => 'Compra',
            self::Transfer => 'Transfência',
            self::TransferUp => 'Recebe tranfeência',
            self::TransferDown => 'Envia tranfeência',
        };
    }

    public function getIcon(): ?string
    {
        // Retorna o valor de backing (a string do Iconoir)
        return "iconoir-$this->value";
    }

    public function getIcons(): ?string
    {
        // Retorna o valor de backing (a string do Iconoir)
        return "iconoir-$this->value";
    }

    public function getIconForSize(IconSize $size): string
    {
        return match ($size) {
            default => "iconoir-$this->value",
        };
    }

    public static function randomName(): string
    {
        $arr = array_column(self::cases(), 'name');

        return $arr[array_rand($arr)];
    }

    public static function randomValue(): string
    {
        // 1. Obtém todos os membros do Enum como um array de objetos Case
        $cases = self::cases();

        // 3. Seleciona um índice aleatório do array de cases
        $randomIndex = array_rand($cases);

        // 4. Acessa o objeto Case aleatório
        $randomCase = $cases[$randomIndex];

        // 5. Retorna o valor de backing (o código HEX)
        return $randomCase->value; // 🚨 CORREÇÃO: Acessa a propriedade 'value'
    }

    public function getType(): ?CategoryTypeEnum
    {
        foreach (self::getDefault() as $categoryType => $iconList) {

            // 2. Verifica se a instância atual do ícone ($this) está na lista
            if (in_array($this, $iconList, true)) {

                // 3. Se encontrado, retorna a instância do CategoryTypeEnum
                return CategoryTypeEnum::from($categoryType);
            }
        }

        // Se não for encontrado em nenhuma lista (erro ou ícone neutro)
        return null;
    }

    public static function getDefault(): array
    {
        return [
            CategoryTypeEnum::EXPENSE->value => [
                self::Rent,
                self::Electricity,
                self::Water,
                self::Internet,
                self::Groceries,
                self::Restaurants,
                self::VehiclePayment,
                self::Fuel,
                self::PublicTransport,
                self::Travel,
                self::Doctor,
                self::Pharmacy,
                self::Clothing,
                self::CreditCardPayment,
                self::ShoppingBag,
            ],
            CategoryTypeEnum::INCOME->value  => [
                self::Salary,
                self::Bonus,
                self::OtherIncome,
                self::AssetSale,
                self::Investments,
            ],
        ];
    }
}
