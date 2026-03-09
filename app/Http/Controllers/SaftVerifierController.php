<?php

namespace App\Http\Controllers;

use App\Services\SaftHashVerifierService;
use App\Services\SaftParserService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaftVerifierController extends Controller
{
    public function __construct(
        private readonly SaftParserService $parserService,
        private readonly SaftHashVerifierService $verifierService,
    ) {}

    /**
     * Show the upload form.
     */
    public function index(): View
    {
        return view('saft.upload');
    }

    /**
     * Process the uploaded SAF-T file and show verification results.
     */
    public function verify(Request $request): View
    {
        $request->validate([
            'saft_file' => [
                'required',
                'file',
                'mimes:xml',
                'max:' . (config('saft.max_file_size_kb', 102400)),
            ],
            'public_key' => [
                'nullable',
                'file',
                'mimes:pem,txt',
                'max:64',
            ],
        ], [
            'saft_file.required' => 'Por favor, selecione um ficheiro SAF-T PT.',
            'saft_file.file' => 'O ficheiro fornecido é inválido.',
            'saft_file.mimes' => 'O ficheiro deve ser um XML SAF-T PT (.xml).',
            'saft_file.max' => 'O ficheiro excede o tamanho máximo permitido de ' . config('saft.max_file_size_mb', 100) . 'MB.',
            'public_key.file' => 'A chave pública fornecida é inválida.',
            'public_key.mimes' => 'A chave pública deve ser um ficheiro PEM (.pem ou .txt).',
            'public_key.max' => 'O ficheiro de chave pública é demasiado grande.',
        ]);

        try {
            $xmlContent = file_get_contents($request->file('saft_file')->getRealPath());

            if ($xmlContent === false) {
                throw new \RuntimeException('Não foi possível ler o ficheiro SAF-T.');
            }

            // Parse public key if provided
            $publicKeyPem = null;
            if ($request->hasFile('public_key') && $request->file('public_key')->isValid()) {
                $publicKeyPem = file_get_contents($request->file('public_key')->getRealPath());
                if ($publicKeyPem === false) {
                    throw new \RuntimeException('Não foi possível ler o ficheiro de chave pública.');
                }
            }

            // Parse the SAF-T file
            $header = $this->parserService->parseHeader($xmlContent);
            $invoices = $this->parserService->parseInvoices($xmlContent);

            if (empty($invoices)) {
                return view('saft.results', [
                    'header' => $header,
                    'seriesResults' => [],
                    'totalDocuments' => 0,
                    'validDocuments' => 0,
                    'invalidDocuments' => 0,
                    'notVerifiableDocuments' => 0,
                    'hasPublicKey' => $publicKeyPem !== null,
                    'warning' => 'O ficheiro SAF-T não contém invoices na secção SalesInvoices.',
                ]);
            }

            // Verify the invoices
            $seriesResults = $this->verifierService->verify($invoices, $publicKeyPem);

            // Calculate totals
            $totalDocuments = 0;
            $validDocuments = 0;
            $invalidDocuments = 0;
            $notVerifiableDocuments = 0;

            foreach ($seriesResults as $seriesResult) {
                $totalDocuments += $seriesResult->totalDocuments;
                $validDocuments += $seriesResult->validDocuments;
                $invalidDocuments += $seriesResult->invalidDocuments;
                $notVerifiableDocuments += $seriesResult->notVerifiableDocuments;
            }

            return view('saft.results', [
                'header' => $header,
                'seriesResults' => $seriesResults,
                'totalDocuments' => $totalDocuments,
                'validDocuments' => $validDocuments,
                'invalidDocuments' => $invalidDocuments,
                'notVerifiableDocuments' => $notVerifiableDocuments,
                'hasPublicKey' => $publicKeyPem !== null,
                'warning' => null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return view('saft.upload', [
                'error' => $e->getMessage(),
            ]);
        } catch (\RuntimeException $e) {
            return view('saft.upload', [
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return view('saft.upload', [
                'error' => 'Ocorreu um erro inesperado ao processar o ficheiro: ' . $e->getMessage(),
            ]);
        }
    }
}
