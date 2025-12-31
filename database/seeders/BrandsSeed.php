<?php

namespace Database\Seeders;

use App\Enums\StatusEnum;
use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandsSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $datas = [
            // --- BANCOS (Type: BANK) - Mais de 10 ---
            [ 'name' => 'Nubank', 'type' => 'BANK', 'icon_path' => 'brand/bank/nubank.svg'],
            [ 'name' => 'Itaú Unibanco', 'type' => 'BANK', 'icon_path' => 'brand/bank/itau.svg'],
            [ 'name' => 'Banco do Brasil', 'type' => 'BANK', 'icon_path' => 'brand/bank/bb.svg'],
            [ 'name' => 'Caixa Econômica Federal', 'type' => 'BANK', 'icon_path' => 'brand/bank/caixa.svg'],
            [ 'name' => 'Bradesco', 'type' => 'BANK', 'icon_path' => 'brand/bank/bradesco.svg'],
            [ 'name' => 'Santander', 'type' => 'BANK', 'icon_path' => 'brand/bank/santander.svg'],
            [ 'name' => 'C6 Bank', 'type' => 'BANK', 'icon_path' => 'brand/bank/c6.svg'],
            [ 'name' => 'Inter', 'type' => 'BANK', 'icon_path' => 'brand/bank/inter.svg'],
            [ 'name' => 'BTG Pactual', 'type' => 'BANK', 'icon_path' => 'brand/bank/btg.svg'],
            [ 'name' => 'Neon', 'type' => 'BANK', 'icon_path' => 'brand/bank/neon.svg'],
            [ 'name' => 'Sicoob', 'type' => 'BANK', 'icon_path' => 'brand/bank/sicoob.svg'],
            [ 'name' => 'PicPay', 'type' => 'BANK', 'icon_path' => 'brand/bank/picpay.svg'],

            // --- BANDEIRAS DE CARTÃO (Type: CREDITCARD) - Mais de 10 ---
            [ 'name' => 'Visa', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/visa.svg'],
            [ 'name' => 'Mastercard', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/mastercard.svg'],
            [ 'name' => 'Elo', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/elo.svg'],
            [ 'name' => 'American Express', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/amex.svg'],
            [ 'name' => 'Hipercard', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/hipercard.svg'],
            [ 'name' => 'Diners Club', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/diners.svg'],
            [ 'name' => 'Alelo', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/alelo.svg'], // Benefício
            [ 'name' => 'Sodexo', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/sodexo.svg'], // Benefício
            [ 'name' => 'Riachuelo', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/ticket.svg'], // Benefício
            [ 'name' => 'Caju', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/caju.svg'],
            [ 'name' => 'PicPay Card', 'type' => 'CREDITCARD', 'icon_path' => 'brand/creditcard/pic_pay_card.png'],
        ];

        $activeStatus = StatusEnum::ACTIVE->value;

        // 2. Mapeia os dados para incluir timestamps e status (para inserção em massa)
        $dataForInsertion = array_map(function ($item) use ($activeStatus) {
            $item['status'] = $activeStatus;
            return $item;
        }, $datas);

        // 3. Inserção em massa (Mais rápido que factory()->create() em um loop)
        DB::table('brands')->insert($dataForInsertion);
    }
}
