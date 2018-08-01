<?php
App::uses('AppController', 'Controller');

class InscripcionsController extends AppController {

	var $name = 'Inscripcions';
    var $paginate = array('Inscripcion' => array(
        'contain' => array('Centro', 'Ciclo', 'Alumno'),
        'limit' => 4,
        'order' => 'Inscripcion.fecha_alta DESC'));

	function beforeFilter(){
	    parent::beforeFilter();
		/* ACCESOS SEGÚN ROLES DE USUARIOS (INICIO).
        *Si el usuario tiene un rol de superadmin le damos acceso a todo. Si no es así (se trata de un usuario "admin o usuario") tendrá acceso sólo a las acciones que les correspondan.
        */
        if ($this->Auth->user('role') === 'superadmin') {
	        $this->Auth->allow();
	    } elseif ($this->Auth->user('role') === 'usuario') {
	        $this->Auth->allow('index', 'add', 'view', 'edit');
	    } else if ($this->Auth->user('role') === 'admin') {
            $this->Auth->allow('index', 'add', 'view', 'edit');
        }
	    /* FIN */
        /* FUNCIÓN PRIVADA "LISTS" (INICIO).
        *Si se ejecutan las acciones add/edit activa la función privada "lists".
		*/
		if ($this->ifActionIs(array('add', 'edit'))) {
			$this->__lists();
		}
		/* FIN */
        App::uses('HttpSocket', 'Network/Http');
    }

