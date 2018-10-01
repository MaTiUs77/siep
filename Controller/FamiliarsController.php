<?php

App::uses('AppController', 'Controller');

class FamiliarsController extends AppController {

	var $name = 'Familiars';
	var $paginate = array('Familiar' => array('limit' => 3, 'order' => 'Familiar.id DESC'));

	function beforeFilter(){
	    parent::beforeFilter();
		/* ACCESOS SEGÚN ROLES DE USUARIOS (INICIO).
        *Si el usuario tiene un rol de superadmin le damos acceso a todo. Si no es así (se trata de un usuario "admin o usuario") tendrá acceso sólo a las acciones que les correspondan.
        */
        switch($this->Auth->user('role'))
		{
			case 'superadmin':
				if ($this->Auth->user('puesto') === 'Sistemas') {
					$this->Auth->allow();				
				} else {
					//En caso de ser ATEI
					$this->Auth->allow('add', 'view', 'edit', 'autocompleteNombrePersona', 'autocompleteNombreAlumno');	
				}
				break;
			case 'usuario':
			case 'admin':
				$this->Auth->allow('add', 'view', 'edit', 'autocompleteNombrePersona', 'autocompleteNombreAlumno');
				break;
		}
		/* FIN */
    }

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash('Familiar no valido', 'default', array('class' => 'alert alert-warning'));
			$this->redirect(array('controller' => 'personas', 'action' => 'index'));
		}
		$this->set('familiar', $this->Familiar->read(null, $id));
		/* SETS DE DATOS PARA PERSONAS (INICIO). */
		$this->loadModel('Persona');
		$personaNombre = $this->Persona->find('list', array('fields'=>array('id', 'nombre_completo_persona')));
        $personaNacionalidad = $this->Persona->find('list', array('fields'=>array('id', 'nacionalidad')));        
        $personaCuilCuit = $this->Persona->find('list', array('fields'=>array('id', 'cuil_cuit')));
        $personaOcupacion = $this->Persona->find('list', array('fields'=>array('id', 'ocupacion')));
        $personaLugarTrabaja = $this->Persona->find('list', array('fields'=>array('id', 'lugar_de_trabajo')));
        $personaCiudad = $this->Persona->find('list', array('fields'=>array('id', 'ciudad_id')));
        $this->loadModel('Ciudad');
        $ciudades = $this->Ciudad->find('list', array('fields'=>array('nombre')));
        $personaCalleNombre = $this->Persona->find('list', array('fields'=>array('id', 'calle_nombre')));
        $personaCalleNumero = $this->Persona->find('list', array('fields'=>array('id', 'calle_nro')));
        $personaTelefono = $this->Persona->find('list', array('fields'=>array('id', 'telefono_nro')));
        $personaEmail = $this->Persona->find('list', array('fields'=>array('id', 'email'))); 
        /* FIN */
        /* SETS DE DATOS PARA ALUMNOS RELACIONADOS (INICIO). */
        $this->loadModel('Alumno');
   		$alumnoId = $this->Familiar->AlumnosFamiliar->find('list', array('fields'=>array('alumno_id'), 'conditions'=>array('familiar_id'=>$id)));
   		$alumnoPersonaId = $this->Alumno->find('list', array('fields'=>array('persona_id'), array('conditions' => array('id' => $alumnoId, 'recursive' => -1))));
        $personaDocumentoTipo = $this->Persona->find('list', array('fields'=>array('id', 'documento_tipo')));
        $personaDocumentoNro = $this->Persona->find('list', array('fields'=>array('id', 'documento_nro')));
        /* FIN */
        $this->set(compact('personaNombre', 'personaNacionalidad', 'personaCuilCuit', 'personaOcupacion', 'personaLugarTrabaja', 'personaCiudad', 'ciudades', 'personaCalleNombre', 'personaCalleNumero', 'personaTelefono', 'personaEmail', 'alumnoPersonaId', 'personaDocumentoTipo', 'personaDocumentoNro'));
	}

	function add() {
		  //abort if cancel button was pressed  
          if(isset($this->params['data']['cancel'])){
                $this->Session->setFlash('Los cambios no fueron guardados. Agregación cancelada.', 'default', array('class' => 'alert alert-warning'));
                $this->redirect(array('controller' => 'personas', 'action' => 'index'));
		  }
   		  if (!empty($this->data)) {
			$this->Familiar->create();
			/* Guarda el id de persona del padre/tutor */
			//Obtengo personaId
            $personaId = $this->request->data['Persona']['persona_id'];
            // Propone guardar el id de persona en el campo persona_id. 
            $this->request->data['Familiar']['persona_id'] = $personaId;
            // Obtengo el id de persona del alumno.
			$alumnoPersonaIdArrayDos = $this->request->data['Alumno'];
			$alumnoPersonaIdArrayUno = $alumnoPersonaIdArrayDos['Alumno']; 
			// Obtengo el id del alumno relacionado a esa persona.
			$alumnoIdArrayDos = $this->Familiar->Alumno->find('list', array('fields'=>array('id'), 'conditions'=>array('persona_id'=>$alumnoPersonaIdArrayUno)));
			$alumnoIdArrayUno = $this->Familiar->Alumno->findById($alumnoIdArrayDos, 'id');
			$alumnoIdString	= $alumnoIdArrayUno['Alumno']['id'];
			$this->request->data['Alumno'] = $alumnoIdArrayDos;
			if ($this->Familiar->save($this->data)) {
				$this->Session->setFlash('El familiar ha sido grabado', 'default', array('class' => 'alert alert-success'));
				$inserted_id = $this->Familiar->id;
				$this->redirect(array('action' => 'view', $inserted_id));
			} else {
				$this->Session->setFlash('El familiar no fue grabado. Intentelo nuevamente.', 'default', array('class' => 'alert alert-danger'));
			}
		}
		$centroId = $this->getUserCentroId();
		$alumnoPersonaId = $this->Familiar->Alumno->find('list', array('fields'=>array('persona_id'), 'conditions'=>array('centro_id'=>$centroId)));
        $this->loadModel('Persona');
        $alumnosNombre = $this->Persona->find('list', array('fields'=>array('id', 'nombre_completo_persona'), 'conditions' => array('id' => $alumnoPersonaId)));
        $this->set(compact('alumnosNombre'));
    }

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash('Familiar no valido', 'default', array('class' => 'alert alert-warning'));
			$this->redirect(array('controller' => 'personas', 'action' => 'index'));
		}
		if (!empty($this->data)) {
		  //abort if cancel button was pressed  
          if(isset($this->params['data']['cancel'])){
                $this->Session->setFlash('Los cambios no fueron guardados. Edición cancelada.', 'default', array('class' => 'alert alert-warning'));
                $this->redirect( array('controller' => 'familiars', 'action' => 'view', $id));
		  }
		  if ($this->Familiar->save($this->data)) {
				$this->Session->setFlash('El familiar ha sido grabado', 'default', array('class' => 'alert alert-success'));
				//$this->redirect($this->referer());
				//$this->redirect(array('controller' => 'alumnos','action' => 'index'));
				$inserted_id = $this->Familiar->id;
				$this->redirect(array('action' => 'view', $inserted_id));
			} else {
				$this->Session->setFlash('El familiar no fue grabado. Intentelo nuevamente.', 'default', array('class' => 'alert alert-danger'));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Familiar->read(null, $id);
		}
		$alumnoId = $this->Familiar->Alumno->find('list', array('fields'=>array('id', 'persona_id')));
        $this->loadModel('Persona');
        $alumnos = $this->Persona->find('list', array('fields'=>array('id', 'nombre_completo_persona'), 'conditions' => array('id' => $alumnoId)));
        $personas = $this->Familiar->Persona->find('list', array('fields'=>array('id', 'nombre_completo_persona')));
        $this->set(compact('alumnos', 'personas'));
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash('Id no valido para familiar', 'default', array('class' => 'alert alert-warning'));
			$this->redirect(array('controller' => 'personas', 'action' => 'index'));
		}
		if ($this->Familiar->delete($id)) {
			$this->Session->setFlash('El Familiar ha sido borrado', 'default', array('class' => 'alert alert-success'));
			$this->redirect(array('controller' => 'personas', 'action' => 'index'));
		}
		$this->Session->setFlash('Familiar no fue borrado', 'default', array('class' => 'alert alert-danger'));
		$this->redirect(array('controller' => 'personas', 'action' => 'index'));
	}

	/* AUTOCOMPLETE PARA EL FORMULARIO DE AGREGACIÓN (INICIO).
	*  Sólo muestra las personas con perfíl de familiar.
	*/
	public function autocompleteNombrePersona() {

		$conditions = array();
		$term = $this->request->query('term');

		if(!empty($term))
		{
			// Si se busca un numero de documento.. se raliza el siguiente filtro
			if(is_numeric($term)) {
				$conditions[] = array('Persona.documento_nro LIKE' => $term . '%');
			} else {
				// Se esta buscando por nombre y/o apellidos
				$terminos = explode(' ', trim($term));
				$terminos = array_diff($terminos,array(''));

				// Esto es posible porque nombre_completo_persona esta definido en el modelo como virtual
				foreach($terminos as $termino) {
					$conditions[] = array('nombre_completo_persona LIKE' => '%' . $termino . '%');
				}
			}

			$this->loadModel('Persona');
			$personaId = $this->Persona->find('list', array('fields'=>array('id'), 'conditions'=>array('familiar'=>1)));
			$personas = $this->Persona->find('all', array(
					'recursive'	=> -1,
					// Condiciona la búsqueda también por id de persona con perfil de alumno.
					'conditions' => array($conditions, 'id' => $personaId),
					'fields' 	=> array('id', 'nombre_completo_persona','documento_nro'))
			);

			echo json_encode($personas);
		}

		$this->autoRender = false;
	}

	/* AUTOCOMPLETE PARA LOS FORMULARIOS ADD Y EDIT (INICIO).
	*  Sí el usuario es "admin" muestra sólo los alumnos del establecimiento.
	*  Sino sí es "usuario", muestra los alumnos del nivel correspondiente al centro.
	*  Sino sí es "superadmin" muestra todos los alumnos.
	*/
	public function autocompleteNombreAlumno() {
		$conditions = array();
		$term = $this->request->query('term');

		// Primero obtiene el termino a buscar
		if(!empty($term))
		{
			// Si se busca un numero de documento.. se raliza el siguiente filtro
			if(is_numeric($term)) {
				$conditions[] = array('Persona.documento_nro LIKE' => $term . '%');
			} else {
				// Se esta buscando por nombre y/o apellidos
				$terminos = explode(' ', trim($term));
				$terminos = array_diff($terminos,array(''));

				foreach($terminos as $termino) {
					$conditions[] = array(
						'OR' => array(
							array('Persona.apellidos LIKE' => $term . '%'),
							array('Persona.nombres LIKE' => $term . '%')
						)
					);
				}
			}
			$userRole = $this->Auth->user('role');
			$userCentroId = $this->getUserCentroId();
			$this->loadModel('Centro');
			//$nivelCentro = $this->Centro->find('list', array('fields'=>array('nivel_servicio'), 'conditions'=>array('id'=>$userCentroId)));
			$nivelCentroArray = $this->Centro->findById($userCentroId, 'nivel_servicio');
			$nivelCentroString = $nivelCentroArray['Centro']['nivel_servicio'];
			if ($userRole === 'admin') {
				$personas = $this->Alumno->find('all', array(
						'recursive'	=> 0,
						'contain' => 'Persona',
						// Condiciona la búsqueda también por id de persona de los alumnos del centro correspondiente.
						'conditions' => array($conditions, 'centro_id'=>$userCentroId),
						'fields' 	=> array('Alumno.id', 'Persona.nombres', 'Persona.apellidos', 'Persona.documento_nro')
					)
				);
			} else if (($userRole === 'usuario') && ($nivelCentroString === 'Común - Inicial - Primario')) {
				$nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'conditions'=>array('nivel_servicio'=>array('Común - Inicial', 'Común - Primario'))));
				$personas = $this->Alumno->find('all', array(
						'recursive'	=> 0,
						'contain' => 'Persona',
						// Condiciona la búsqueda también por id de persona de los alumnos del centro correspondiente.
						'conditions' => array($conditions, 'centro_id'=>$nivelCentroId),
						'fields' 	=> array('Alumno.id', 'Persona.nombres', 'Persona.apellidos', 'Persona.documento_nro')
					)
				);
			} else if ($userRole === 'usuario') {
				// Obtiene el id de persona del nivel del centro correspondiente.
				$nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'conditions'=>array('nivel_servicio'=>$nivelCentroString)));
				$personas = $this->Alumno->find('all', array(
						'recursive'	=> 0,
						'contain' => 'Persona',
						// Condiciona la búsqueda también por id de persona de los alumnos del centro correspondiente.
						'conditions' => array($conditions, 'centro_id'=>$nivelCentroId),
						'fields' 	=> array('Alumno.id', 'Persona.nombres', 'Persona.apellidos', 'Persona.documento_nro')
					)
				);
			} else if ($userRole === 'superadmin') {
				$personas = $this->Alumno->find('all', array(
					'recursive'	=> 0,
					'contain' => 'Persona',
					// Condiciona la búsqueda también por id de persona de los alumnos del centro correspondiente.
					'conditions' => array($conditions),
					'fields' 	=> array('Alumno.id', 'Persona.nombres', 'Persona.apellidos', 'Persona.documento_nro')
					)
				);
			}
			echo json_encode($personas);
			}
			// No renderiza el layout
			$this->autoRender = false;
		}
		/* FIN */
}
?>
