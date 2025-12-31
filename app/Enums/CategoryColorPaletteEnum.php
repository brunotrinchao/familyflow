<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CategoryColorPaletteEnum: string implements HasLabel, HasColor
{
    // Primeira Linha
    case PinkShock = '#F06292';      // Rosa Choque
    case LavenderBlue = '#7961C4';   // Azul Lavanda
    case DarkIndigo = '#3F44AF';     // Azul Índigo Escuro
    case SkyBlue = '#5599DC';        // Azul Céu
    case MagentaPink = '#C44D97';    // Rosa Magenta
    case VibrantCoral = '#FF6861';   // Coral Vibrante
    case SalmonPink = '#FFA1A1';     // Rosa Salmão
    case NavyBlue = '#3F61AC';       // Azul Marinho
    case LightMintGreen = '#7FD57F'; // Verde Menta Claro
    case SoftPeach = '#FFB7A1';      // Pêssego Suave
    case BabyPink = '#FFC0C4';       // Rosa Bebê

    // Segunda Linha
    case ForestGreen = '#4B8855';    // Verde Floresta
    case LightOrange = '#F8A87F';    // Laranja Claro
    case GoldenYellow = '#F5BC53';   // Amarelo Ouro
    case EarthBrown = '#A34F2C';     // Marrom Terra
    case GrayishBlue = '#91B9E0';    // Azul Acinzentado
    case LightGray = '#A8A8A8';      // Cinza Claro
    case AquaGreen = '#4DCCA2';      // Verde Água
    case DarkTeal = '#1C624F';       // Verde Petróleo
    case MintPastel = '#A5E9C8';     // Verde Menta Pastel
    case CherryRed = '#DB3C44';      // Vermelho Cereja

    case Invoice = '#7e8187';

    public function getLabel(): ?string
    {
        // Retorna um nome descritivo para o Filament (usando os nomes em português como base)
        return match ($this) {
            self::PinkShock => 'Rosa Choque',
            self::LavenderBlue => 'Azul Lavanda',
            self::DarkIndigo => 'Azul Índigo Escuro',
            self::SkyBlue => 'Azul Céu',
            self::MagentaPink => 'Rosa Magenta',
            self::VibrantCoral => 'Coral Vibrante',
            self::SalmonPink => 'Rosa Salmão',
            self::NavyBlue => 'Azul Marinho',
            self::LightMintGreen => 'Verde Menta Claro',
            self::SoftPeach => 'Pêssego Suave',
            self::BabyPink => 'Rosa Bebê',
            self::ForestGreen => 'Verde Floresta',
            self::LightOrange => 'Laranja Claro',
            self::GoldenYellow => 'Amarelo Ouro',
            self::EarthBrown => 'Marrom Terra',
            self::GrayishBlue => 'Azul Acinzentado',
            self::LightGray => 'Cinza Claro',
            self::AquaGreen => 'Verde Água',
            self::DarkTeal => 'Verde Petróleo',
            self::MintPastel => 'Verde Menta Pastel',
            self::CherryRed => 'Vermelho Cereja',
        };
    }

    public function getColor(): string|array|null
    {
        // Retorna o código hexadecimal como valor de cor para o Filament
        return $this->value;
    }

    public static function getColorsList(): array
    {
        $colors = [];

        // Itera sobre todos os membros do enum
        foreach (self::cases() as $case) {
            // Usa o getLabel() como a chave do array (rótulo em português)
            $label = $case->getLabel();

            // Usa o valor de backing ($this->value) como o valor do array (HEX)
            $hexValue = $case->value;

            $colors[$hexValue] = $hexValue;
        }

        return $colors;
    }

    public static function getRandomColorHex(): string
    {
        // 1. Obtém todos os membros do Enum como um array de objetos Case
        $cases = self::cases();

        // 2. Se o Enum estiver vazio, retorna uma cor padrão ou lança uma exceção
        if (empty($cases)) {
            return '#000000'; // Cor preta como fallback seguro
        }

        // 3. Seleciona um membro aleatório
        $randomCase = $cases[array_rand($cases)];

        // 4. Retorna o valor de backing (o código HEX)
        return $randomCase->value;
    }
}
