<div id="app" class="table-responsive">
    <?php echo $this->Form->create('CursosInscripcion',array('type'=>'post','url'=>'confirmarAlumnos', 'novalidate' => true));?>
        <table class="table table-bordered table-hover table-striped table-condensed">
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="checkAll"/>
                </th>
                <th><?php echo $this->Paginator->sort('ciclo_id', 'Ciclo');?></th>
                <th><?php echo $this->Paginator->sort('centro_id', 'Centro');?></th>
                <th><?php echo $this->Paginator->sort('curso_id', 'Curso');?></th>
                <th><?php echo $this->Paginator->sort('turno', 'Turno');?></th>
                <th><?php echo $this->Paginator->sort('documento_nro', 'DNI');?></th>
                <th><?php echo $this->Paginator->sort('persona_id', 'Alumno');?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cursosInscripcions as $cursosInscripcion) : ?>
            <tr>
                <td>
                    <?php  if($cursosInscripcion['Inscripcion']['estado_inscripcion']!='CONFIRMADA') : ?>
                    <input type="checkbox" class="toggle_checkbox" name="id[]" value="<?php echo $cursosInscripcion['Inscripcion']['id']; ?>">
                    <?php  endif;  ?>
                </td>
                <td><?php echo $cursosInscripcion['Ciclo']['nombre']; ?> </td>
                <td><?php echo $cursosInscripcion['Centro']['nombre']; ?> </td>
                <td><?php echo $cursosInscripcion['Curso']['anio']." ".$cursosInscripcion['Curso']['division']; ?> </td>
                <td><?php echo $cursosInscripcion['Curso']['turno']; ?> </td>
                <td><?php echo $cursosInscripcion['Persona']['documento_nro']; ?> </td>
                <td><?php echo $cursosInscripcion['Persona']['nombres']." ".$cursosInscripcion['Persona']['apellidos']; ?> </td>
                <td>
                    <span class="link"><?php echo $this->Html->link('<i class="glyphicon glyphicon-eye-open"></i>', array('controller' => 'Inscripcions', 'action'=> 'view', $cursosInscripcion['Inscripcion']['id']), array('class' => 'btn btn-default', 'escape' => false)); ?></span>
                </td>
            </tr>
            <?php endforeach ?>
            <tr id="tdConfirmar" style="display:none;">
                <td colspan="6">
                    <div class="text-center">
                        <input type="submit" class="btn btn-primary" value="Promocionar a los seleccionados" />
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <?php echo $this->Form->end(); ?>
</div>


<script>
    $(function() {

        toggleConfirmButton();

        $("#checkAll").change(function () {
            var checkboxes = $(this).closest('form').find('.toggle_checkbox');
            var checked = $(this).prop("checked");
            checkboxes.prop('checked', checked);
            checkboxes.closest("tr").toggleClass("info", checked);

            toggleConfirmButton();
        });

        $('.toggle_checkbox').change(function() {
            $(this).closest("tr").toggleClass("info", this.checked);

            toggleConfirmButton();
        });
    });

    function toggleConfirmButton() {
        let seleccionados = $('.toggle_checkbox:checked').length;
        if(seleccionados>0) {
            $('#tdConfirmar').show();
        } else {
            $("#checkAll").prop('checked', false);
            $('#tdConfirmar').hide();
        }

    }
</script>