<!DOCTYPE html>
<html lang="it" class="light md:h-screen">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MediClinic - Dashboard</title>

    <!-- Feather icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
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

        /* Helper per il tema scuro (se necessario) */
        .dark .dark\:invert {
            filter: invert(1);
        }

        /* Animazione per i puntini di caricamento */
        .loading-dot {
            animation: bounce 1.4s infinite both;
        }

        .loading-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loading-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes bounce {

            0%,
            80%,
            100% {
                transform: scale(0);
            }

            40% {
                transform: scale(1.0);
            }
        }

        /* Aggiungi stili per il font Inter */
        body {
            font-family: 'Inter', sans-serif;
        }

        #form-modifica-casistica::-webkit-scrollbar {
            display: none;
            /* Per Chrome, Safari e Opera */
        }

        #form-modifica-casistica {
            -ms-overflow-style: none;
            /* Per IE e Edge */
            scrollbar-width: none;
            /* Per Firefox */
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap');
    </style>
    <script src="/js/dark_theme.js"></script>
</head>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200 font-sans h-full">
    <header class="bg-white dark:bg-gray-800 shadow-md sticky top-0 z-30">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo e Navigazione -->
            <div class="flex items-center space-x-8">
                <!-- Logo -->
                <a href="/dashboard" class="flex items-center space-x-2">
                    <span class="p-2 bg-blue-600 rounded-lg">
                        <i data-feather="plus" class="w-4 h-4 font-bold text-white"></i>
                    </span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white">MediClinic</span>
                </a>
            </div>

            <!-- Controlli Utente -->
            <div class="flex items-center space-x-4">
                <!-- Pulsante Tema -->
                <button id="theme-toggle-btn"
                    class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 hover:cursor-pointer">
                    <i data-feather="moon" class="w-5 h-5 hidden dark:block"></i>
                    <i data-feather="sun" class="w-5 h-5 block dark:hidden"></i>
                </button>

                <!-- Menu Utente -->
                <div class="relative">
                    <button id="user-menu-button" class="flex items-center space-x-2">
                        <img src="https://placehold.co/40x40/E2E8F0/4A5568?text=DR" alt="Avatar utente"
                            class="w-8 h-8 rounded-full border-2 border-gray-300 dark:border-gray-600"
                            onerror="this.src='https://placehold.co/40x40/E2E8F0/4A5568?text=DR'">
                        <span
                            class="hidden sm:inline text-sm font-medium text-gray-700 dark:text-gray-200">{{ request()->user()->name }}</span>
                        <i data-feather="chevron-down" class="w-4 h-4 text-gray-500"></i>
                    </button>
                    <!-- Dropdown Menu -->
                    <div id="user-menu-dropdown"
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 border border-gray-200 dark:border-gray-700
                                hidden opacity-0 scale-95 transform transition-all duration-100 ease-out
                                origin-top-right z-40">
                        {{-- <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Profilo
                        </a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Storico diagnosi
                        </a> --}}
                        {{-- <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div> --}}
                        <a href="/auth/logout"
                            class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50">
                            Logout
                        </a>
                    </div>
                </div>
                <button
                    class="md:hidden p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <i data-feather="menu" class="w-5 h-5"></i>
                </button>
            </div>
        </nav>
    </header>


    <main class="container mx-auto md:px-4 py-6 h-full">
        <div class="flex flex-wrap sm:flex-nowrap justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Analisi Cliniche</h1>
            <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-end">

                <!-- Pulsante Nuova Casistica -->
                <button id="btn-nuova-casistica"
                    class="flex items-center bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 sm:px-4 rounded-md transition shadow-sm text-sm font-medium cursor-pointer">
                    <i data-feather="plus" class="w-4 h-4 mr-1 sm:mr-2"></i>
                    Nuova Casistica
                </button>

            </div>
        </div>

        <!-- Quick Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <form method="GET" action="/dashboard" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                {{-- Ciclo per filtri --}}
                @foreach (['reparto', 'medico', 'paziente'] as $filter)
                    <div>
                        <label for="filter-{{ $filter }}"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 select-none pointer-events-none">{{-- {{ Str::ucfirst($filter) }} --}}
                            {{ ucfirst($filter) }}</label>
                        <div class="relative">
                            <select id="filter-{{ $filter }}" name="{{ $filter }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white appearance-none py-2 pl-3 pr-8 outline-hidden">
                                <option value="">Seleziona {{ $filter }}</option>

                                @foreach ($data[$filter] as $item)
                                    <option {{ $item['id'] == request()->query($filter) ? 'selected' : '' }}
                                        value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                @endforeach
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-400">
                                <i data-feather="chevron-down" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div>
                    <label for="filter-stato"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 select-none pointer-events-none">{{-- {{ Str::ucfirst($filter) }} --}}
                        {{ ucfirst('status') }}</label>
                    <div class="relative">
                        <select id="filter-stato" name="stato"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white appearance-none py-2 pl-3 pr-8 outline-hidden">
                            <option value="">Seleziona status</option>

                            @foreach ($data['stato'] as $item)
                                <option {{ $item == request()->query('stato') ? 'selected' : '' }}
                                    value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-400">
                            <i data-feather="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="hover:cursor-pointer w-full py-2 px-4 bg-blue-600 text-white font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-150">
                        Applica Filtri
                    </button>
                </div>
            </form>
        </div>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <!-- Card 4 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 select-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aperti</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ count(
                                $data['all_medical_cases']->filter(function ($medical_case) {
                                    return $medical_case['status'] == 'Aperto';
                                }),
                            ) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-600 text-yellow-600 dark:text-yellow-300">
                        <i data-feather="user-plus" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
            <!-- Card 2 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 select-none">In Analisi</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ count(
                                $data['all_medical_cases']->filter(function ($medical_case) {
                                    return $medical_case['status'] == 'Analisi';
                                }),
                            ) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300">
                        <i data-feather="alert-circle" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 select-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">In Revisione</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ count(
                                $data['all_medical_cases']->filter(function ($medical_case) {
                                    return $medical_case['status'] == 'Revisione';
                                }),
                            ) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300">
                        <i data-feather="edit" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
            <!-- Card 3 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 select-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Chiusi</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ count(
                                $data['all_medical_cases']->filter(function ($medical_case) {
                                    return $medical_case['status'] == 'Chiuso';
                                }),
                            ) }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300">
                        <i data-feather="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>

            <!-- Card 1 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 select-none">Casistiche Totali
                        </p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">
                            {{ count($data['all_medical_cases']) }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">
                        <i data-feather="file-text" class="w-5 h-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cases Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="table-status">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  select-none tracking-wider">
                                ID Caso</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  select-none tracking-wider hidden lg:table-cell">
                                Data Registrazione</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  select-none tracking-wider">
                                Paziente</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  select-none tracking-wider hidden lg:table-cell">
                                Diagnosi Ingresso</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  select-none tracking-wider">
                                Stato</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase  select-none tracking-wider hidden lg:table-cell">
                                Medico</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase select-none  tracking-wider">
                                Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                        id="report-table-body">
                        @foreach ($data['medical_cases'] as $medical_case)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="/reports/{{ $medical_case['id'] }}"
                                        class="text-blue-600 dark:text-blue-400 hover:underline">#CASE-{{ $medical_case['id'] }}</a>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                    {{ $medical_case['hospitalization_date'] }}</td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ substr($medical_case['patient']['name'], 0, 40) }}...</td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                    {{ substr($medical_case['present_illness_history'], 0, 45) }}...</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php $status = $medical_case["status"]; @endphp
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if ($status == 'Chiuso') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @endif
                                        @if ($status == 'Analisi') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif
                                        @if ($status == 'Revisione') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @endif
                                        @if ($status == 'Aperto') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                        {{ $medical_case['status'] }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">
                                    Dr. {{ $medical_case['doctor']['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium select-none">
                                    <button
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-500 mr-3 cursor-pointer">
                                        <i data-medical-case-id="{{ $medical_case['id'] }}" data-feather="edit"
                                            class="w-4 h-4 editbtn"></i>
                                    </button>
                                    <button
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-500 cursor-pointer">
                                        <i data-medical-case-id="{{ $medical_case['id'] }}" data-feather="trash-2"
                                            class="w-4 h-4 trashbin"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Paginazione -->
            <div
                class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-600 sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div class="ml-auto">
                        <nav class="relative z-0 inline-flex rounded-md  -space-x-px" aria-label="Pagination">
                            {{-- {{ json_encode($data["medical_cases"]) }} --}}
                            @if ($data['medical_cases']['prev_page_url'])
                                <a href="{{ $data['medical_cases']['prev_page_url'] }}"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-600 dark:border-gray-600 dark:text-gray-300">
                                    <span class="sr-only">Previous</span>
                                    <i data-feather="chevron-left" class="h-4 w-4"></i>
                                </a>
                            @endif

                            {{ $data['medical_cases']->appends(request()->query())->links() }}
                        </nav>
                    </div>
                </div>
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
                            <input type="date" name="birthday" id="data_nascita"
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
                            <input type="datetime-local" name="hospitalization_date" id="data_registrazione"
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
                            <input type="date" name="birthday" id="data_nascita" disabled
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
                            <input type="datetime-local" name="hospitalization_date" id="data_registrazione"
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
                            <input type="datetime-local" name="discharge_date" id="data_dimissione"
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

    <!-- Chatbot Overlay -->
    <div class="fixed inset-0 z-50 invisible opacity-0 transition-opacity duration-300 ease-in-out"
        id="chatbot-overlay">
        <div class="absolute inset-0 bg-black opacity-50" id="chatbot-backdrop"></div>
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white dark:bg-gray-800 shadow-xl transform transition-transform duration-300 ease-in-out translate-x-full"
            id="chatbot-panel">
            <div class="h-full flex flex-col">
                <div class="flex items-center justify-between bg-blue-600 dark:bg-blue-800 text-white px-4 py-3">
                    <h3 class="font-medium text-lg">MediConsult AI</h3>
                    <button id="chatbot-close" class="text-white hover:text-blue-200">
                        <i data-feather="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Messaggi -->
                <div class="flex-1 p-4 overflow-y-auto space-y-4" id="chat-messages"></div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <div id="attachment-list" class="mb-3 hidden">
                        <div class="flex items-start justify-between mb-2">
                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                I file qui sotto verranno inviati con il messaggio.
                            </p>
                            <button id="clear-all"
                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
                                Svuota
                            </button>
                        </div>
                        <div id="attachment-chips" class="flex flex-wrap gap-2"></div>
                    </div>

                    <div id="drop-area"
                        class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-3 hidden">
                        <div class="text-center">
                            <i data-feather="upload" class="w-8 h-8 mx-auto text-gray-400"></i>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Trascina qui i file o clicca per
                                selezionare</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Supportiamo PDF, DOC, JPG, PNG</p>
                        </div>
                        <input type="file" id="fileElem" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </div>

                    <div class="flex">
                        <input type="text" id="chat-input" placeholder="Scrivi un messaggio..."
                            class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 outline-none focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white px-3 py-2">
                        <button id="send-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 rounded-r-md">
                            <i data-feather="send" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <div class="mt-2 flex justify-between items-center">
                        <button id="attach-btn"
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm flex items-center">
                            <i data-feather="paperclip" class="w-4 h-4 mr-1"></i> Allega file
                        </button>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Oppure trascina qui i file</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed bottom-6 right-6 z-40">
        <button id="chatbot-toggle"
            class="p-4 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition flex items-center justify-center">
            <i data-feather="message-square" class="w-6 h-6"></i>
        </button>
    </div>
    <script src="/js/chat.js"></script>
    <!-- <script>

        document.addEventListener("DOMContentLoaded", function() {

            async function sendChat(messages) {

                const response = await fetch('http://localhost:8085/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        messages: messages
                    })
                });

                
                if (!response.ok) throw new Error("Network response was not ok");
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                let isFirstChunk = true;
                let buffer = "";
                
                // Loop di lettura stream
                addAIResponse(null, 0); 
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    const chunkText = decoder.decode(value, { stream: true });
                    buffer += chunkText;
                    let lines = buffer.split('\n');
                    
                    // L'ultima linea potrebbe essere incompleta
                    buffer = lines.pop();

                    
                    for (const line of lines) {
                        if (line.trim() === "") continue;
                        try {
                            const jsonMessage = JSON.parse(line);
                            console.log(jsonMessage)
                            if (jsonMessage.content === "" || jsonMessage.type == "reasoning_xml") continue;
                            
                            // Gestione primo chunk (UI init)
                            if (isFirstChunk) {
                                isFirstChunk = false;
                                addAIResponse("", 0); 
                            }

                            if (jsonMessage.type === "content") {
                                appendToAIMessage(0, jsonMessage.content);
                            } else if (jsonMessage.type === "reasoning_xml") {
                                // appendToAIMessage(0, jsonMessage.content); 
                            }
                            
                        } catch (e) {
                            console.error("Errore nel parsing JSON della linea:", line, e);
                        }
                    }
                }

            }

            async function onSend() {
                if (isAwaitingResponse) return;

                const filesToSend = [...pendingFiles];

                if (text === '' && filesToSend.length === 0) return;

                isAwaitingResponse = true;
                setChatUIEnabled(false);

                // Reset UI input
                chatInput.value = '';
                pendingFiles = [];
                renderChips();

                // 1. Mostra messaggio utente
                if (filesToSend.length > 0) {
                    addUserFilesMessage(filesToSend, text);
                    filesToSend.forEach(file => {
                        var formData = new FormData();
                        formData.append('document', file);
                    })
                } else {
                    addUserTextMessage(text);
                }

                // // 2. Prepara il messaggio AI (Loading state)
                const msgId = `msg-${Date.now()}`;
                addAIResponse(null, msgId);
                console.log(filesToSend)

                try {
                } catch (error) {
                    console.error("Errore chat:", error);
                    addAIResponse("Spiacente, si è verificato un errore di connessione.", msgId);
                } finally {
                    isAwaitingResponse = false;
                    setChatUIEnabled(true);
                    chatInput.focus();
                }
            }

            // Funzione modificata per gestire creazione o reset messaggio
            function addAIResponse(text, msgId) {
                let msgElement = document.getElementById(msgId);
                let bubble;

                if (!msgElement) {
                    const aiDiv = document.createElement('div');
                    aiDiv.className = 'flex justify-start';
                    aiDiv.id = msgId;
                    bubble = document.createElement('div');
                    bubble.className = 'bg-gray-100 dark:bg-gray-700 rounded-lg p-3 max-w-xs';
                    aiDiv.appendChild(bubble);
                    chatMessages.appendChild(aiDiv);
                } else {
                    bubble = msgElement.querySelector('div');
                    bubble.innerHTML = '';
                }

                if (text === null) {
                    // Loading dots
                    bubble.innerHTML = `
                        <div class="flex space-x-1.5 p-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full loading-dot"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full loading-dot"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full loading-dot"></div>
                        </div>`;
                } else {
                    const p = document.createElement('p');
                    p.className = 'text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap';
                    p.textContent = text;
                    bubble.appendChild(p);
                }
                scrollBottom();
            }
            
             function addUserTextMessage(text) {
                const userDiv = document.createElement('div');
                userDiv.className = 'flex justify-end';
                const bubble = document.createElement('div');
                bubble.className = 'bg-blue-600 text-white rounded-lg p-3 max-w-xs';
                const p = document.createElement('p');
                p.className = 'text-sm whitespace-pre-wrap';
                p.textContent = text;
                bubble.appendChild(p);
                userDiv.appendChild(bubble);
                chatMessages.appendChild(userDiv);
                scrollBottom();
            }

            function addUserFilesMessage(files, text) {
                const wrap = document.createElement('div');
                wrap.className = 'flex justify-end';
                const card = document.createElement('div');
                card.className = 'bg-blue-100 dark:bg-blue-900 rounded-lg p-3 max-w-xs';

                files.forEach(file => {
                    const row = document.createElement('div');
                    row.className = 'flex items-center';
                    if (card.childElementCount > 0) row.classList.add('mt-2');

                    const icon = document.createElement('i');
                    icon.setAttribute('data-feather', getFileIcon(file.type));
                    icon.className = 'w-4 h-4 mr-2 text-blue-600 dark:text-blue-300 flex-shrink-0';

                    const name = document.createElement('span');
                    name.className = 'text-sm text-blue-800 dark:text-blue-200 truncate';
                    name.textContent = `${file.name} (${humanFileSize(file.size)})`;

                    row.append(icon, name);
                    card.appendChild(row);
                });

                if (text) {
                    const hr = document.createElement('div');
                    hr.className = 'my-2 h-px bg-blue-200/60 dark:bg-blue-800/60';
                    card.appendChild(hr);
                    const noteEl = document.createElement('p');
                    noteEl.className = 'text-sm text-blue-900 dark:text-blue-100 whitespace-pre-wrap';
                    noteEl.textContent = text;
                    card.appendChild(noteEl);
                }

                wrap.appendChild(card);
                chatMessages.appendChild(wrap);
                scrollBottom();
                feather.replace();
            }

            function scrollBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function getFileIcon(fileType) {
                if (!fileType) return 'file';
                if (fileType.includes('pdf')) return 'file-text';
                if (fileType.includes('image')) return 'image';
                if (fileType.includes('word') || fileType.includes('document')) return 'file-text';
                return 'file';
            }

            function humanFileSize(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024,
                    sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }

            function preventAllWhenChatOpen(e) {
                if (!chatbotOverlay.classList.contains('invisible')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
            window.addEventListener('dragover', preventAllWhenChatOpen, false);
            window.addEventListener('drop', preventAllWhenChatOpen, false);

            function eventHasFiles(e) {
                const types = e?.dataTransfer?.types;
                if (!types) return false;
                try {
                    return Array.from(types).includes('Files');
                } catch {
                    return types.contains && types.contains('Files');
                }
            }

            function showDropArea() {
                if (!isAwaitingResponse) dropArea.classList.remove('hidden');
            }

            function hideDropArea() {
                dropArea.classList.add('hidden');
                dragCounter = 0;
            }

            chatbotOverlay.addEventListener('dragenter', (e) => {
                if (!eventHasFiles(e)) return;
                dragCounter++;
                showDropArea();
            }, false);

            chatbotOverlay.addEventListener('dragover', (e) => {
                if (!eventHasFiles(e)) return;
                e.preventDefault();
                e.stopPropagation();
            }, false);

            chatbotOverlay.addEventListener('dragleave', (e) => {
                if (!eventHasFiles(e)) return;
                dragCounter = Math.max(0, dragCounter - 1);
                if (dragCounter === 0) hideDropArea();
            }, false);

            chatbotOverlay.addEventListener('drop', (e) => {
                if (!eventHasFiles(e) || isAwaitingResponse) return;
                e.preventDefault();
                e.stopPropagation();
                hideDropArea();
                handleFiles({
                    target: {
                        files: e.dataTransfer.files
                    }
                });
            }, false);

            ['dragenter', 'dragover'].forEach(ev => {
                dropArea.addEventListener(ev, () => {
                    dropArea.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
                }, false);
            });
            ['dragleave', 'drop'].forEach(ev => {
                dropArea.addEventListener(ev, () => {
                    dropArea.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
                }, false);
            });

            if (fileElem) {
                fileElem.addEventListener('change', handleFiles, false);
            }

            attachBtn.addEventListener('click', () => {
                if (!isAwaitingResponse) {
                    dropArea.classList.toggle('hidden');
                }
            });

            clearAllBtn.addEventListener('click', () => {
                pendingFiles = [];
                renderChips();
            });

            function handleFiles(e) {
                const files = e.target.files;
                if (!files || !files.length) return;
                for (const f of files) {
                    pendingFiles.push(f);
                }
                renderChips();
                hideDropArea();
            }

            function renderChips() {
                chipsWrap.innerHTML = '';
                if (pendingFiles.length === 0) {
                    attachmentList.classList.add('hidden');
                    return;
                }
                attachmentList.classList.remove('hidden');

                pendingFiles.forEach((file, idx) => {
                    const chip = document.createElement('div');
                    chip.className =
                        'inline-flex items-center gap-2 px-2 py-1 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 shadow-sm';
                    const icon = document.createElement('i');
                    icon.setAttribute('data-feather', getFileIcon(file.type));
                    icon.className = 'w-3.5 h-3.5 text-blue-600 dark:text-blue-300';
                    const name = document.createElement('span');
                    name.className = 'text-xs text-gray-800 dark:text-gray-200 max-w-[9rem] truncate';
                    name.textContent = file.name;
                    const size = document.createElement('span');
                    size.className = 'text-[10px] text-gray-500 dark:text-gray-400';
                    size.textContent = humanFileSize(file.size);
                    const remove = document.createElement('button');
                    remove.className = 'p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700';
                    remove.innerHTML = '<i data-feather="x" class="w-3 h-3 text-gray-500"></i>';
                    remove.addEventListener('click', () => {
                        pendingFiles.splice(idx, 1);
                        renderChips();
                    });
                    chip.append(icon, name, size, remove);
                    chipsWrap.appendChild(chip);
                });
                feather.replace();
            }
        });
    </script> -->

    <script type="module" src="/js/medical_cases_ops.js"></script>
</body>

</html>
