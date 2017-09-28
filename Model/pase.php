<?php
class Pase extends AppModel {
	var $name = 'Pase';
	public $displayField = 'id';
	//public $actsAs = array('Containable');

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'Alumno' => array(
			'className' => 'Alumno',
			'foreignKey' => 'alumno_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Ciclo' => array(
			'className' => 'Ciclo',
			'foreignKey' => 'ciclo_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	//Validaciones
                var $validate = array(
                    'alumno_id' => array(
                        'required' => array(
					    'rule' => 'notBlank',
                        'required' => 'create',
						'message' => 'Indicar un alumno.'
						),
						'numeric' => array(
	 					'rule' => 'naturalNumber',
	 					'message' => 'Indicar un alumno válido.'
	 					)
                    ),
                    'centro_id_origen' => array(
                        'required' => array(
					    'rule' => 'notBlank',
                        'required' => 'create',
						'message' => 'Indicar un centro.'
						),
						'numeric' => array(
	 					'rule' => 'naturalNumber',
	 					'message' => 'Indicar un centro válido.'
	 					)
                    ),
                    'centro_id_destino' => array(
                        'required' => array(
					    'rule' => 'notBlank',
                        'required' => 'create',
						'message' => 'Indicar un centro.'
						),
						'numeric' => array(
	 					'rule' => 'naturalNumber',
	 					'message' => 'Indicar un centro válido.'
	 					)
                    ),
				    'tipo' => array(
                        'required' => array(
						'rule' => 'notBlank',
                        'required' => 'create',
						'message' => 'Indicar un tipo de pase.'
						 ),
						'alphaBet' => array(
		 				'rule' => '/^[ áÁéÉíÍóÓúÚa-zA-ZñÑ]{5,}$/i',
		 				)
                    ),
                    'motivo' => array(
                        'minLength' => array(
                        'rule' => array('minLength', 3),
                        'allowEmpty' => true,
                        'message' => 'Indicar una opción.'
						),
						'alphaBet' => array(
 						'rule' => '/^[ áÁéÉíÍóÓúÚa-zA-ZñÑ]{3,}$/i',
 						)
                    ),
                    'nota_tutor' => array(
                           'boolean' => array(
                           'rule' => array('boolean'),
                           'allowEmpty' => true,
					       'message' => 'Indicar una opción'
				           )
                    ),
                    'estado_pase' => array(
						'valid' => array(
						'rule' => array('inList', array('CONFIRMADO','NO CONFIRMADO','BAJA')),
						'message' => 'Indicar una opción',
						'allowEmpty' => false
							)
					),
                   'estado_documentacion' => array(
						'valid' => array(
						'rule' => array('inList', array('PENDIENTE','COMPLETA')),
						'message' => 'Indicar una opción',
					  	'allowEmpty' => false
							)
					)				   
         );
}
?>
