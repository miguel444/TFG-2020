<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\local_league_management\Controller\MyModuleController;
use Drupal\file\Entity\File;

class AddCompeticionForm extends FormBase {

  // Array que almacena los nombres de las competiciones ya existentes
  protected $competicion_names;

  // Nodo de la competición
  protected $competicion_node;

  ###################################################################################################################
  ####################################################################################################################

  // Función de devuelve el identificador del formulario
    public function getFormId() {
        return 'my_module_addcompeticionform';
      }

  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state,$id=NULL) {




// Se define el campo para el nombre de la competición y sus atributos
    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('<b>Nombre de la competición :</b>'),
        '#required' => TRUE,



    );

    // Se define el campo para la descripción de la competición y sus atributos
    $form['descripcion'] = array(
      '#type' => 'text_format',
      '#title' => t('<b>Descripción :</b>'),
      '#validated' => TRUE,


    );

    // Se define el campo para el estado de la competición y sus atributos
    $form['estado'] = array(
      '#type' => 'select',
      '#title' => t('<b>Estado de la competición :</b>'),
      '#options' => array('Activa', 'No activa'),
      '#validated' => TRUE,
      '#required' => TRUE,

    );

    // Se define el campo para el curso de la competición y sus atributos
    $form['año'] = array(
      '#type' => 'number',
      '#title' => t('<b>Año académico (Ejemplo 2011 -> Curso 2011/2012) :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#size' => 4,
      '#prefix' => '<div id="año">',
      '#suffix' => '</div>',


    );

    // Se define el campo para el reglamento de la competición y sus atributos
    $form['reglamento'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('<b>Reglamento (Archivos PDF)</b>'),
      '#prefix' => '<div class="reglamento">',
      '#suffix' => '</div>',
      '#upload_location' => 'public://rules',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
      ],

    );

    // Se define el campo para la imagen de la competición y sus atributos
    $form['imagen'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('<b>Imagen de presentación de la competición en el menú principal</b>'),
      '#upload_location' => 'public://images',
      '#upload_validators' => [
        'file_validate_extensions' => array('gif png jpg jpeg'),
      ],

    );






// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
      ];

    $this->competicion_node = $id;

    // Si se está editando la competición
    if(!is_null($id)){


      // Se carga la competición a editar y se aplican los valores del nodo a los campos del formulario como valores por defecto.
      $query = Drupal::entityQuery('node')
        ->condition('type', 'competicion')
        ->condition('nid',$id)
        ->execute();

      $this->competicion_node = Node::load(current($query));


      $form['nombre']['#default_value'] = $this->competicion_node->get('title')->value;
      $form['#title'] = $this->t('<ul class="list-inline text-center"><li><b> Editar competición - ' . $this->competicion_node->get('title')->value .'</b></li></ul>');
      $form['estado']['#default_value'] = array_search($this->competicion_node->get('field_estado')->value,array('Activa', 'No activa'));

      $form['descripcion']['#default_value'] = $this->competicion_node->get('body')->value;
      $form['año']['#default_value'] = $this->competicion_node->get('field_ano_academico')->value;
      if(!is_null($this->competicion_node->get('field_reglamento')->target_id)){

      $form['reglamento']['#default_value'] = array($this->competicion_node->get('field_reglamento')->target_id);

    }

      if(!is_null($this->competicion_node->get('field_image')->target_id)){


        $form['imagen']['#default_value'] = array($this->competicion_node->get('field_image')->target_id);

      }
    }

    // Si se añade una nueva competición
    else

    $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir competición </b></li></ul>');

  // Se adjunta las bibliotecas definidas en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';
    $form['#attached']['library'][] = 'local_league_management/bootstrap';

    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state) {

      // Se extrae el nombre de la competición
    $nombre_competicion = str_replace(" ", "-",strtolower($form_state->getValue('nombre')));


    // Se cargan todas las competiciones del sistema y se guardan sus nombres
    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('field_ano_academico',$form_state->getValue('año'))
      ->execute();

    if (!empty($query)) {
      foreach ($query as $competicion) {
        $this->competicion_names[] = Node::load($competicion)->get('field_alias')->value;}}


    if(!empty($this->competicion_names)){

      // Se comprueba si existe otra competición con el mismo nombre en el curso indicado
        if (in_array($nombre_competicion,$this->competicion_names)  and ( is_null($this->competicion_node) or  (!is_null($this->competicion_node) and ($nombre_competicion != $this->competicion_node->get('field_alias')->value)))) {

            $option_competicion = &$form['nombre'];
            $form_state->setError($option_competicion, $this->t("Ya existe una competición con ese nombre en ese año académico"));
    }


    }

    // Se comprueba que el año académico no sea negativo
    if($form_state->getValue('año') < 0){
      $option_competicion = &$form['año'];
      $form_state->setError($option_competicion, $this->t("El año académico no puede ser negativo"));


    }







  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state) {

      // Se extrae el fichero introducido
    $form_file = $form_state->getValue('reglamento', 0);

  // Se existe un reglamento anterior, se elimina y se añade el nuevo
    if( !is_null($this->competicion_node) and $this->competicion_node->get('field_reglamento')->target_id != $form_file[0] ) {
      if (isset($form_file[0]) && !empty($form_file[0])) {

        $file = File::load($form_file[0]);
        $file->setPermanent();
        $file->save();
      }

      file_delete( $this->competicion_node->get('field_reglamento')->target_id);

    }

    $image_file = $form_state->getValue('imagen', 0);



// Se existe una imagen anterior, se elimina y se añade el nuevo
    if( !is_null($this->competicion_node) and $this->competicion_node->get('field_image')->target_id != $image_file[0] ) {
      if (isset($image_file[0]) && !empty($image_file[0])) {

        $file = File::load($image_file[0]);
        $file->setPermanent();
        $file->save();
      }


      file_delete( $this->competicion_node->get('field_image')->target_id);

    }


    // Se cargan los datos introducidos por el usuario
    $anio = $form_state->getValue('año');

    $competicion_form = $form_state->getValue('nombre');

    $body = $form_state->getValue('descripcion');



    $estado_value = $form_state->getValue('estado');
    $estado = $form['estado']['#options'][$estado_value];






    // Si se está editando una competición , se modifica sus valores con los datos introducidos por el usuario
    if(!is_null($this->competicion_node)) {
      drupal_set_message($this->t('<b>Competición editada:</b> @competicion',
        ['@competicion' => $competicion_form,
        ])
      );

      if($estado == 'Activa' and $this->competicion_node->get('field_estado')->value == 'No activa')
        $this->competicion_node->set('status', TRUE);
      elseif ($estado == 'No activa' and $this->competicion_node->get('field_estado')->value == 'Activa')
        $this->competicion_node->set('status', FALSE);

      $this->competicion_node->set('title', $competicion_form);
      $this->competicion_node->set('field_ano_academico', $anio);
      $this->competicion_node->set('field_estado', $estado);
      $this->competicion_node->set('body', $body);
      $this->competicion_node->set('field_alias', str_replace(" ", "-",strtolower($competicion_form)));
      $this->competicion_node->set('field_reglamento', $form_file[0]);
      $this->competicion_node->set('field_image', $image_file[0]);
      $this->competicion_node->save();

      MyModuleController::update($this->competicion_node);
      //pathauto_entity_update($this->competicion_node);
      //\Drupal::service('pathauto.generator')->updateEntityAlias($this->competicion_node, 'update');

      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->competicion_node->get('nid')->value);

    }else{

      // Si se añade una nueva competición
      drupal_set_message($this->t('<b>Competición añadida:</b> @competicion',
        ['@competicion' => $competicion_form,
        ])
      );




      $alias = MyModuleController::create_node_competicion($form_state->getValue('nombre'),0,$body,$form_file[0],$anio,$form['estado']['#options'][$estado_value],$image_file[0]);

    }




    MyModuleController::my_goto($alias);
  }



}
