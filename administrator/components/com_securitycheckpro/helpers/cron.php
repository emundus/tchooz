<script type="text/javascript" language="javascript">
    // Añadimos la función Disable cuando se cargue la página para que deshabilite (o no) el desplegable del launching interval
    jQuery(document).ready(function() {        
        Disable();
    });
        
    function Disable() {
        //Obtenemos el índice de la periodicidad y los elementos de la opción launching interval
        var element = adminForm.elements["periodicity"].selectedIndex;
        var nodes = document.getElementById("launch_time").getElementsByTagName('*');
        
        // Si se seleccionan las horas, deshabilitamos los elementos del launching interval, puesto que no serán necesarios.
        if ( element<5 ) {
            $("#launch_time").hide();            
        } else {
            $("#launch_time").show();           
        }
        
    }
</script>
