<?php
/**
 * Footer va Skriptlar
 */

declare(strict_types=1);
?>
    </main>

    <!-- Pastki Footer Qismi -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-slate-700">HRD.UZ Omborxona Davomat Tizimi</span>
                <span>&bull;</span>
                <span>PHP 8.3 + MySQL (100% Deterministik Kod)</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center text-emerald-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span> Tizim holati: Faol (100+ Ishchi)
                </span>
                <span>Versiya 2.4.0-Production</span>
            </div>
        </div>
    </footer>

    <!-- Toast Xabarnomalar Qutisi -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <script>
        // Global Toast xabar ko'rsatish funksiyasi
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-center p-4 rounded-xl shadow-lg border text-sm transition-all duration-300 transform translate-y-2 opacity-0 ${
                type === 'success' ? 'bg-white border-emerald-200 text-emerald-800' : 
                type === 'error' ? 'bg-white border-rose-200 text-rose-800' : 'bg-white border-blue-200 text-blue-800'
            }`;
            
            const icon = type === 'success' ? 'fa-check-circle text-emerald-500' : 
                         type === 'error' ? 'fa-triangle-exclamation text-rose-500' : 'fa-circle-info text-blue-500';

            toast.innerHTML = `
                <i class="fa-solid ${icon} text-lg mr-3"></i>
                <div class="font-medium mr-4">${message}</div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 text-xs">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    </script>
</body>
</html>
