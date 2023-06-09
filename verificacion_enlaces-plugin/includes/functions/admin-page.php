<div class="wrap">
    <h1>Enlaces Erróneos</h1>
    <?php
    // Obtener los enlaces erróneos desde la opción de WordPress
        $option_name = 'enlaces_erroneos_plugin_enlaces_erroneos';
        $enlaces_erroneos = get_option($option_name, array());
        //var_dump($enlaces_erroneos);// Imprimo los enlaces erróneos para depuración

    // Condicional si hay enlaces erróneos para mostrar
        if (!empty($enlaces_erroneos)) {
            ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>Estado</th>x
                        <th>Origen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
          // Iterar sobre cada enlace erróneo
                foreach ($enlaces_erroneos as $post_id => $enlaces) { 
                        //var_dump($enlaces); ?>
                        <tr>
                            <td colspan="3">
                                <strong><?php echo get_the_title($post_id); ?></strong>                                
                            </td>
                        </tr>
                        <?php // Mostrar los detalles de cada enlace erróneo, llamando el get_option 
                        foreach ($enlaces as $enlace) { ?>
                            <tr>
                                <td><?php echo $enlace['url']; ?></td>
                                <td><?php echo $enlace['estado']; ?></td>
                                <td><?php echo $enlace['origen']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
            <?php
        } else {
            //De lo contrario, todo esta bien
            echo '<p>No se encontraron enlaces erróneos.</p>';
        }
    ?>
</div>
