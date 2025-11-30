<!DOCTYPE html>
<html lang="it" class="light md:h-screen">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MediClinic - Dashboard AI</title>

    <!-- Feather icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    
    <!-- SweetAlert2 per azioni AI -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        /* Nasconde l'input file di default */
        #fileElem {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        .dark .dark\:invert { filter: invert(1); }

        /* Animazione caricamento */
        .loading-dot { animation: bounce 1.4s infinite both; }
        .loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .loading-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        body { font-family: 'Inter', sans-serif; }

        /* Scrollbar personalizzata per la chat e form */
        #chat-messages::-webkit-scrollbar,
        #form-modifica-casistica::-webkit-scrollbar {
            width: 6px;
        }
        #chat-messages::-webkit-scrollbar-track { background: transparent; }
        #chat-messages::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        #chat-messages > div > div {
            overflow-wrap: anywhere;
            word-break: break-word; 
            min-width: 0;           
        }
        .dark #chat-messages::-webkit-scrollbar-thumb { background-color: #475569; }

        /* Stile specifico per il processo di Thinking dell'AI */
        .ai-thinking-box {
            background-color: #f3f4f6;
            border-left: 3px solid #8b5cf6; /* Viola */
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #4b5563;
        }
        .dark .ai-thinking-box {
            background-color: #1f2937;
            border-left-color: #a78bfa;
            color: #d1d5db;
        }
        .ai-thinking-summary {
            cursor: pointer;
            font-weight: 600;
            color: #7c3aed; /* Viola scuro */
            display: flex;
            align-items: center;
            gap: 5px;
            user-select: none;
        }
        .dark .ai-thinking-summary { color: #c4b5fd; }
        
        /* Markdown styles in chat */
        .chat-markdown p { margin-bottom: 0.5rem; }
        .chat-markdown p:last-child { margin-bottom: 0; }
        .chat-markdown ul { list-style-type: disc; margin-left: 1.5rem; }
        .chat-markdown strong { font-weight: 700; }

        /* Stile compatto per il contenuto del pensiero */
        .ai-thinking-content {
            font-size: 0.8rem;      /* Testo leggermente più piccolo */
            line-height: 1.4;       /* Interlinea più stretta */
            color: #4b5563;
            /* Rimuoviamo white-space pre-wrap qui per lasciare gestire il layout all'HTML di marked */
            white-space: normal !important;
            overflow-wrap: anywhere; 
        }

        /* Targettiamo TUTTI i possibili tag generati da marked all'interno del box */
        .ai-thinking-content p, 
        .ai-thinking-content ul, 
        .ai-thinking-content ol, 
        .ai-thinking-content blockquote, 
        .ai-thinking-content pre {
            margin-bottom: 0.4em !important; /* Spazio minimo tra blocchi */
            margin-top: 0 !important;
        }

        /* Rimuove il margine dall'ultimo elemento per non avere spazio vuoto in fondo */
        .ai-thinking-content *:last-child {
            margin-bottom: 0 !important;
        }

        /* Gestione Liste: riduce il padding sinistro eccessivo */
        .ai-thinking-content ul, 
        .ai-thinking-content ol {
            padding-left: 1.2em; 
            list-style-position: outside;
        }

        /* Stile per i punti elenco specifici dentro il thinking */
        .ai-thinking-content li {
            margin-bottom: 0.1em;
        }

        /* Dark mode fix */
        .dark .ai-thinking-content {
            color: #d1d5db;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap');
    </style>
    <script src="/js/dark_theme.js"></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200 font-sans h-full overflow-hidden flex flex-col">
    
    <!-- HEADER -->
    <header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-30 shrink-0">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-8">
                <a href="/dashboard" class="flex items-center space-x-2">
                    <span class="p-2 bg-blue-600 rounded-lg">
                        <i data-feather="plus" class="w-4 h-4 font-bold text-white"></i>
                    </span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white">MediClinic AI</span>
                </a>
            </div>

            <div class="flex items-center space-x-4">
                <button id="theme-toggle-btn" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 hover:cursor-pointer">
                    <i data-feather="moon" class="w-5 h-5 hidden dark:block"></i>
                    <i data-feather="sun" class="w-5 h-5 block dark:hidden"></i>
                </button>

                <div class="relative group">
                    <button id="user-menu-button" class="flex items-center space-x-2">
                        <img src="https://placehold.co/40x40/E2E8F0/4A5568?text=DR" alt="Avatar" class="w-8 h-8 rounded-full border-2 border-gray-300 dark:border-gray-600">
                        <span class="hidden sm:inline text-sm font-medium text-gray-700 dark:text-gray-200">{{ request()->user()->name }}</span>
                        <i data-feather="chevron-down" class="w-4 h-4 text-gray-500"></i>
                    </button>
                    <!-- Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 border border-gray-200 dark:border-gray-700 hidden group-hover:block z-40">
                        <a href="/auth/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/50">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- MAIN CONTENT -->
    <main class="container mx-auto md:px-4 py-6 flex-1 overflow-y-auto">
        <div class="flex flex-wrap sm:flex-nowrap justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Analisi Cliniche</h1>
            <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-end">
                <button id="btn-nuova-casistica" class="flex items-center bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 sm:px-4 rounded-md transition shadow-sm text-sm font-medium cursor-pointer">
                    <i data-feather="plus" class="w-4 h-4 mr-1 sm:mr-2"></i> Nuova Casistica
                </button>
            </div>
        </div>

        <!-- Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            @php
                $stats = [
                    ['label' => 'Totali', 'color' => 'blue', 'icon' => 'file-text', 'count' => count($data['all_medical_cases'])],
                    ['label' => 'Aperti', 'color' => 'yellow', 'icon' => 'user-plus', 'count' => $data['all_medical_cases']->where('status', 'Aperto')->count()],
                    ['label' => 'In Analisi', 'color' => 'orange', 'icon' => 'alert-circle', 'count' => $data['all_medical_cases']->where('status', 'Analisi')->count()],
                    ['label' => 'Chiusi', 'color' => 'green', 'icon' => 'check-circle', 'count' => $data['all_medical_cases']->where('status', 'Chiuso')->count()],
                ];
            @endphp

            @foreach($stats as $stat)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 select-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $stat['count'] }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-300">
                        <i data-feather="{{ $stat['icon'] }}" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="table-status">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Caso</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Paziente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stato</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($data['medical_cases'] as $medical_case)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                                    <a href="/reports/{{ $medical_case['id'] }}">#CASE-{{ $medical_case['id'] }}</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $medical_case['patient']['name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $medical_case['status'] == 'Chiuso' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $medical_case['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-blue-600 hover:text-blue-900 mr-3 editbtn" data-medical-case-id="{{ $medical_case['id'] }}">
                                        <i data-feather="edit" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-600">
                {{ $data['medical_cases']->links() }}
            </div>
        </div>
    </main>


    <div id="modal-nuova-casistica"
        class="fixed inset-0 z-50 bg-gray-900 flex items-center justify-center p-4 invisible opacity-0 transition-opacity duration-300 ease-in-out">

        <div id="modal-panel"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl overflow-hidden transform transition-all scale-95">

            <!-- Modal Header -->
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Inserisci Nuova Casistica</h3>
                <button id="modal-close-btn" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i data-feather="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body (Form con icone) -->
            <form id="form-nuova-casistica" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">
                        <label for="paziente"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Paziente</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="user" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" name="name" id="paziente" placeholder="Name"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>
                    <div>
                        <label for="data_nascita"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Nascita</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" name="birthday" id="data_nascita" max="{{explode(' ', now())[0]}}"
                                class="py-2 block outline-none pr-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Luogo
                            di nascita</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="map" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" name="city" id="city" placeholder="Luogo di nascita"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>

                    <!-- Stato (con icona e chevron) -->
                    <div class="md:col-span-2">
                        <label for="stato"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stato</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="activity" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <select id="stato" name="stato"
                                class="py-2 block outline-none block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10 pr-10 appearance-none">
                                <option>Aperto</option>
                                <option>Analisi</option>
                                <option>Revisione</option>
                                <option>Chiuso</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                <i data-feather="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Data Registrazione (con icona) -->
                    <div class="md:col-span-2">
                        <label for="data_registrazione"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data
                            Registrazione</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" name="hospitalization_date" id="data_registrazione" max="{{explode(' ', now())[0]}}"
                                class="py-2 block outline-none pr-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>


                    <!-- Diagnosi Ingresso (con icona) -->
                    <div>
                        <label for="diagnosi_passata"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anamnesi patologica
                            remota</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="diagnosi_passata" name="past_illness_history" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere la diagnosi iniziale..."></textarea>
                        </div>
                    </div>

                    <div>
                        <label for="diagnosi_attuale"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anamnesi patologica
                            prossima</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="diagnosi_attuale" name="present_illness_history" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere la diagnosi attuale..."></textarea>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="clinical_evolution"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Decorso clinico</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="clinical_evolution" name="clinical_evolution" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere le eventuali evoluzioni cliniche..."></textarea>
                        </div>
                    </div>


                    <div class="md:col-span-2">
                        <label for="data_dimissione"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Dimissione</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" name="discharge_date" id="data_dimissione" max="{{explode(' ', now())[0]}}"
                                class="py-2 block outline-none pr-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>


                    <div id="dimission_text_wrapper" class="md:col-span-2 opacity-50 pointer-events-none select-none">
                        <label for="dimission_text"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Indicazioni alla
                            dimissione e terapia domiciliare</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="dimission_text" name="discharge_description" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere le eventuali evoluzioni cliniche..."></textarea>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Modal Footer (con animazioni hover) -->
            <div
                class="flex justify-end items-center p-4 bg-gray-50 dark:bg-gray-700 border-t dark:border-gray-600 space-x-3">
                <button id="modal-cancel-btn"
                    class="cursor-pointer bg-white dark:bg-gray-600 hover:bg-gray-100 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 py-2 px-4 rounded-md transition-all duration-150 ease-in-out border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium transform hover:scale-[1.03] hover:shadow-md">
                    Annulla
                </button>
                <button type="submit" form="form-nuova-casistica"
                    class="hover:cursor-pointer bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-all duration-150 ease-in-out shadow-sm text-sm font-medium transform hover:scale-[1.03] hover:shadow-md">
                    Salva Casistica
                </button>
            </div>
        </div>
    </div>

    <div id="modal-modifica-casistica"
        class="fixed inset-0 z-50 bg-gray-900  flex items-center justify-center p-4 invisible opacity-0 transition-opacity duration-300 ease-in-out">

        <div id="modal-panel-modifica"
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl overflow-hidden transform transition-all scale-95 duration-300 ease-in-out">

            <!-- Modal Header -->
            <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Modifica Casistica</h3>
                <!-- Pulsante 'X' per chiudere -->
                <button id="modal-modifica-close-btn"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i data-feather="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Body (Form con icone) -->
            <form id="form-modifica-casistica" class="p-6 overflow-y-auto" style="max-height: 80vh;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">
                        <label for="paziente"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Paziente</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="user" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" name="name" disabled id="paziente" placeholder="Nome Cognome"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>
                    <div>
                        <label for="data_nascita"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Nascita</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" name="birthday" id="data_nascita" disabled max="{{explode(' ', now())[0]}}"
                                class="py-2 block outline-none pr-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>

                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            disabled>Luogo di nascita</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="map" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="text" name="city" id="city" disabled
                                placeholder="Luogo di nascita"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>

                    <!-- Stato (con icona e chevron) -->
                    <div class="md:col-span-2">
                        <label for="stato"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stato</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="activity" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <select id="stato" name="stato"
                                class="py-2 block outline-none block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10 pr-10 appearance-none">
                                <option>Aperto</option>
                                <option>Analisi</option>
                                <option>Revisione</option>
                                <option>Chiuso</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                <i data-feather="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Data Registrazione (con icona) -->
                    <div class="md:col-span-2">
                        <label for="data_registrazione"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data
                            Registrazione</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" name="hospitalization_date" id="data_registrazione"  max="{{explode(' ', now())[0]}}"
                                class="py-2 block outline-none pr-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>


                    <!-- Diagnosi Ingresso (con icona) -->
                    <div>
                        <label for="diagnosi_passata"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anamnesi patologica
                            remota</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="diagnosi_passata" name="past_illness_history" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere la diagnosi iniziale..."></textarea>
                        </div>
                    </div>

                    <div>
                        <label for="diagnosi_attuale"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anamnesi patologica
                            prossima</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="diagnosi_attuale" name="present_illness_history" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere la diagnosi attuale..."></textarea>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="clinical_evolution"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Decorso clinico</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="clinical_evolution" name="clinical_evolution" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere le eventuali evoluzioni cliniche..."></textarea>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="data_dimissione"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Dimissione</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i data-feather="calendar" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <input type="date" name="discharge_date" id="data_dimissione" max="{{explode(' ', now())[0]}}"
                                class="py-2 block outline-none pr-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10">
                        </div>
                    </div>


                    <div id="dimission_text_wrapper" class="md:col-span-2 opacity-50 pointer-events-none select-none">
                        <label for="dimission_text"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Indicazioni alla
                            dimissione e terapia domiciliare</label>
                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute top-3 left-0 flex items-center pl-3">
                                <i data-feather="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <textarea id="dimission_text" name="discharge_description" rows="3"
                                class="py-2 block outline-none w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pl-10"
                                placeholder="Descrivere le eventuali evoluzioni cliniche..."></textarea>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Modal Footer (con animazioni hover) -->
            <div
                class="flex justify-end items-center p-4 bg-gray-50 dark:bg-gray-700 border-t dark:border-gray-600 space-x-3">
                <!-- Pulsante 'Annulla' per chiudere -->
                <button id="modal-modifica-cancel-btn"
                    class="cursor-pointer bg-white dark:bg-gray-600 hover:bg-gray-100 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 py-2 px-4 rounded-md transition-all duration-150 ease-in-out border border-gray-300 dark:border-gray-500 shadow-sm text-sm font-medium transform hover:scale-[1.03] hover:shadow-md">
                    Annulla
                </button>
                <button type="submit" form="form-modifica-casistica"
                    class="hover:cursor-pointer bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-all duration-150 ease-in-out shadow-sm text-sm font-medium transform hover:scale-[1.03] hover:shadow-md">
                    Salva Modifiche
                </button>
            </div>
        </div>
    </div>

    <!-- CHATBOT OVERLAY -->
    <div class="fixed inset-0 z-50 invisible opacity-0 transition-opacity duration-300 ease-in-out" id="chatbot-overlay">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="chatbot-backdrop"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white dark:bg-gray-800 shadow-2xl transform transition-transform duration-300 ease-in-out translate-x-full flex flex-col" id="chatbot-panel">
            
            <!-- Chat Header -->
            <div class="flex items-center justify-between bg-blue-600 dark:bg-blue-800 text-white px-4 py-3 shadow-md shrink-0">
                <div class="flex items-center gap-2">
                    <div class="bg-white/20 p-1.5 rounded-lg">
                        <i data-feather="cpu" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base leading-tight">MediConsult AI</h3>
                        <p class="text-xs text-blue-100 opacity-90">Powered by Reasoning Models</p>
                    </div>
                </div>
                <button id="chatbot-close" class="text-white hover:bg-white/10 p-1 rounded transition">
                    <i data-feather="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Chat Messages Area -->
            <div class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50 dark:bg-gray-900" id="chat-messages">
                <!-- Welcome Message -->
                <div class="flex justify-start">
                    <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3 max-w-[85%] shadow-sm">
                        <p class="text-sm text-gray-800 dark:text-gray-200">
                            Ciao Dr. {{ auth()->user()->name }}! 👋 <br>
                            Posso analizzare referti, suggerire diagnosi o eseguire azioni sulla dashboard. Carica un documento o chiedimi qualcosa.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Chat Input Area -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
                
                <!-- File Preview Chips -->
                <div id="attachment-list" class="mb-3 hidden">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Allegati pronti all'invio:</span>
                        <button id="clear-all" class="text-xs text-red-500 hover:underline cursor-pointer">Rimuovi tutti</button>
                    </div>
                    <div id="attachment-chips" class="flex flex-wrap gap-2 max-h-24 overflow-y-auto custom-scrollbar"></div>
                </div>

                <!-- Drag & Drop Overlay Zone -->
                <div id="drop-area" class="hidden absolute inset-4 bg-blue-50/90 dark:bg-blue-900/90 border-2 border-dashed border-blue-500 rounded-lg z-10 flex flex-col items-center justify-center text-center backdrop-blur-sm">
                    <i data-feather="upload-cloud" class="w-10 h-10 text-blue-600 dark:text-blue-300 mb-2"></i>
                    <p class="font-medium text-blue-800 dark:text-blue-200">Rilascia i file qui</p>
                </div>

                <!-- Input Field -->
                <div class="relative">
                    <div class="flex items-end gap-2 bg-gray-100 dark:bg-gray-700 rounded-xl p-2 border border-transparent focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-all">
                        <button id="attach-btn" class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300 transition-colors" title="Allega file">
                            <i data-feather="paperclip" class="w-5 h-5"></i>
                        </button>
                        
                        <textarea id="chat-input" rows="1" placeholder="Scrivi un messaggio..." 
                            class="w-full bg-transparent border-none focus:ring-0 p-2 text-gray-800 dark:text-white resize-none max-h-32 text-sm leading-relaxed"
                            style="min-height: 40px;"></textarea>
                        
                        <button id="send-btn" class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm transition-transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-feather="send" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <input type="file" id="fileElem" multiple accept=".pdf,.jpg,.png,.txt,.doc,.docx" class="hidden">
                </div>
                <p class="text-[10px] text-center text-gray-400 mt-2">AI can make mistakes. Verify important info.</p>
            </div>
        </div>
    </div>

    <!-- Toggle Button -->
    <div class="fixed bottom-6 right-6 z-40">
        <button id="chatbot-toggle" class="p-4 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-transform hover:scale-105 flex items-center justify-center group">
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full hidden" id="chat-badge">1</span>
            <i data-feather="message-square" class="w-6 h-6 group-hover:hidden"></i>
            <i data-feather="maximize-2" class="w-6 h-6 hidden group-hover:block"></i>
        </button>
    </div>

    <!-- Logic Script -->
    <script src="/js/chat.js"></script>
    <script type="module" src="/js/medical_cases_ops.js"></script>
    
    <script>
        // Init icons
        feather.replace();
        
        // Modal logic (Basic toggle for demo)
        const modal = document.getElementById('modal-nuova-casistica');
        const btnNew = document.getElementById('btn-nuova-casistica');
        const btnClose = document.getElementById('modal-close-btn');
        const btnCancel = document.getElementById('modal-cancel-btn');

        function toggleModal(show) {
            if(show) {
                modal.classList.remove('invisible', 'opacity-0');
            } else {
                modal.classList.add('invisible', 'opacity-0');
            }
        }

        btnNew?.addEventListener('click', () => toggleModal(true));
        btnClose?.addEventListener('click', () => toggleModal(false));
        btnCancel?.addEventListener('click', () => toggleModal(false));
    </script>
</body>
</html>