<?php

namespace App\Services\Invoice;

class InvoiceParserFactory
{
    protected array $drivers = [
        PicPayDriver::class,
//        NubankDriver::class,
//        InterDriver::class,
    ];

    public function make(string $text): InvoiceDriver
    {
        foreach ($this->drivers as $driverClass) {
            $driver = app($driverClass);
            if ($driver->canParse($text)) {
                return $driver;
            }
        }

        throw new \Exception("Nenhum driver compatível encontrado para este PDF.");
    }
}
