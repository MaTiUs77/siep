<?php
App::uses('AppController', 'Controller');

class CursosInscripcionsController extends AppController {

	var $name = 'CursosInscripcions';
   	public $paginate = array('CursosInscripcion' => array('limit' => 2, 'order' => 'CursosInscripcion.curso_id ASC'));

    public function beforeFilter() {
        parent::beforeFilter();
        /* ACCESOS SEGÚN ROLES DE USUARIOS (INICIO).
        *Si el usuario tiene un rol de superadmin le damos acceso a todo. Si no es así (se trata de un usuario "admin o usuario") tendrá acceso sólo a las acciones que les correspondan.
        */
        if($this->Auth->user('role') === 'superadmin') {
	        $this->Auth->allow();
	    } elseif (($this->Auth->user('role') === 'admin') || ($this->Auth->user('role') === 'usuario')) { 
	        $this->Auth->allow('index');
	    }
	    /* FIN */ 
    } 

/**
 * index method
 *
 * @return void
 */
	public function index()
	{
		// Hachazo nivel Ninja Warrior!

		// Datos del usuario
		$userCentroId = $this->getUserCentroId();
		$userRole = $this->Auth->user('role');
		$cicloIdActual = $this->getActualCicloId();

		// Modelos a utilizar
		$this->loadModel('Centro');
		$this->loadModel('Curso');
		$this->loadModel('Ciclo');

		// Abria que ver como cake gestiona estos joins de manera nativa en el ORM
		$this->paginate['CursosInscripcion'] = array(
			'fields' => array(
				'CursosInscripcion.*',
				'Inscripcion.*',
				'Curso.*',
				'Centro.*',
				'Persona.*',
				'Ciclo.nombre'
			),
			'limit' => 8,
			'order' => array('CursosInscripcion.curso_id' => 'ASC'),
			'joins' => array(
				array(
					'alias' => 'Alumno',
					'table' => 'alumnos',
					'type' => 'LEFT',
					'conditions' => '`Alumno`.`id` = `Inscripcion`.`alumno_id`'
				),
				array(
					'alias' => 'Persona',
					'table' => 'personas',
					'type' => 'LEFT',
					'conditions' => '`Persona`.`id` = `Alumno`.`persona_id`'
				),
				array(
					'alias' => 'Ciclo',
					'table' => 'ciclos',
					'type' => 'LEFT',
					'conditions' => '`Ciclo`.`id` = `Inscripcion`.`ciclo_id`'
				),
				array(
					'alias' => 'Centro',
					'table' => 'centros',
					'type' => 'LEFT',
					'conditions' => '`Centro`.`id` = `Inscripcion`.`centro_id`'
				)
			)
		);
		/* PAGINACIÓN SEGÚN ROLES DE USUARIOS (INICIO).
		*Sí el usuario es "admin" muestra los cursos del establecimiento. Sino sí es "usuario" externo muestra los cursos del nivel.
		*/

		// Se busca el nivel del servicio segun el centro_id del usuario
		$nivelCentroServicio = $this->Centro->find('first', array(
				'recursive' => -1,
				'fields'=>array('Centro.nivel_servicio'),
				'conditions'=>array('Centro.id'=>$userCentroId))
		);

		$nivelServicio = $nivelCentroServicio['Centro']['nivel_servicio'];
		switch($userRole)
		{
			case 'admin':
				$this->paginate['CursosInscripcion']['conditions'] = array(
					'Inscripcion.centro_id' => $userCentroId,
					'Inscripcion.estado_inscripcion' =>array('CONFIRMADA','NO CONFIRMADA')
				);
			break;
			case 'usuario':
				if($nivelServicio === 'Común - Inicial - Primario')
				{
					$nivelCentroId = $this->Centro->find('list', array(
						'fields'=>array('id'),
						'conditions'=>array(
							'nivel_servicio'=>array('Común - Inicial', 'Común - Primario')
						)
					));

					$this->paginate['CursosInscripcion']['conditions'] = array(
						'Inscripcion.centro_id' => $nivelCentroId,
						'Inscripcion.estado_inscripcion' =>array('CONFIRMADA','NO CONFIRMADA')
					);
				} else
				{
					$nivelCentroId = $this->Centro->find('list', array(
						'fields'=>array('id'),
						'conditions'=>array('nivel_servicio'=>$nivelServicio))
					);
					$this->paginate['CursosInscripcion']['conditions'] = array(
						'Inscripcion.centro_id' => $nivelCentroId,
						'Inscripcion.estado_inscripcion' =>array('CONFIRMADA','NO-CONFIRMADA')
					);
				}
				break;
		}
		/* FIN */

		/* PAGINACIÓN SEGÚN CRITERIOS DE BÚSQUEDAS (INICIO).
        *Pagina según búsquedas simultáneas ya sea por CENTRO y/o CURSO y/o INSCRIPCIÓN.
        */
		$this->redirectToNamed();
		$conditions = array();
		if(!empty($this->params['named']['centro_id'])) {
			$conditions['Inscripcion.centro_id ='] = $this->params['named']['centro_id'];
		}
		if(!empty($this->params['named']['ciclo_id'])) {
			$conditions['Inscripcion.ciclo_id ='] = $this->params['named']['ciclo_id'];
		}
		if(!empty($this->params['named']['turno'])) {
			$conditions['Curso.turno ='] = $this->params['named']['turno'];
		}
		if(!empty($this->params['named']['anio'])) {
			$conditions['Curso.anio ='] = $this->params['named']['anio'];
		}
		if(!empty($this->params['named']['inscripcion_id'])) {
			$conditions['CursosInscripcion.inscripcion_id ='] = $this->params['named']['inscripcion_id'];
		}

		// Inicializa la paginacion segun las condiciones
		$cursosInscripcions = $this->paginate('CursosInscripcion', $conditions);

		/*
		 * *********************
		 * 		IMPORTANTE
		 * *********************
		 * Falta filtrar estos combobox segun el tipo de usuario logueado
		 *
		 */
		$comboAnio = $this->Curso->find('list', array(
			'recursive'=> -1,
			'fields'=> array('Curso.anio','Curso.anio')
		));

		$comboDivision = $this->Curso->find('list', array(
			'recursive'=> -1,
			'fields'=> array('Curso.division','Curso.division')
		));

		$comboCiclo = $this->Ciclo->find('list', array(
			'fields'=>array('Ciclo.id', 'Ciclo.nombre')
		));

		/*
		 * ********************
		 * 		IMPORTANTE
		 * ********************
		 * La seccion no la filtra bien, debido a que no se deberia filtrar por id, por ej:
		 *  "1ro" tiene multiples cursos y deberia mostrar todos los primeros
		 */
		/*
		$comboSecciones = $this->Curso->find('list', array(
			'fields'=>array('nombre_completo_curso','nombre_completo_curso'),
		));
		*/
		$this->loadModel('Centro');
		$this->loadModel('Curso');
		$userCentroId = $this->getUserCentroId();
        $nivelCentro = $this->Centro->find('list', array('fields'=>array('nivel_servicio'), 'conditions'=>array('id'=>$userCentroId)));
        $userRol = $this->Auth->user('role');
		if ($userRol == 'superadmin') {
			$comboSecciones = $this->Curso->find('list', array('fields'=>array('id','nombre_completo_curso')));
		} else if (($userRol === 'usuario') && ($nivelCentro === 'Común - Inicial - Primario')) {
            $nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'conditions'=>array('nivel_servicio'=>array('Común - Inicial', 'Común - Primario'))));
            $comboSecciones = $this->Curso->find('list', array('fields'=>array('id','nombre_completo_curso'), 'conditions'=>array('centro_id'=>$nivelCentroId, 'status'=> '1')));
        } else if ($userRol === 'usuario') {
            $nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'conditions'=>array('nivel_servicio'=>$nivelCentro)));
            $comboSecciones = $this->Curso->find('list', array('fields'=>array('nombre_completo_curso'), 'conditions'=>array('centro_id'=>$nivelCentroId, 'status' => '1')));
        } else if ($userRol == 'admin') {
			$userCentroId = $this->getUserCentroId();
			$comboSecciones = $this->Curso->find('list', array('fields'=>array('id','nombre_completo_curso'), 'conditions'=>array('centro_id'=>$userCentroId, 'status' => '1')));
		}
		/* FIN */
		$this->set(compact('cursosInscripcions','comboAnio','comboDivision','comboCiclo','cicloIdActual','comboSecciones'));
	}
}