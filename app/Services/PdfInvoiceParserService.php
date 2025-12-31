<?php

namespace App\Services;

use App\DTO\ImportedInvoiceDTO;
use App\Services\Invoice\InvoiceParserFactory;
use Gemini\Data\GenerationConfig;
use Smalot\PdfParser\Parser;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Enums\ResponseMimeType;

class PdfInvoiceParserService
{
    public function __construct(protected InvoiceParserFactory $factory) {}

    public function parse(string $filePath): ImportedInvoiceDTO
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $driver = $this->factory->make($text);
        return $driver->parse($text);

//        $generationConfig = new GenerationConfig(
//            responseMimeType: ResponseMimeType::APPLICATION_JSON,
//            temperature     : 1,
//        );
//
//        $result = Gemini::generativeModel(model: 'gemini-2.5-flash')
//            ->withGenerationConfig($generationConfig)
//            ->generateContent([
//                "Você é um extrator de dados bancários especializado em faturas de cartão de crédito.
//         Analise o texto da fatura e extraia os dados rigorosamente para o formato JSON abaixo.
//         Ignore propagandas e foque apenas nos dados do cliente, cartão, valores e transações.
//
//         ESTRUTURA DESEJADA:
//         {
//  'fatura': {
//    'cliente': {
//      'nome': '',
//      'cpf': '',
//      'endereco': { 'rua': '', 'numero': '', 'complemento': '', 'bairro': '', 'cep': '', 'cidade': '', 'estado': '' }
//    },
//    'cartao': {
//      'bandeira': '',
//      'tipo': '',
//      'final_digitos': ''
//    },
//    'datas': {
//      'vencimento': 'YYYY-MM-DD',
//      'fechamento': 'YYYY-MM-DD',
//      'mes_referencia': ''
//    },
//    'resumo_valores': {
//      'total_da_fatura': 0.00,
//      'despesas_do_mes': 0.00,
//      'pagamentos_recebidos': 0.00
//    },
//    'boleto_informacoes': {
//      'beneficiario': { 'nome': '', 'cnpj': '' }
//    },
//    'encargos_e_limites': {
//      'limites_disponiveis': { 'total': 0.00 }
//    },
//    'transacoes': [
//       {
//         'data': 'YYYY-MM-DD',
//         'estabelecimento': '',
//         'valor': 0.00,
//         'cartao_final': '',
//         'parcelamento': {
//            'is_parcelado': true/false,
//            'parcela_atual': 1,
//            'parcela_total': 10,
//            'data_primeira_parcela': 'YYYY-MM-DD' // Calcule com base na parcela atual e data da compra
//         }
//       }
//    ]
//  }
//}
//
//Regras para parcelas:
//1. Se no estabelecimento houver algo como '02/10', 'PARC 05', deduza que is_parcelado é true.
//2. 'parcela_atual' é o número da parcela nesta fatura.
//3. 'parcela_total' é o total de vezes.
//4. 'data_primeira_parcela': Se hoje é a parcela 3, subtraia 2 meses da data da transação para estimar o início.",
//                $text
//            ]);
//        $data = json_decode($result->text(), true);
//
//        $dataInvoice = ImportedInvoiceDTO::fromAiResponse($data);
//
//        return $dataInvoice;
    }

    private function extractItems(string $text): array
    {
        $items = [];

        // Exemplo de Regex para capturar: DD/MM Descrição Valor
        // Este padrão varia por banco (Ex: Nubank, Inter, Itaú têm formatos diferentes)
        // Abaixo um padrão genérico para "DATA DESCRIÇÃO VALOR"
        $pattern = '/(\d{2}\/\d{2})\s+(.*?)\s+(-?[\d\.]+,\d{2})/';

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $items[] = [
                'date'        => $this->formatDate($match[1]),
                'description' => trim($match[2]),
                'amount'      => $match[3],
            ];
        }

        return $items;
    }

    private function formatDate(string $date): string
    {
        // Converte DD/MM para YYYY-MM-DD (assume o ano atual)
        $parts = explode('/', $date);
        return date('Y') . '-' . $parts[1] . '-' . $parts[0];
    }
}
