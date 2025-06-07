                <!-- Footer -->
                <footer class="mt-5 pt-4 pb-2 text-muted text-center">
                    <div class="container">
                        <p class="mb-1">&copy; <?= date('Y') ?> Sumaq Agroexport - Sistema de Gestión de Exportaciones</p>
                        <small>Desarrollado con PHP y Bootstrap</small>
                    </div>
                </footer>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Activar DataTables en todas las tablas
        $(document).ready(function() {
            $('table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true
            });
            
            // Tema oscuro/claro
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const html = document.documentElement;
            
            // Verificar preferencia del usuario
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const savedTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
            
            // Aplicar tema guardado
            html.setAttribute('data-bs-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
            
            // Manejar clic en el botón de tema
            themeToggle.addEventListener('click', () => {
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-bs-theme', newTheme);
                themeIcon.className = newTheme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
                localStorage.setItem('theme', newTheme);
            });
        });
    </script>
</body>
</html>