@extends('layouts.app')

@section('title', 'Resultados — Verificador SAF-T PT')

@section('content')

{{-- Page Header --}}
<div class="mb-6 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Resultados da Verificação</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $header->companyName }} · NIF: {{ $header->taxRegistrationNumber }}</p>
    </div>
    <a href="{{ route('saft.upload') }}"
       class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:border-blue-400 hover:text-blue-700
              text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Nova verificação
    </a>
</div>

{{-- Warning --}}
@if (!empty($warning))
<div class="mb-6 bg-yellow-50 border border-yellow-300 rounded-lg p-4 flex gap-3">
    <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <p class="text-yellow-800 text-sm">{{ $warning }}</p>
</div>
@endif

{{-- Summary Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5 text-center shadow-sm">
        <div class="text-3xl font-bold text-gray-800">{{ $totalDocuments }}</div>
        <div class="text-sm text-gray-500 mt-1">Total Documentos</div>
    </div>
    <div class="bg-green-50 rounded-xl border border-green-200 p-5 text-center shadow-sm">
        <div class="text-3xl font-bold text-green-700">{{ $validDocuments }}</div>
        <div class="text-sm text-green-600 mt-1">✓ Válidos</div>
    </div>
    <div class="bg-red-50 rounded-xl border border-red-200 p-5 text-center shadow-sm">
        <div class="text-3xl font-bold text-red-700">{{ $invalidDocuments }}</div>
        <div class="text-sm text-red-600 mt-1">✗ Inválidos</div>
    </div>
    <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-5 text-center shadow-sm">
        <div class="text-3xl font-bold text-yellow-700">{{ $notVerifiableDocuments }}</div>
        <div class="text-sm text-yellow-600 mt-1">— Não verificáveis</div>
    </div>
</div>

{{-- Company Info --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6 shadow-sm">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Informação da Empresa</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
        <div>
            <span class="text-gray-500 block">Empresa</span>
            <span class="font-medium text-gray-900">{{ $header->companyName ?: '—' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">NIF</span>
            <span class="font-medium text-gray-900">{{ $header->taxRegistrationNumber ?: '—' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">ID Empresa</span>
            <span class="font-medium text-gray-900">{{ $header->companyId ?: '—' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Exercício Fiscal</span>
            <span class="font-medium text-gray-900">{{ $header->fiscalYear ?: '—' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Período</span>
            <span class="font-medium text-gray-900">{{ $header->startDate ?: '—' }} → {{ $header->endDate ?: '—' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Certificado SW</span>
            <span class="font-medium text-gray-900">{{ $header->softwareCertificateNumber ?: '—' }}</span>
        </div>
    </div>
</div>

{{-- Verification Mode Notice --}}
@if (!$hasPublicKey)
<div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex gap-3">
    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-blue-800 text-sm">
        <strong>Modo sem chave pública:</strong> Sem a chave pública RSA do software emissor, não é possível
        verificar criptograficamente as assinaturas. Apenas o 1º documento de cada série é marcado como
        "Não verificável". Os restantes mostram a string de assinatura reconstruída para consulta.
    </p>
</div>
@endif

{{-- Series Results --}}
@forelse ($seriesResults as $seriesResult)
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
    {{-- Series Header --}}
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-bold text-gray-900">Série: {{ $seriesResult->series }}</h2>
            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                {{ $seriesResult->totalDocuments }} documento(s)
            </span>
        </div>
        <div class="flex gap-2 text-xs font-medium">
            @if ($seriesResult->validDocuments > 0)
            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full">
                {{ $seriesResult->validDocuments }} válido(s)
            </span>
            @endif
            @if ($seriesResult->invalidDocuments > 0)
            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full">
                {{ $seriesResult->invalidDocuments }} inválido(s)
            </span>
            @endif
            @if ($seriesResult->notVerifiableDocuments > 0)
            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">
                {{ $seriesResult->notVerifiableDocuments }} não verificável(is)
            </span>
            @endif
        </div>
    </div>

    {{-- Documents Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-left">Documento</th>
                    <th class="px-4 py-3 text-left">Data</th>
                    <th class="px-4 py-3 text-left">Tipo</th>
                    <th class="px-4 py-3 text-right">Total Bruto</th>
                    <th class="px-4 py-3 text-left">Controlo Hash</th>
                    <th class="px-4 py-3 text-left">Detalhes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($seriesResult->results as $idx => $result)
                @php
                    $statusClass = match ($result->status->value) {
                        'valid' => 'bg-green-50',
                        'invalid' => 'bg-red-50',
                        default => 'bg-yellow-50',
                    };
                    $badgeClass = match ($result->status->value) {
                        'valid' => 'bg-green-100 text-green-800',
                        'invalid' => 'bg-red-100 text-red-800',
                        default => 'bg-yellow-100 text-yellow-800',
                    };
                    $badgeText = match ($result->status->value) {
                        'valid' => '✓ Válido',
                        'invalid' => '✗ Inválido',
                        default => '— Não verificável',
                    };
                    $detailsId = 'details-' . $seriesResult->series . '-' . $idx;
                @endphp
                <tr class="{{ $statusClass }}">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                            {{ $badgeText }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ $result->invoice->invoiceNo }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $result->invoice->invoiceDate }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $result->invoice->invoiceType }}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-800">{{ number_format((float)$result->invoice->grossTotal, 2, ',', '.') }} €</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">v{{ $result->invoice->hashControl }}</td>
                    <td class="px-4 py-3">
                        <button onclick="toggleDetails('{{ $detailsId }}')"
                                class="text-blue-600 hover:text-blue-800 text-xs font-medium underline">
                            Ver detalhes
                        </button>
                    </td>
                </tr>
                {{-- Expandable Details Row --}}
                <tr id="{{ $detailsId }}" class="hidden {{ $statusClass }}">
                    <td colspan="7" class="px-4 pb-4">
                        <div class="bg-white border border-gray-200 rounded-lg p-4 text-xs space-y-3">
                            {{-- Status Message --}}
                            <div>
                                <span class="font-semibold text-gray-600">Mensagem:</span>
                                <span class="text-gray-800 ml-1">{{ $result->message }}</span>
                            </div>

                            {{-- Signature String --}}
                            @if ($result->signatureString !== null)
                            <div>
                                <span class="font-semibold text-gray-600">String de assinatura reconstruída:</span>
                                <div class="mt-1 font-mono bg-gray-50 border border-gray-200 rounded p-2 break-all text-gray-700 leading-relaxed">
                                    {{ $result->signatureString }}
                                </div>
                                <p class="text-gray-400 mt-1">Formato: Data;DataHoraSistema;InvoiceNo;TotalBruto;HashAnterior</p>
                            </div>
                            @endif

                            {{-- Hash --}}
                            <div>
                                <span class="font-semibold text-gray-600">Hash (Base64):</span>
                                <div class="mt-1 font-mono bg-gray-50 border border-gray-200 rounded p-2 break-all text-gray-600 text-xs leading-relaxed">
                                    {{ $result->invoice->hash ?: '(vazio)' }}
                                </div>
                            </div>

                            {{-- Entry Date --}}
                            <div>
                                <span class="font-semibold text-gray-600">SystemEntryDate:</span>
                                <span class="font-mono text-gray-700 ml-1">{{ $result->invoice->systemEntryDate }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <p class="text-gray-500">Nenhum documento de faturação encontrado no ficheiro SAF-T.</p>
</div>
@endforelse

<script>
function toggleDetails(id) {
    const row = document.getElementById(id);
    row.classList.toggle('hidden');
}
</script>
@endsection
