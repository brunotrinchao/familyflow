<?php

namespace App\Services;

use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;

class AiInvoiceScannerService
{


    public function scanPdf(string $filePath): array
    {
        $fileData = base64_encode(file_get_contents($filePath));
        $result = Gemini::generativeModel(model: 'gemini-2.0-flash')
            ->generateContent([
                $this->getPrompt(),
                new Blob(
                    mimeType: MimeType::APPLICATION_PDF,
                    data    : base64_encode(file_get_contents($filePath))
                )
            ]);

        $data =  json_decode($result->text(), true) ?? [];
        dd($data);
        return $data;
    }

    private function getPrompt(): string
    {
        return "Extraia os lançamentos desta fatura de cartão de crédito.
                Retorne EXCLUSIVAMENTE um objeto JSON com esta estrutura:
                {
                    'invoice_details': { 'due_date': 'YYYY-MM-DD', 'total_amount': 0.00, 'data_card' => [] },
                    'items': [
                        { 'date': 'YYYY-MM-DD', 'description': 'Nome do estabelecimento', 'amount': 0.00 }
                    ]
                }
                Ignore pagamentos da fatura anterior, estornos ou créditos. Foque apenas em despesas.";
    }
}
