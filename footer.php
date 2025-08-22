    </main>
    
    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Restaurante Pro - Sistema de gestión</p>
        </div>
    </footer>
    
    <script>
    // Funciones comunes de JavaScript
    function confirmarEliminacion(mensaje) {
        return confirm(mensaje || '¿Está seguro de que desea eliminar este elemento?');
    }
    
    function mostrarMensaje(tipo, mensaje) {
        const div = document.createElement('div');
        div.className = `alert-message ${tipo}`;
        div.textContent = mensaje;
        document.body.appendChild(div);
        
        setTimeout(() => {
            div.remove();
        }, 3000);
    }
    </script>
</body>
</html