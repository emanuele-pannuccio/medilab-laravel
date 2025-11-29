if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia(
        '(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark')
} else {
    document.documentElement.classList.remove('dark')
}

// Funzione globale per il toggle
function toggleTheme() {
    if (localStorage.theme === 'dark') {
        localStorage.theme = 'light';
        document.documentElement.classList.remove('dark');
    } else {
        localStorage.theme = 'dark';
        document.documentElement.classList.add('dark');
    }
    // Aggiorna icone dopo il cambio tema se necessario
    feather.replace();
}

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});