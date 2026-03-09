@extends('layouts.app')

@section('title', 'Verificador SAF-T PT — Upload')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Page Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Verificador de Assinaturas SAF-T PT
        </h1>
        <p class="text-gray-600">
            Verifique a integridade das assinaturas digitais (Hash) dos documentos de faturação
            contidos em ficheiros SAF-T PT, de acordo com a Portaria 302/2016.
        </p>
    </div>

    {{-- Error Alert --}}
    @if (!empty($error))
    <div class="mb-6 bg-red-50 border border-red-300 rounded-lg p-4 flex gap-3">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <h3 class="font-semibold text-red-800">Erro ao processar ficheiro</h3>
            <p class="text-red-700 text-sm mt-1">{{ $error }}</p>
        </div>
    </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
    <div class="mb-6 bg-red-50 border border-red-300 rounded-lg p-4">
        <h3 class="font-semibold text-red-800 mb-2">Erros de validação:</h3>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Upload Form --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <form action="{{ route('saft.verify') }}" method="POST" enctype="multipart/form-data" id="upload-form">
            @csrf

            {{-- SAF-T File Upload --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Ficheiro SAF-T PT
                    <span class="text-red-500 ml-1">*</span>
                </label>
                <div id="drop-zone"
                     class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer
                            hover:border-blue-400 hover:bg-blue-50 transition-colors duration-200"
                     onclick="document.getElementById('saft_file').click()">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-gray-600 mb-1">
                        <span class="font-semibold text-blue-600">Clique para selecionar</span>
                        ou arraste o ficheiro aqui
                    </p>
                    <p class="text-sm text-gray-400">Ficheiro XML SAF-T PT (máx. {{ config('saft.max_file_size_mb', 100) }}MB)</p>
                    <p id="selected-file" class="mt-3 text-sm font-medium text-blue-700 hidden"></p>
                </div>
                <input type="file" id="saft_file" name="saft_file" accept=".xml"
                       class="hidden" onchange="handleFileSelect(this, 'selected-file', 'drop-zone')">
            </div>

            {{-- Public Key Upload (Optional) --}}
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Chave Pública RSA
                    <span class="text-gray-400 font-normal ml-1">(opcional)</span>
                </label>
                <p class="text-xs text-gray-500 mb-2">
                    Se fornecer a chave pública RSA do software emissor (formato PEM), será possível
                    verificar criptograficamente as assinaturas RSA. Sem a chave, apenas a integridade
                    da cadeia de hashes é verificada.
                </p>
                <div id="key-drop-zone"
                     class="border-2 border-dashed border-gray-200 rounded-lg p-5 text-center cursor-pointer
                            hover:border-blue-300 hover:bg-blue-50 transition-colors duration-200"
                     onclick="document.getElementById('public_key').click()">
                    <p class="text-sm text-gray-500">
                        <span class="font-medium text-blue-500">Selecionar chave pública</span>
                        (.pem ou .txt)
                    </p>
                    <p id="selected-key" class="mt-1 text-sm font-medium text-blue-700 hidden"></p>
                </div>
                <input type="file" id="public_key" name="public_key" accept=".pem,.txt"
                       class="hidden" onchange="handleFileSelect(this, 'selected-key', 'key-drop-zone')">
            </div>

            {{-- Submit Button --}}
            <button type="submit" id="submit-btn"
                    class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 px-6 rounded-lg
                           transition-colors duration-200 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verificar Assinaturas
            </button>
        </form>
    </div>

    {{-- Info Box --}}
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-5">
        <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Como funciona a verificação?
        </h3>
        <ul class="text-sm text-blue-800 space-y-1.5 list-disc list-inside">
            <li>Os documentos são agrupados por <strong>série documental</strong> e ordenados sequencialmente.</li>
            <li>O <strong>1º documento</strong> de cada série é marcado como "Não verificável" — o hash anterior era vazio na criação.</li>
            <li>Para os restantes, reconstrói-se a string de assinatura:
                <code class="bg-blue-100 px-1 rounded text-xs">Data;DataHora;InvoiceNo;Total;HashAnterior</code>
            </li>
            <li>Se a chave pública for fornecida, verifica-se a assinatura RSA (SHA-256 e SHA-1).</li>
        </ul>
    </div>
</div>

<script>
function handleFileSelect(input, labelId, zoneId) {
    const label = document.getElementById(labelId);
    const zone = document.getElementById(zoneId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const size = (file.size / 1024 / 1024).toFixed(2);
        label.textContent = `✓ ${file.name} (${size} MB)`;
        label.classList.remove('hidden');
        zone.classList.add('border-blue-400', 'bg-blue-50');
        zone.classList.remove('border-gray-300', 'border-gray-200');
    }
}

// Drag and drop support
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('saft_file');

['dragenter', 'dragover'].forEach(event => {
    dropZone.addEventListener(event, (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    });
});

['dragleave', 'drop'].forEach(event => {
    dropZone.addEventListener(event, (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(fileInput, 'selected-file', 'drop-zone');
    }
});

// Show loading state on submit
document.getElementById('upload-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = `<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg> A processar...`;
});
</script>
@endsection
