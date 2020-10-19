<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\local_league_management\Controller\MyModuleController;
use Drupal\file\Entity\File;

class AddSportForm extends FormBase {

  // Nodo de la competición
  protected $competicion_node;

  // Nodo del deporte
  protected $sport_node;

  ###################################################################################################################
  ####################################################################################################################

// Función de devuelve el identificador del formulario
    public function getFormId() {
        return 'my_module_addsportform';
      }

  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state,$id=NULL) {


    // Se consultan todas las competiciones disponibles
    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $competicion) {
           $competicion_names[] = Node::load($competicion)->get('title')->value;}}
    else{

      drupal_set_message("NO EXISTEN COMPETICIONES ACTIVAS DONDE INSCRIBIR EL DEPORTE",'error');
      MyModuleController::my_goto('<front>');
    }



    if(!is_null($id)) {

      $node = node_load($id);

      $type = $node->getType();

      // Si se crea un nuevo deporte
      if ($type == 'competicion') {

        // Se consulta y guarda la competición correspondiente
        $query = Drupal::entityQuery('node')
          ->condition('type', 'competicion')
          ->condition('nid', $id)
          ->execute();

        $lista[] = Node::load(current($query))->get('title')->value;
      }

      // Si se edita el deporte
      else{

        // Se consulta y carga el deporte
        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('nid', $id)
          ->execute();

        $this->sport_node = Node::load(current($query));
        $lista = $competicion_names;
      }




    }
    else $lista = $competicion_names;




// Se define el campo para el nombre del deporte y sus atributos
    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('<b>Nombre del deporte :</b>'),
        '#required' => TRUE,

    );


    // Se define el campo para la competición del deporte y sus atributos
    $form['competicion'] = array(
      '#type' => 'select',
      '#title' => t('<b>Competición :</b>'),
      '#validated' => TRUE,
      '#options' => $lista,

  );

// Se define el campo para el número máximo de clubes del deporte y sus atributos
    $form['equipos_maximos'] = array(
      '#type' => 'number',
      '#title' => t('<b>Número máximo de equipos participantes. Si indica 0 podrán participar todos los equipos que se desee</b>'),
      '#default_value' => 0,
      '#required' => TRUE,

    );

    $form['fecha_competicion'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('<b>Competición</b>'),
      '#group' => 'information',
      '#open' => TRUE,
    );

    // Se define el campo para el inicio del deporte y sus atributos
    $form['fecha_competicion']['fecha_inicio'] = array(
      '#type' => 'date',
      '#title' => t('<b>Fecha de inicio de la competición :</b>'),
      '#prefix' => '<div id="fecha_competicion">',
    );


// Se define el campo para el fin del deporte y sus atributos
    $form['fecha_competicion']['fecha_fin'] = array(
      '#type' => 'date',
      '#title' => t('<b>Fecha de fin de la competición :</b>'),
      '#suffix' => '</div>',
    );

    $form['fecha_inscripcion'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('<b>Inscripción</b>'),
      '#group' => 'information',
      '#open' => TRUE,
    );

    // Se define el campo para el inicio de las incripciones del deporte y sus atributos
    $form['fecha_inscripcion']['fecha_inicio_inscripcion'] = array(
      '#type' => 'date',
      '#title' => t('<b>Fecha de inicio de inscripción :</b>'),
      '#prefix' => '<div id="fecha_inscripcion">',
    );

    // Se define el campo para el fin de las inscripciones del deporte y sus atributos
    $form['fecha_inscripcion']['fecha_fin_inscripcion'] = array(
      '#type' => 'date',
      '#title' => t('<b>Fecha de fin de inscripción: </b>'),
      '#suffix' => '</div>',
    );

// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
      ];

    // Se adjunta las bibliotecas definidas en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';
    $form['#attached']['library'][] = 'local_league_management/bootstrap';


    // Si se edita el deporte, se aplican los valores del nodo a los campos del formulario como valores por defecto.
    if(!is_null($this->sport_node)){


      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><span class="glyphicon glyphicon-edit"></span></li><li><b> Editar deporte - ' . $this->sport_node->get('title')->value .'</b></li></ul>');
      $form['nombre']['#default_value'] = $this->sport_node->get('title')->value;
      $form['competicion']['#default_value'] = array_search($this->sport_node->field_competicion->entity->getTitle(),$lista);


      $form['equipos_maximos']['#default_value'] = $this->sport_node->get('field_numero_maximo_de_equipos')->value;

      $form['fecha_competicion']['fecha_inicio']['#default_value'] = $this->sport_node->get('field_fecha_de_inicio')->value;
      $form['fecha_competicion']['fecha_fin']['#default_value'] = $this->sport_node->get('field_fecha_de_fin')->value;
      $form['fecha_inscripcion']['fecha_inicio_inscripcion']['#default_value'] = $this->sport_node->get('field_fecha_de_inicio_inscripcio')->value;
      $form['fecha_inscripcion']['fecha_fin_inscripcion']['#default_value'] = $this->sport_node->get('field_fecha_de_fin_inscripcion')->value;


    }
    // Si se crea un nuevo deporte
    else

      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir nuevo deporte </b></li><li><span class="glyphicon glyphicon-plus-sign"></span></li></ul>');

    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state) {


    // Se obtiene la competición asociada y el nombre introducido para el deporte
    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];
    $nombre_deporte = str_replace(" ", "-",strtolower($form_state->getValue('nombre')));



    // Se carga la competición asociada
    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->condition('title',$competicion_form)
    ->execute();

    $this->competicion_node = Node::load(current($query));


    // Se consultan todos los deportes de la competición seleccionado
    $query = Drupal::entityQuery('node')
    ->condition('type', 'deporte')
    ->condition('field_competicion',($this->competicion_node)->get('nid')->value)
    ->execute();


    if (!empty($query)) {


        foreach ($query as $deporte) {
          $deporte_names[] = Node::load($deporte)->get('field_alias')->value;
          }

        //  Se comprueba que no exista un deporte en la competición con el mismo nombre que el introducido
        if (in_array($nombre_deporte,$deporte_names) and  !is_null($this->sport_node) and ($nombre_deporte != $this->sport_node->get('title')->value) or ((in_array($nombre_deporte,$deporte_names) and is_null($this->sport_node)))) {

          $option_deporte = &$form['nombre'];
          $form_state->setError($option_deporte, $this->t("Ese deporte ya está inscrito en esa competición"));
    }}

    // Se obtienen las fechas de la competición.
    $fecha_inicio = $form_state->getValue('fecha_inicio');
    $fecha_fin = $form_state->getValue('fecha_fin');

    // Se comprueba si la fecha de inicio es mayor que la fecha de fin
    if(MyModuleController::compare_date($fecha_inicio,$fecha_fin)){
      $fecha = &$form['fecha_competicion']['fecha_fin'];
      $form_state->setError($fecha, $this->t("La fecha de finalización de la competición no puede ser inferior a la fecha de inicio"));

    }

    // Se obtienen las fechas de inscripicíón del deporte.
    $fecha_inicio_inscripcion = $form_state->getValue('fecha_inicio_inscripcion');
    $fecha_fin_incripcion = $form_state->getValue('fecha_fin_inscripcion');

    // Se comprueba si la fecha de inicio de inscripcion es mayor que la fecha de fin de inscripcion
    if(MyModuleController::compare_date($fecha_inicio_inscripcion,$fecha_fin_incripcion)){
      $fecha = &$form['fecha_inscripcion']['fecha_fin_inscripcion'];
      $form_state->setError($fecha, $this->t("La fecha de fin de inscripción no puede ser inferior a la fecha de inicio de inscripción"));

    }

    // Se comprueba si la fecha de inicio de inscripción es mayor que la fecha de inicio
    if(MyModuleController::compare_date($fecha_inicio_inscripcion,$fecha_inicio)){
      $fecha = &$form['fecha_competicion']['fecha_inicio'];
      $form_state->setError($fecha, $this->t("La fecha de inicio de la competición no puede ser inferior a la fecha de inicio de inscripción"));

    }

    // Se comprueba si la fecha de fin de inspcripcion es mayor que la fecha de inicio
    if(MyModuleController::compare_date($fecha_fin_incripcion,$fecha_inicio)){
      $fecha = &$form['fecha_inscripcion']['fecha_fin_inscripcion'];
      $form_state->setError($fecha, $this->t("La fecha de fin de la inscripción no puede ser superior a la fecha de inicio de la competición"));

    }



  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state) {


      // Se obtiene los valores introducidos por el usuario
    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];

    $fecha_inicio = $form_state->getValue('fecha_inicio');
    $fecha_fin = $form_state->getValue('fecha_fin');

    $fecha_inicio_inscripcion = $form_state->getValue('fecha_inicio_inscripcion');
    $fecha_fin_incripcion = $form_state->getValue('fecha_fin_inscripcion');


    // Si se edita el deporte
    if(!is_null($this->sport_node)) {

      drupal_set_message($this->t('<b>Deporte editado</b>: @nombre ',
        [ '@nombre' => $form_state->getValue('nombre'),
        ])
      );

      // Se almacenan las modificaciones realizadas
      $this->sport_node->set('title',  $form_state->getValue('nombre'));
      $this->sport_node->set('field_competicion', ($this->competicion_node)->get('nid')->value);
      $this->sport_node->set('field_numero_maximo_de_equipos', $form_state->getValue('equipos_maximos'));
      $this->sport_node->set('field_fecha_de_inicio', $fecha_inicio);
      $this->sport_node->set('field_fecha_de_inicio_inscripcio', $fecha_inicio_inscripcion);
      $this->sport_node->set('field_fecha_de_fin', $fecha_fin);
      $this->sport_node->set('field_fecha_de_fin_inscripcion', $fecha_fin_incripcion);
      $this->sport_node->set('field_alias', str_replace(" ", "-",strtolower($form_state->getValue('nombre'))));

      $this->sport_node->save();

      MyModuleController::update($this->sport_node);
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->sport_node->get('nid')->value);
    }

    // Si se crea un deporte
    else{
      drupal_set_message($this->t('<b>Deporte inscrito</b>: @nombre en @competicion',
        [ '@nombre' => $form_state->getValue('nombre'),
          '@competicion' => $competicion_form,
        ])
      );



      $alias = MyModuleController::create_node_deporte($form_state->getValue('nombre'),0,$form_state->getValue('equipos_maximos'),($this->competicion_node)->get('nid')->value,$competicion_form,$fecha_inicio,$fecha_fin,$fecha_inicio_inscripcion,$fecha_fin_incripcion);

    }


    // Se actualiza el número de deportes de la competición asociada.
    $query = Drupal::entityQuery('node')
      ->condition('type', 'deporte')
      ->condition('field_competicion',($this->competicion_node)->get('nid')->value)
      ->execute();

    $num_deportes = count($query);
    $this->competicion_node->set('field_numero_de_deportes', $num_deportes);
    $this->competicion_node->save();





    MyModuleController::my_goto($alias);
  }

/*
  public function create_node_club($nombre,$num_jugadores,$id_competicion){

    $node = Node::create(array(
        'type' => 'club',
        'title' => $nombre,
        'field_numero_de_jugadores' => $num_jugadores,
        'field_competicion' => $id_competicion,
    ));

    $node->save();
}
*/



}