	public function index() {
        $this->Inscripcion->recursive = 0;
		$this->paginate['Inscripcion']['contain'] = 'Alumno.Persona';
		$this->paginate['Inscripcion']['limit'] = 4;
		$this->paginate['Inscripcion']['order'] = array('Inscripcion.fecha_alta' => 'DESC');
		/* PAGINACIÓN SEGÚN ROLES DE USUARIOS (INICIO).
		*Sí el usuario es "admin" muestra los cursos del establecimiento. Sino sí es "usuario" externo muestra los cursos del nivel.
		*/
        $userRole = $this->Auth->user('role');
        $userCentroId = $this->getUserCentroId();
        $this->loadModel('Centro');
        $this->Centro->recursive = 0;
        $this->Centro->Behaviors->load('Containable');
        $nivelCentroArray = $this->Centro->findById($userCentroId, 'nivel_servicio');
        $nivelCentro = $nivelCentroArray['Centro']['nivel_servicio'];
        $nivelCentroId = $this->Centro->find('list', array(
            'fields'=>array('id'),
            'contain'=>false,
            'conditions'=>array(
                'nivel_servicio'=>$nivelCentro)));
		if ($this->Auth->user('role') === 'admin') {
        $this->paginate['Inscripcion']['conditions'] = array('Inscripcion.centro_id' => $userCentroId, 'Inscripcion.estado_inscripcion' =>array('CONFIRMADA', 'NO CONFIRMADA', 'BAJA', 'EGRESO'));    
        } else if (($userRole === 'usuario') && ($nivelCentro === 'Común - Inicial - Primario')) {
			$nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>array('Común - Inicial', 'Común - Primario'))));
			$this->paginate['Inscripcion']['conditions'] = array('Inscripcion.centro_id' => $nivelCentroId, 'Inscripcion.estado_inscripcion' =>array('CONFIRMADA', 'NO CONFIRMADA', 'BAJA', 'EGRESO'));
		} else if ($userRole === 'usuario') {
			$nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>$nivelCentro)));
			$this->paginate['Inscripcion']['conditions'] = array('Inscripcion.centro_id' => $nivelCentroId, 'Inscripcion.estado_inscripcion' =>array('CONFIRMADA', 'NO CONFIRMADA', 'BAJA', 'EGRESO'));
		}
		/* FIN */
    	/* PAGINACIÓN SEGÚN CRITERIOS DE BÚSQUEDAS (INICIO).
		*Pagina según búsquedas simultáneas ya sea por CICLO y/o CENTRO y/o LEGAJO y/o ESTADO.
		*/
    	$this->redirectToNamed();
		$conditions = array();
		if (!empty($this->params['named']['ciclo_id'])) {
			$conditions['Inscripcion.ciclo_id ='] = $this->params['named']['ciclo_id'];
		}
		if (!empty($this->params['named']['centro_id'])) {
			$conditions['Inscripcion.centro_id ='] = $this->params['named']['centro_id'];
		}
		if (!empty($this->params['named']['legajo_nro'])) {
			$conditions['Inscripcion.legajo_nro ='] = $this->params['named']['legajo_nro'];
		}
		if(!empty($this->params['named']['tipo_inscripcion'])) {
            $conditions['Inscripcion.tipo_inscripcion ='] = $this->params['named']['tipo_inscripcion'];
        }
        if(!empty($this->params['named']['estado_documentacion'])) {
			$conditions['Inscripcion.estado_documentacion ='] = $this->params['named']['estado_documentacion'];
		}
        if(!empty($this->params['named']['estado_inscripcion'])) {
            $conditions['Inscripcion.estado_inscripcion ='] = $this->params['named']['estado_inscripcion'];
        }
		$inscripcions = $this->paginate('Inscripcion',$conditions);
		/* FIN */
		/* SETS DE DATOS PARA COMBOBOX (INICIO). */
		/* Carga de Ciclos */
        $this->Inscripcion->Ciclo->recursive = 0;
        $ciclos = $this->Inscripcion->Ciclo->find('list', array(
            'fields'=>array('id', 'nombre'),
            'contain'=>false
            ));
        /* Carga combobox de Centros
        *  Sí es superadmin carga todos los centros.
        *  Sino sí es un usario de Inicial/Primaria, carga los centros de ambos niveles.
        *  Sino sí es un usuario del resto de los niveles, carga los centros del nivel correspondientes.     
        */
		if ($userRole == 'superadmin') {
			$centros = $this->Inscripcion->Centro->find('list', array('fields'=>array('id', 'sigla'), 'contain'=>false));
		} else if (($userRole === 'usuario') && ($nivelCentro === 'Común - Inicial - Primario')) {
			$nivelCentroId = $this->Inscripcion->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>array('Común - Inicial', 'Común - Primario')))); 		
			$centros = $this->Inscripcion->Centro->find('list', array('fields'=>array('sigla'), 'contain'=>false, 'conditions'=>array('id'=>$nivelCentroId)));
        } else if ($userRole === 'usuario') {
            $nivelCentroId = $this->Inscripcion->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>$nivelCentro)));       
            $centros = $this->Inscripcion->Centro->find('list', array('fields'=>array('sigla'), 'contain'=>false, 'conditions'=>array('id'=>$nivelCentroId)));
        } else if ($userRole == 'admin') {
			$centros = $this->Inscripcion->Centro->find('list', array('fields'=>array('id', 'sigla'), 'contain'=>false, 'conditions'=>array('id'=>$nivelCentroId)));
		}
		$this->set(compact('inscripcions', 'centros', 'ciclos'));
	}

    public function view($id = null) {
        $this->Inscripcion->recursive = 0;
        if (!$id) {
            $this->Session->setFlash('Inscripcion no valida.', 'default', array('class' => 'alert alert-warning'));
            $this->redirect(array('action' => 'index'));
        }
        //Obtenemos datos de la inscripcion desde el API
        $apiInscripcion = $this->consumeApiFindInscripcion($id);
        // Si no existe error al consumir el api
        if(!isset($apiInscripcion['error']))
        {
            $curso = $apiInscripcion['curso'];
            $inscripcion = $apiInscripcion['inscripcion'];

            $this->set(compact('inscripcion','curso'));
        } else {
            // Error al consumir el API
            $this->Session->setFlash($apiInscripcion['error'], 'default', array('class' => 'alert alert-danger'));
            $this->redirect(array('action' => 'index'));
        }
        //Sí se trata de una inscripción por hermano, obtiene el nombre completo del hermano.  
        $tipoInscripcionArray = $this->Inscripcion->findById($id, 'tipo_inscripcion');
        $tipoInscripcion = $tipoInscripcionArray['Inscripcion']['tipo_inscripcion'];
        if ($tipoInscripcion == 'Hermano de alumno regular') {
            //Obtención del id de alumno del hermano.
            $hermanoIdArray = $this->Inscripcion->findById($id, 'hermano_id');
            $hermanoId = $hermanoIdArray['Inscripcion']['hermano_id'];
            //Obtención del id de persona del hermano.
            $this->loadModel('Alumno');
            $this->Alumno->recursive = 0;
            $this->Alumno->Behaviors->load('Containable');
            $personaIdArray = $this->Alumno->findById($hermanoId, 'persona_id');
            $personaId = $personaIdArray['Alumno']['persona_id'];
            //Obtención del nombre completo del hermano.
            $this->loadModel('Persona');
            $this->Persona->recursive = 0;
            $this->Persona->Behaviors->load('Containable');
            $hermanoNombreArray = $this->Persona->findById($personaId, 'nombre_completo_persona');
            $hermanoNombre = $hermanoNombreArray['Persona']['nombre_completo_persona'];
            //Envío de dato a la vista.
            $this->set(compact('hermanoNombre'));
        }
        //Sí se trata de una inscripción por pase, obtiene el nombre de la institución origen.
        if ($tipoInscripcion == 'Pase') {
            //Obtención del id del centro de origen.
            $centroOrigenIdArray = $this->Inscripcion->findById($id, 'centro_origen_id');
            $centroOrigenId = $centroOrigenIdArray['Inscripcion']['centro_origen_id'];
            //Obtención del nombre de ese centro de origen.
            $this->loadModel('Centro');
            $this->Centro->recursive = 0;
            $this->Centro->Behaviors->load('Containable');
            $centroOrigenNombreArray = $this->Centro->findById($centroOrigenId, 'sigla');
            $centroOrigenNombre = $centroOrigenNombreArray['Centro']['sigla'];
            //Envío de dato a la vista.
            $this->set(compact('centroOrigenNombre'));
        }
    }

	public function add() {
        $this->Inscripcion->recursive = 0;
        /* BOTÓN CANCELAR (INICIO) */
        if (isset($this->params['data']['cancel'])) {
            $this->Session->setFlash('Los cambios no fueron guardados. Agregación cancelada.', 'default', array('class' => 'alert alert-warning'));
            $this->redirect( array( 'action' => 'index' ));
		}
	    /* FIN */
        /* INICIO: PERMISO PARA AGREGAR SEGÚN EL NIVEL DEL CENTRO DEL USUARIO */
        //Se obtiene el rol del usuario
        $userRole = $this->Auth->user('role');
        //Se obtiene el centro del usuario
        $userCentroId = $this->getUserCentroId();
        $userData = $this->Auth->user();
        if($userRole == 'admin') {
            switch($userData['Centro']['nivel_servicio']) {
                case 'Común - Inicial':
                case 'Común - Primario':
                case 'Común - Secundario':
                case 'Adultos - Primario':
                case 'Adultos - Secundario':
                //  PERMITIDOS AGREGAR
                    break;
                default:
                    $this->Session->setFlash('No tiene permisos para agregar inscripciones.', 'default', array('class' => 'alert alert-warning'));
                    $this->redirect( array( 'action' => 'index' ));
                    break;
            }
        }
        $this->Inscripcion->contain(array('Centro', 'Ciclo'));
        /* FIN */
        //Al realizar SUBMIT
        if (!empty($this->data)) {
            $this->Inscripcion->create();
            //Se genera el id del usuario
            $this->request->data['Inscripcion']['usuario_id'] = $this->Auth->user('id');
            //La fecha de alta se toma del servidor php al momento de ejecutar el controlador
            $this->request->data['Inscripcion']['fecha_alta'] = date('Y-m-d');
            /* DEFINICIÓN DEL CENTRO DE ORIGEN SEGÚN EL ROL DEL USUARIO (INICIO) */
            switch($userRole) {
                case 'superadmin':
                case 'usuario':
                    // Usa el centro especificado en el formulario
                    $userCentroId = $this->request->data['Inscripcion']['centro_id'];
                break;
                case 'admin':
                    $this->request->data['Inscripcion']['centro_id'] = $userCentroId;
                break;
            }
            /* FIN */
            // Luego de seleccionar el ciclo, se deja en los datos que se intentarán guardar.
            $cicloId = $this->request->data['Inscripcion']['ciclo_id'];
            $this->Inscripcion->Ciclo->recursive = 0;
            $ciclos = $this->Inscripcion->Ciclo->findById($cicloId, 'nombre');
            $ciclo = substr($ciclos['Ciclo']['nombre'], -2);
            // Obtiene la división del curso...
            $this->loadModel('Curso');
            $this->Curso->recursive = 0;
            $this->Curso->Behaviors->load('Containable');
            $cursoIdArray = $this->request->data['Curso'];
            $cursoIdString = $cursoIdArray['Curso'];
            $divisionArray = $this->Curso->findById($cursoIdString, 'division');
            $divisionString = $divisionArray['Curso']['division'];
            // No hay que continuar con la inscripcion si no se definio el centro_id, y el curso!
            if (count($divisionArray)<=0) {
                $this->Session->setFlash('No definio la sección.', 'default', array('class' => 'alert alert-danger'));
                $this->redirect($this->referer());
            }
            /* INICIO: VERIFICACION DE DEFINICIÓN DE LA PERSONA */
            //Antes que nada obtengo personaId
            $personaId = $this->request->data['Persona']['persona_id'];
            if (empty($personaId)) {
                //No esta definida? terminamos volvemos al formulario anterior
                $this->Session->setFlash('No se definio la persona.', 'default', array('class' => 'alert alert-danger'));
                $this->redirect($this->referer());
            }
            /* FIN */
            /* INICIO: GENERACIÓN DEL CÓDIGO DE INSCRIPCIÓN */
            //Obtención del DNI de la persona.
            $this->loadModel('Persona');
            $this->Persona->recursive = 0;
            $this->Persona->Behaviors->load('Containable');
            $persona = $this->Persona->findById($personaId,'id, documento_nro');
            $personaDni = $persona['Persona']['documento_nro'];
            //Genera del código de inscripción y se deja en los datos que se intentaran guardar.
            //Obtiene el tipo de inscripción actual. 
            $tipoInscripcionActual = $this->request->data['Inscripcion']['tipo_inscripcion'];
            // Obtiene número de pase para el ciclo actual.
            // Sí el tipo de inscripción actual es PASE, genera un código específico.
            if ($tipoInscripcionActual == 'Pase') {
                $paseNro = 0;
                //Busca el número de pase que corresponde al ciclo actual.
                do { 
                    $paseNro += 1;
                    $codigoPrueba = $this->__getCodigoPase($ciclo, $personaDni, $paseNro);
                    $cuentaInscripcionPase = $this->Inscripcion->find('count',array(
                                    'contain' => false,
                                    'conditions' => array('Inscripcion.legajo_nro' => $codigoPrueba)
                                    ));
                } while ($cuentaInscripcionPase != 0);
                $codigoActualPase = $this->__getCodigoPase($ciclo, $personaDni, $paseNro);
            } 
            $codigoActual = $this->__getCodigo($ciclo, $personaDni);
            /* FIN */
            /* INICIO: Comprobación de unicidad del código de inscripción en la base de datos. */
            $existePersonaInscripta = $this->Inscripcion->find('first',array(
                 'contain' => false,
                 'conditions' => array('Inscripcion.legajo_nro' => $codigoActual)
            ));
            $this->loadModel('Centro');
            $this->Centro->recursive = 0;
            $this->Centro->Behaviors->load('Containable');
            //
            switch ($tipoInscripcionActual) {
                case 'Pase':
                    //Si existe una inscripción del actual ciclo relacionada continúa el proceso. Sino indica mensaje y detiene el proceso.                        
                    if (isset($existePersonaInscripta['Inscripcion']['legajo_nro'])) {
                        //Obtención del estado actual de la inscripción
                        $inscripcionEstadoActualArray = $this->Inscripcion->findByLegajoNro($codigoActual, 'estado_inscripcion');
                        $inscripcionEstadoActual = $inscripcionEstadoActualArray['Inscripcion']['estado_inscripcion'];
                        //Si es estado de inscripción actual es BAJA, continúa con la nueva inscripción por pase. Sino indica mensaje y detiene el proceso.
                        if ($inscripcionEstadoActual == 'BAJA') {
                           $this->request->data['Inscripcion']['legajo_nro'] = $codigoActualPase;
                        } else {
                           $this->Session->setFlash(sprintf("El alumno debe estar dado de baja para realizar el pase."), 'default', array('class' => 'alert alert-danger'));
                           $this->redirect($this->referer());
                        }                       
                    } else {
                        $this->Session->setFlash(sprintf("El alumno debe registrar inscripción en este ciclo para realizar el pase."), 'default', array('class' => 'alert alert-danger'));
                        $this->redirect($this->referer());
                    }
                    break;
                case 'Común':
                case 'Hermano de alumno regular':
                case 'Integración':
                case 'Situación social':
                    if (isset($existePersonaInscripta['Inscripcion']['legajo_nro'])) {
                        $this->Session->setFlash(sprintf("El alumno ya está inscripto para este ciclo en %s", $existePersonaInscripta['Centro']['nombre']), 'default', array('class' => 'alert alert-danger'));
                    } else {
                        $this->request->data['Inscripcion']['legajo_nro'] = $codigoActual;
                    }
                    break;
                default:
                    $this->Session->setFlash(sprintf("Error al indicar el tipo de inscripción."), 'default', array('class' => 'alert alert-danger'));
                    break;
            }
            /* FIN */
            /* INICIO: VERIFICACIONES PARA CREACIÓN DEL ALUMNO (cualquiera sea el tipo de inscripción) */
            //Verifica si la persona se encuentra inscripta como alumno              
            $this->loadModel('Alumno');
            $this->Alumno->Behaviors->load('Containable');
            $this->Alumno->recursive = 0;
            $alumno = $this->Alumno->findByPersonaId($personaId);
            //Obtención del código anterior de inscripción.
            $codigoAnterior = $this->__getCodigo(($ciclo - 1), $personaDni);
            //Verficación de existencia de inscripción con ese código anterior.
            $existeInscripcionAnterior = $this->Inscripcion->find('first', array(
                'contain' => false,   
                'conditions' => array('Inscripcion.legajo_nro' => $codigoAnterior)));
            //Si existe inscripción anterior, obtiene el centro de esa inscripción.
            if (isset($existeInscripcionAnterior['Inscripcion']['legajo_nro'])) {
                $inscripcionAnterior = $this->Inscripcion->findByLegajoNro($codigoAnterior, 'centro_id');
                $inscripcionAnteriorCentro = $inscripcionAnterior['Inscripcion']['centro_id'];
            } else {
                $inscripcionAnteriorCentro = 0;
            }
            // Si el alumno no fue creado, o si el id del centro a inscribir es diferente al centro en el que se encontraba el alumno en el ciclo anterior, crea el alumno y le asigna el id del centro actual. 
            if (count($alumno) == 0 || $userCentroId != $inscripcionAnteriorCentro) {
                // Crear alumno
                $this->Alumno->create();
                $insert = array(
                        'Alumno' => array(
                            'created' => '2017-09-08 12:01',
                            'persona_id' => $personaId,
                            'centro_id' => $userCentroId));
                $alumno = $this->Alumno->save($insert);
                if (!$alumno['Alumno']['id']) {
                    print_r("Error al registrar a la persona como alumno");
                    die;
                }
            }
            $this->request->data['Inscripcion']['alumno_id'] = $alumno['Alumno']['id'];
            /* FIN */
            /* INICIO:  Definición del estado de la documentación según el nivel del centro.*/
            $userCentroNivel = $this->getUserCentroNivel($userCentroId);
            switch($userCentroNivel) {
                case 'Común - Inicial':
                case 'Común - Primario':
                        if(($this->request->data['Inscripcion']['fotocopia_dni'] ==1) && ($this->request->data['Inscripcion']['partida_nacimiento_alumno'] ==1) && ($this->request->data['Inscripcion']['certificado_vacunas'] ==1)) {
                               $estadoDocumentacion = "COMPLETA";
                        } else {
                                $estadoDocumentacion = "PENDIENTE";
                        }
                    break;
                case 'Común - Secundario':
                        if(($this->request->data['Inscripcion']['fotocopia_dni'] ==1) && ($this->request->data['Inscripcion']['partida_nacimiento_alumno'] ==1) && ($this->request->data['Inscripcion']['certificado_vacunas'] ==1) && ($this->request->data['Inscripcion']['certificado_septimo'] ==1)) {
                                $estadoDocumentacion = "COMPLETA";
                        } else {
                                $estadoDocumentacion = "PENDIENTE";   
                        }                        
                    break;
                default:
                       $estadoDocumentacion = "PENDIENTE";
            }
            //Se genera el estado y se deja en los datos que se intentaran guardar
            $this->request->data['Inscripcion']['estado_documentacion'] = $estadoDocumentacion;
            /* FIN */
            /* INICIO: Adecúa mensajes para los combobox dependientes según el tipo de inscripción. */
            switch($this->request->data['Inscripcion']['tipo_inscripcion']) {
                case 'Hermano de alumno regular':
                    $hermano  = $this->Alumno->findById($this->request->data['Inscripcion']['hermano_id']);
                    if (count($hermano) == 0) {
                        $this->Session->setFlash('No se localizo al hermano como alumno.', 'default', array('class' => 'alert alert-danger'));
                        $this->redirect($this->referer());
                    }
                    break;
                case 'Pase':
                    $centroOrigen = $this->Centro->findById($this->request->data['Inscripcion']['centro_origen_id']);
                    // Aca puede ir la logica de que nivel de servicio es necesario para guardar la inscripcion por pase
                    if (count($centroOrigen) == 0) {
                        $this->Session->setFlash('No se localizo el centro origen para el pase', 'default', array('class' => 'alert alert-danger'));
                        $this->redirect($this->referer());
                    }
                    break;
                }
            /* FIN */
            if ($this->Inscripcion->save($this->data)) {
                /* ATUALIZA MATRÍCULA Y VACANTES (INICIO).
                *  Al registrarse una Inscripción sí es para el ciclo actual o para un agrupamiento 
                *  para el próximo ciclo, actualiza valores de matrícula y vacantes del curso correspondiente.
                */
                // Obtiene el ciclo id...
                $this->loadModel('Ciclo');
                $this->Ciclo->recursive = 0;
                $this->Ciclo->Behaviors->load('Containable');
                $cicloIdActual = $this->getActualCicloId();
                $cicloIdActualArray = $this->Ciclo->findById($cicloIdActual, 'id');
                $cicloIdActualString = $cicloIdActualArray['Ciclo']['id'];
                $cursoIdInt = $cursoIdString[0];
                $this->loadModel('CursosInscripcion');
                $this->CursosInscripcion->recursive = 0;
                $this->CursosInscripcion->Behaviors->load('Containable');
                $matriculaActual = $this->CursosInscripcion->query("
                    SELECT COUNT(*) AS `matriculas` 
                    FROM `siep`.`cursos_inscripcions` AS CursosInscripcion
                    LEFT JOIN `siep`.`inscripcions` AS Inscripcion on Inscripcion.id = CursosInscripcion.inscripcion_id       
                    WHERE 
                        CursosInscripcion.curso_id = $cursoIdInt AND 
                        Inscripcion.ciclo_id = $cicloIdActualString");
                $matriculaActual = $matriculaActual[0][0]['matriculas'];
                $this->Curso->id=$cursoIdString;
                $this->Curso->saveField("matricula", $matriculaActual);
                $plazasArray = $this->Curso->findById($cursoIdString, 'plazas');
                $plazasString = $plazasArray['Curso']['plazas'];
                $vacantesActual = $plazasString - $matriculaActual;
                $this->Curso->saveField("vacantes", $vacantesActual);
                /* FIN */
                $inserted_id = $this->Inscripcion->id;
                    /*
                     * __ LINEAS PARA DEBUG __
                    echo '<pre>';
                    print_r($inserted_id);
                    print_r($this->request->data);
                    echo '</pre>';
                    die;
                    */
                $this->Session->setFlash('La inscripcion ha sido grabada.', 'default', array('class' => 'alert alert-success'));
                $this->redirect(array('action' => 'view', $inserted_id));
            } else {
                    $this->Session->setFlash('La inscripcion no fue grabada. Intente nuevamente.', 'default', array('class' => 'alert alert-danger'));
            }
        }
    }

	public function edit($id = null) {
        $this->Inscripcion->recursive = 0;
        if (!$id && empty($this->data)) {
			$this->Session->setFlash('Inscripcion no valida.', 'default', array('class' => 'alert alert-warning'));
			$this->redirect(array('action' => 'index'));
		}
        // Obtención de estados de inscripción anterior y actual.
        $estadoInscripcionAnteriorArray = $this->Inscripcion->findById($id, 'estado_inscripcion');
        $estadoInscripcionAnterior = $estadoInscripcionAnteriorArray['Inscripcion']['estado_inscripcion'];
        // Obtención del registro relación curso-inscripción correspondiente a la inscripción.
        $this->loadModel('CursosInscripcion');
        $this->CursosInscripcion->recursive = 0;
        $this->CursosInscripcion->Behaviors->load('Containable');
        $cursoInscripcion  = $this->CursosInscripcion->find('first',[
            'contains' => false,
            'conditions' => ['Inscripcion.id'=> $id]
        ]);
        // Obtención del alumno correspondiente a la inscripción.
        $this->loadModel('Alumno');
        $this->Alumno->recursive = 0;
        $this->Alumno->Behaviors->load('Containable');
        $alumno = $this->Alumno->find('first',[
            'contains' => false,
            'conditions' => ['Alumno.id'=> $cursoInscripcion['Inscripcion']['alumno_id']]
        ]);
        // En este punto tengo al alumno y a la persona relacionadas al id de inscripcion.
        $alumnoId = $alumno['Alumno']['id'];
        $personaId  = $alumno['Persona']['id'];
        // Obtención del ciclo actual.
        $hoyArray = getdate();
        $this->loadModel('Ciclo');
        $this->Ciclo->recursive = 0;
        $this->Ciclo->Behaviors->load('Containable');
        $cicloActual = $this->Ciclo->find('first', array(
            'contain' => false,
            'conditions' => array('nombre' => $hoyArray['year'])
        ));
        $cicloActual = array_pop($cicloActual);
        // Submit de formulario
    	if (!empty($this->data)) {
            //abort if cancel button was pressed
            if(isset($this->params['data']['cancel'])){
                $this->Session->setFlash('Los cambios no fueron guardados. Edición cancelada.', 'default', array('class' => 'alert alert-warning'));
                $this->redirect( array( 'action' => 'index' ));
		    }
            /* INICIO:  Definición del estado de la documentación según el nivel del centro.*/
            $userCentroId = $this->getUserCentroId();
            $userCentroNivel = $this->getUserCentroNivel($userCentroId);
            switch($userCentroNivel) {
                case 'Común - Inicial':
                case 'Común - Primario':
                    if(($this->request->data['Inscripcion']['fotocopia_dni'] ==1) && ($this->request->data['Inscripcion']['partida_nacimiento_alumno'] ==1) && ($this->request->data['Inscripcion']['certificado_vacunas'] ==1)) {
                        $estadoDocumentacion = "COMPLETA";
                    } else {
                        $estadoDocumentacion = "PENDIENTE";
                    }
                    break;
                case 'Común - Secundario':
                    if(($this->request->data['Inscripcion']['fotocopia_dni'] ==1) && ($this->request->data['Inscripcion']['partida_nacimiento_alumno'] ==1) && ($this->request->data['Inscripcion']['certificado_vacunas'] ==1) && ($this->request->data['Inscripcion']['certificado_septimo'] ==1)) {
                        $estadoDocumentacion = "COMPLETA";
                    } else {
                        $estadoDocumentacion = "PENDIENTE";   
                    }                        
                    break;
                default:
                    $estadoDocumentacion = "PENDIENTE";
            }
            //Se genera el estado y se deja en los datos que se intentaran guardar
            $this->request->data['Inscripcion']['estado_documentacion'] = $estadoDocumentacion;
            /*FIN*/
            /* INICIO: Se define el id del centro en función del rol.*/
            //Se obtiene el rol del usuario
            $userRole = $this->Auth->user('role');
            switch($userRole) {
                case 'superadmin':
                case 'usuario':
                    // Usa el centro especificado en el formulario
                    $userCentroId = $this->request->data['Inscripcion']['centro_id'];
                break;
                case 'admin':
                    // Usa el centro definido para el usuario
                    $userCentroId = $this->getUserCentroId();
                    $this->request->data['Inscripcion']['centro_id'] = $userCentroId ;
                break;
            }
            /* FIN */
            // Obtiene la división del curso "Seleccionado"
            $cursoIdArray = $this->request->data['Curso'];
            $cursoIdString = $cursoIdArray['Curso'];
            $this->loadModel('Curso');
            $this->Curso->recursive = 0;
            $this->Curso->Behaviors->load('Containable');
            $nuevoCurso = $this->Curso->findById($cursoIdString);
            // Es necesario tener una seccion definida para la edicion
            if (count($nuevoCurso) <= 0 || !is_numeric($nuevoCurso['Curso']['id'])) {
                $this->Session->setFlash('No definio la sección.', 'default', array('class' => 'alert alert-danger'));
                $this->redirect($this->referer());
            }
            $cursoIdAnterior = $cursoInscripcion['Curso']['id'];
            $cursoIdNew = $nuevoCurso['Curso']['id'];
            // Sí los id de los cursos son diferentes, se trata de un cambio de sección.
            if ($cursoIdNew != $cursoIdAnterior) {
                // Actualiza los valores de matrícula y vacantes tanto de la sección origen como de la sección destino.
                // Comienza por el curso anterior...
                $matricula = $this->Inscripcion->CursosInscripcion->find('count', array(
                    'fields'=>array(
                        'CursosInscripcion.*',
                        'Inscripcion.*'
                    ),
                    //'contain'=> false,
                    'conditions'=>array(
                        'CursosInscripcion.curso_id'=>$cursoIdAnterior,
                        'Inscripcion.ciclo_id'=>$cicloActual['id'],
                )));
                $matriculaActual = $matricula - 1;
                $this->Curso->id = $cursoIdAnterior;
                $this->Curso->saveField("matricula", $matriculaActual);
                $plazasArray = $this->Curso->findById($cursoIdAnterior, 'plazas');
                $plazasString = $plazasArray['Curso']['plazas'];
                $vacantesActual = $plazasString - $matriculaActual;
                $this->Curso->saveField("vacantes", $vacantesActual);
                // Continúa por el curso actual...
                $matricula = $this->Inscripcion->CursosInscripcion->find('count', array(
                    'fields'=>array(
                        'CursosInscripcion.*',
                        'Inscripcion.*'
                    ),
                    //'contain'=> false,
                    'conditions'=>array(
                        'CursosInscripcion.curso_id'=>$cursoIdNew,
                        'Inscripcion.ciclo_id'=>$cicloActual['id'],
                )));
                $matriculaActual = $matricula + 1;
                $this->Curso->id=$cursoIdNew;
                $this->Curso->saveField("matricula", $matriculaActual);
                $plazasArray = $this->Curso->findById($cursoIdNew, 'plazas');
                $plazasString = $plazasArray['Curso']['plazas'];
                $vacantesActual = $plazasString - $matriculaActual;
                $this->Curso->saveField("vacantes", $vacantesActual);
            }
            /* FIN: PASE INTERNO (ENTRE CURSOS DE UNA MISMA INSTITUCIÓN) */
            /* INICIO: BAJA DE UN ALUMNO (DE UN CURSO DE UNA INSTITUCIÓN)
            *  Sí cambia el estado de inscripción a BAJA.
            *  Actualiza valores de matrícula y vacantes del curso origen.
            */
            $estadoInscripcionActual = $this->request->data['Inscripcion']['estado_inscripcion'];
            // Sí el estado de inscripción paso a BAJA.
            if (($estadoInscripcionAnterior != $estadoInscripcionActual) || ($estadoInscripcionActual == 'BAJA')) {
                // Actualiza los valores de matrícula y vacantes de la sección origen.
                $matricula = $this->Inscripcion->CursosInscripcion->find('count', array(
                    'fields'=>array(
                        'CursosInscripcion.*',
                        'Inscripcion.*'
                    ),
                    //'contain'=> false,
                    'conditions'=>array(
                        'CursosInscripcion.curso_id'=>$cursoIdAnterior,
                        'Inscripcion.ciclo_id'=>$cicloActual['id'],
                )));
                $matriculaActual = $matricula - 1;
                $this->Curso->id = $cursoIdAnterior;
                $this->Curso->saveField("matricula", $matriculaActual);
                $plazasArray = $this->Curso->findById($cursoIdAnterior, 'plazas');
                $plazasString = $plazasArray['Curso']['plazas'];
                $vacantesActual = $plazasString - $matriculaActual;
                $this->Curso->saveField("vacantes", $vacantesActual);
            }
            /* FIN: BAJA DE UN ALUMNO (DE UN CURSO DE UNA INSTITUCIÓN) */
            // Quito estos campos de la modificacion, este dato no se modifica
            unset($this->request->data['Inscripcion']['alumno_id']);
            unset($this->request->data['Inscripcion']['ciclo_id']);
            $this->request->data['Inscripcion']['legajo_nro'] = $cursoInscripcion['Inscripcion']['legajo_nro'];
            $this->request->data['Inscripcion']['id'] = $id;
            $this->request->data['Inscripcion']['usuario_id'] = $this->Auth->user('id');
            $this->Inscripcion->set($this->request->data);
            // ACA INTENTA HACER UN INSERT., CUANDO DEBERIA HACER UN UPDATE
            if ($this->Inscripcion->save()) {
                $this->Session->setFlash('La inscripcion ha sido grabada.', 'default', array('class' => 'alert alert-success'));
				$inserted_id = $this->Inscripcion->id;
				$this->redirect(array('action' => 'view', $inserted_id));
			} else {
                //debug( $this->Inscripcion->invalidFields() );
                //die;
				$this->Session->setFlash('La inscripcion no fue grabada. Intente nuevamente.', 'default', array('class' => 'alert alert-danger'));
			}
		}
        // End submit de formulario
        $this->set(compact('cursoInscripcion','alumno', 'personaId', 'estadoInscripcionAnteriorArray'));
    }

    public function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash('Id no valida para inscripcion.', 'default', array('class' => 'alert alert-warning'));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Inscripcion->delete($id)) {
			$this->Session->setFlash('La Inscripcion ha sido borrada.', 'default', array('class' => 'alert alert-success'));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash('La Inscripcion no fue borrada. Intentelo nuevamente.', 'default', array('class' => 'alert alert-danger'));
		$this->redirect(array('action' => 'index'));
	}

	//Métodos privados
	private function __lists(){
	    $this->loadModel('User');
        $this->User->recursive = 0;
        $this->User->Behaviors->load('Containable');
        // Carga en el combobox el Ciclo actual y uno posterior sí lo hubiera.        
        $this->loadModel('Ciclo');
        $this->Ciclo->recursive = 0;
        $this->Ciclo->Behaviors->load('Containable');
        $cicloIdActual = $this->getActualCicloId();
        $cicloIdActualArray = $this->Ciclo->findById($cicloIdActual, 'id');
        $cicloIdActualString = $cicloIdActualArray['Ciclo']['id'];
        $cicloIdUltimo = $this->getLastCicloId();
        $cicloIdUltimoArray = $this->Ciclo->findById($cicloIdUltimo, 'id');
        $cicloIdUltimoString = $cicloIdUltimoArray['Ciclo']['id'];
        $ciclos = $this->getTwoLastCicloNombres($cicloIdActualString, $cicloIdUltimoString);
        $this->Inscripcion->Centro->recursive = 0;
        $centros = $this->Inscripcion->Centro->find('list');
		/* Sí es "superadmin" ve combobox con todos los cursos, 
        *  Sino sí es usuario de Inicial y Primaria, ve los propios de ambos niveles,
        *  Sino sí es usuario de otro nivel ve los correspondiente.
        */
		$userCentroId = $this->getUserCentroId();
        $nivelCentro = $this->Inscripcion->Centro->find('list', array('fields'=>array('nivel_servicio'), 'contain'=>false, 'conditions'=>array('id'=>$userCentroId)));
        $userRol = $this->Auth->user('role');
		$this->Inscripcion->Curso->recursive = 0;
        if ($userRol == 'superadmin') {
			$cursos = $this->Inscripcion->Curso->find('list', array('fields'=>array('id','nombre_completo_curso'), 'contain'=>false));
		} else if (($userRol === 'usuario') && ($nivelCentro === 'Común - Inicial - Primario')) {
            $nivelCentroId = $this->Inscripcion->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>array('Común - Inicial', 'Común - Primario'))));
            $cursos = $this->Inscripcion->Curso->find('list', array('fields'=>array('id','nombre_completo_curso'), 'contain'=>false, 'conditions'=>array('centro_id'=>$nivelCentroId, 'status'=> '1')));
        } else if ($userRol === 'usuario') {
            $nivelCentroId = $this->Inscripcion->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>$nivelCentro)));
            $cursos = $this->Inscripcion->Curso->find('list', array('fields'=>array('nombre_completo_curso'), 'contain'=>false, 'conditions'=>array('centro_id'=>$nivelCentroId, 'status' => '1')));
        } else if ($userRol == 'admin') {
			$userCentroId = $this->getUserCentroId();
			$cursos = $this->Inscripcion->Curso->find('list', array('fields'=>array('id','nombre_completo_curso'), 'contain'=>false, 'conditions'=>array('centro_id'=>$userCentroId, 'status' => '1')));
		}
		/* Sí es "superadmin" o "usuario" ve combobox con todos los alumnos.
    	*  Sino ve los propios del centro. (INICIO) */
		$userCentroId = $this->getUserCentroId();
		$userRole = $this->Auth->user('role');
		$this->loadModel('Alumno');
        $this->Alumno->recursive = 0;
        $this->Alumno->Behaviors->load('Containable');
        if ($this->Auth->user('role') === 'admin') {
	       	$personaId = $this->Alumno->find('list', array('fields'=>array('persona_id'), 'contain'=>false, 'conditions'=>array('centro_id'=>$userCentroId)));
		} else if ($userRole === 'usuario') {
            $this->loadModel('Centro');
            $this->Centro->recursive = 0;
            $this->Alumno->Behaviors->load('Containable');
            $nivelCentro = $this->Centro->find('list', array('fields'=>array('nivel_servicio'), 'contain'=>false, 'conditions'=>array('id'=>$userCentroId)));
            $nivelCentroId = $this->Centro->find('list', array('fields'=>array('id'), 'contain'=>false, 'conditions'=>array('nivel_servicio'=>$nivelCentro)));
            $personaId = $this->Inscripcion->find('list', array('fields'=>array('alumno_id'), 'contain'=>false, 'conditions'=>array('centro_id'=>$nivelCentroId)));
        } else {
			//Sí es superadmin
			$personaId = $this->Alumno->find('list', array('fields'=>array('persona_id'), 'contain'=>false));
		}
		/* FIN */
        $this->set(compact('ciclos', 'centros', 'cursos', 'materias', 'empleados', 'cicloIdActual','cicloIdUltimo'));
	}

	private function __getCodigo($ciclo, $personaDocString){
		$legajo = $personaDocString."-".$ciclo;
		return $legajo;
    }

    private function __getCodigoPase($ciclo, $personaDocString, $paseNro){
        $legajo = $personaDocString."-".$ciclo."-"."PASE"."_".$paseNro;
        return $legajo;
    }

    private function consumeApiFindInscripcion($inscripcioId) {
        try
        {
            $hostApi = getenv('HOSTAPI');
            $httpSocket = new HttpSocket();
            $request = array('header' => array('Content-Type' => 'application/json'));
            // Datos de la ultima inscripcion de la persona
            $data = [];
            $response = $httpSocket->get("http://$hostApi/api/inscripcion/find/id/$inscripcioId", $data, $request);
            $response = $response->body;
            $apiResponse = json_decode($response,true);
            return $apiResponse;
        } catch(Exception $ex)
        {
            return ['error'=>$ex->getMessage()];
        }
    }

}
?>