<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\local_league_management\Controller\MyModuleController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;



class CopyForm extends FormBase
{



  // Diccionario utilizado para asignar deportes a competiciones
  protected $dicc;

  // Nodo del deporte seleccionado en el formulario
  protected $node_entity;

  // Tipo de la entidad
  protected $tipo;

  ###################################################################################################################
  ####################################################################################################################

// Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_addclubform';
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state,$id=NULL)
  {



    $this->dicc = array();

    // Se cargan todas las competiciones
    $lista_competiciones = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->execute();

    if (!empty($lista_competiciones)) {

      $check = [];
      foreach ($lista_competiciones as $competicion) {

        // Se obtiene el nombre de cada competición
        $competicion_name = Node::load($competicion)->get('title')->value;
        $nombres_competiciones_deporte[] = $competicion_name;


        // Se cargan todos los deportes de cada competición
        $lista_deportes_competicion = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('field_competicion', Node::load($competicion)->get('nid')->value)
          ->execute();

        if (!empty($lista_deportes_competicion)) {
          // Se obtiene el nombre de cada competición
          $nombres_competiciones_club[] = $competicion_name;
          $deporte_names = array();
          // Se obtiene el nombre de cada deporte
          foreach ($lista_deportes_competicion as $deporte) {
            $check[] = $deporte;
            $deporte_names[] = Node::load($deporte)->get('title')->value;


          }

          // Se asocian los deportes a la competición correspondiente.
          $this->dicc[$competicion_name] = $deporte_names;



        }



      }

      if (empty($check)) {
        \Drupal::messenger()->addMessage(t("No existen deportes activos donde inscribir el club"), 'error');
        MyModuleController::my_goto('<front>');
      }


    } else {

      \Drupal::messenger()->addMessage(t("No existen competiciones activas donde inscribir el club"), 'error');
      MyModuleController::my_goto('<front>');

    }







if(!is_null($id)) {



  $node = node_load($id);

  $type = $node->getType($node);



  $this->tipo = $type;

  switch ($type) {

    // Si se copia competición
    case "competicion":
      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Copiar competición </b></li><li><span class="glyphicon glyphicon-copy"></span></li></ul>');

      break;

      // Si se copia deporte
    case "deporte":

      // Se carga el deporte a copiar
      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('nid', $id)
        ->execute();

      $this->node_entity = Node::load(current($query));

      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Copiar deporte - ' . $this->node_entity->get('title')->value . '</b></li></ul>');

      // Se define el campo para la competición del deporte y sus atributos
      $form['competicion'] = [
        '#type' => 'select',
        '#title' => 'Competición :',
        '#required' => TRUE,
        '#validated' => TRUE,
        '#options' => $nombres_competiciones_deporte,
        '#ajax' => [
          'callback' => '::myAjaxCallback',
          'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering
          'event' => 'change',
          'wrapper' => 'edit-output',
          'method' => 'replace',

        ]

      ];

      // Se define la opción de copiar los clubes de este deporte
      $form['opcion_clubes'] = array(
        '#title' => t('Copiar todos los clubes de este deporte'),
        '#type' => 'checkbox',
        '#validated' => TRUE,

      );

      // Se define la opción de copiar los jugadores de cada club
      $form['opcion'] = array(
        '#title' => t('Copiar todos los jugadores de cada club'),
        '#type' => 'checkbox',
        '#validated' => TRUE,

      );



      break;

      // Si se copia un club
    case "club":

      // Se carga el club a copiar
      $query = Drupal::entityQuery('node')
        ->condition('type', 'club')
        ->condition('nid', $id)
        ->execute();

      $this->node_entity = Node::load(current($query));

      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Copiar club - ' . $this->node_entity->get('title')->value . '</b></li></ul>');

      // Se define el campo para la competición del club y sus atributos
      $form['competicion'] = [
        '#type' => 'select',
        '#title' => 'Competición :',
        '#required' => TRUE,
        '#validated' => TRUE,
        '#options' => $nombres_competiciones_club,
        '#ajax' => [
          'callback' => '::myAjaxCallback',
          'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering
          'event' => 'change',
          'wrapper' => 'edit-output',
          'method' => 'replace',

        ]

      ];

      // Se define el campo para el deporte del club y sus atributos
      $form['deporte'] = [
        '#type' => 'select',
        '#title' => t('Deporte :'),
        '#options' => $this->dicc,
        '#prefix' => '<div id="edit-output">',
        '#suffix' => '</div>',
        '#validated' => TRUE,



      ];

      // Se define el campo para el grupo del club y sus atributos
      $form['grupo'] = array(
        '#type' => 'number',
        '#title' => t('Grupo :'),


      );

      // Se define el campo para el estado del club y sus atributos
      $form['estado'] = array(
        '#type' => 'select',
        '#title' => t('Estado :'),
        '#options' =>array('Activado', 'Desactivado','Pendiente'),


      );


      // Se define la opción de copiar los jugadores del club
      $form['opcion'] = array(
        '#title' => t('Copiar todos los jugadores de este club'),
        '#type' => 'checkbox',
        '#validated' => TRUE,

      );


      break;


  }

}

// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
    ];

// Se adjunta una biblioteca definida en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';



    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que realiza una llamada Ajax, de forma que una seleccionado una competición se actualice el campo de
  // deporte en función de ésta.
  public function myAjaxCallback(array &$form, FormStateInterface $form_state)
  {


    $selectedValue = $form_state->getValue('competicion');



    $selectedText = $form['competicion']['#options'][$selectedValue];


    if(array_key_exists($selectedText,$this->dicc))
      $form['deporte']['#options'] = $this->dicc[$selectedText];
    else
      $form['deporte']['#options'] = ['No hay deportes activos en esta competición'];



    return $form['deporte'];
  }

  ###################################################################################################################
  ####################################################################################################################
  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {


    // Se obtiene la competición seleccionada y se carga
    $competicion_clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$competicion_clave];

    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('title', $competicion_name)
      ->execute();

    $competicion_node = Node::load(current($query));



    // Si se copia un club
    if(!is_null($this->node_entity) and $this->tipo=='club') {

      // Se obtiene el deporte seleccionado y se carga
      $deporte_clave = $form_state->getValue('deporte');
      $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];

      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('title', $deporte_name)
        ->condition('field_competicion', ($competicion_node)->get('nid')->value)
        ->execute();

      $sport_node = Node::load(current($query));


      // Se consultan todos los clubes del deporte
      $query = Drupal::entityQuery('node')
        ->condition('type', 'club')
        ->condition('field_deporte', ($sport_node)->get('nid')->value)
        ->execute();


      if (!empty($query)) {
        // Se obtiene el nombre de cada club
        foreach ($query as $club) {
          $club_names[] = Node::load($club)->get('field_alias')->value;
        }

        // Se comprueba si ya existe el club a copiar en ese deporte
        if (in_array($this->node_entity->get('field_alias')->value, $club_names)) {

          $option_club = &$form['deporte'];
          $form_state->setError($option_club, $this->t("Ese club ya está inscrito en ese deporte"));
        }
      }

    }

    // Si se copia un deporte
    elseif (!is_null($this->node_entity) and $this->tipo=='deporte'){


      // Se obtienen todos los deportes de la competición seleccionada
      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('field_competicion', ($competicion_node)->get('nid')->value)
        ->execute();

      $deporte_names = array();

      if (!empty($query)) {
        // Se obtiene el nombre de cada deporte
        foreach ($query as $deporte) {
          $deporte_names[] = Node::load($deporte)->get('field_alias')->value;
        }
      }

    // Se comprueba si ya existe el deporte a copiar en esa competición
      if (in_array($this->node_entity->get('field_alias')->value, $deporte_names)) {


        $option_club = &$form['competicion'];
        $form_state->setError($option_club, $this->t("Ese deporte ya está inscrito en esa competición"));
      }

      // Se comprueba que se copien los jugadores si se ha seleccionad copiar los clubes
      if($form_state->getValue('opcion_clubes') == 0 and $form_state->getValue('opcion') == 1) {
        $option_club = &$form['opcion_clubes'];
        $form_state->setError($option_club, $this->t("Si desea copiar los jugadores es necesario también copiar los clubes"));

      }

    }


  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    // Se obtiene la competición seleccionada y se carga
    $competicion_clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$competicion_clave];

    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('title', $competicion_name)
      ->execute();

    $competicion_node = Node::load(current($query));

    $alias = '/competiciones';

    // Si se copia un club
    if(!is_null($this->node_entity) and $this->tipo=='club') {

      // Se obtiene el deporte seleccionado y se carga
      $deporte_clave = $form_state->getValue('deporte');
      $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];

      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('title', $deporte_name)
        ->condition('field_competicion', ($competicion_node)->get('nid')->value)
        ->execute();

      $sport_node = Node::load(current($query));


      // Se obtiene el estado del club
      $estado_value = $form_state->getValue('estado');
      $estado = $form['estado']['#options'][$estado_value];



      // Si no se selecciona copiar jugadores
      if($form_state->getValue('opcion') == 0){
        $alias = MyModuleController::create_node_club($this->node_entity->get('title')->value, $sport_node->get('nid')->value, 0, $competicion_name,$form_state->getValue('grupo'),$competicion_node->get('nid')->value,$estado,NULL);


      }

      // En caso contrario
      else{
        $alias = MyModuleController::create_node_club($this->node_entity->get('title')->value, $sport_node->get('nid')->value,$this->node_entity->get('field_numero_de_jugadores')->value, $competicion_name,$form_state->getValue('grupo'),$competicion_node->get('nid')->value,$estado,$this->node_entity->get('field_capitan')->target_id);

        // Se carga el club creado en este momento
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('title',$this->node_entity->get('title')->value)
          ->condition('field_deporte',($sport_node)->get('nid')->value)
          ->execute();

        $club_creado = Node::Load(current($query));

        // Se cargan todos los jugadores del club que se quería copiar
        $query = Drupal::entityQuery('node')
          ->condition('type', 'jugador')
          ->condition('field_club',($this->node_entity)->get('nid')->value)
          ->execute();


        // Se copia cada jugador en el nuevo club copiado
        foreach ($query as $jugador){
          $node_jugador = Node::Load($jugador);

          MyModuleController::create_node_jugador($node_jugador->get('title')->value, $node_jugador->get('field_apellidos')->value, $club_creado->get('nid')->value, $node_jugador->get('field_correo_electronico')->value, $node_jugador->get('field_dni')->value, $node_jugador->get('field_ficha_deportiva')->value, $node_jugador->get('field_capitan_jugador')->value);

        }


      }


      // Se actualiza el número de clubes del deporte en donde se ha copiado el club
      $query = Drupal::entityQuery('node')
        ->condition('type', 'club')
        ->condition('field_deporte',($sport_node)->get('nid')->value)
        ->condition('status',TRUE)
        ->execute();

      $num_deportes = count($query);
      $sport_node->set('field_numero_de_equipos', $num_deportes);
      $sport_node->save();


      drupal_set_message($this->t('<b>Club copiado:</b> @nombre en @deporte - @competicion',
        ['@nombre' => $this->node_entity->get('title')->value,
          '@competicion' => $competicion_name,
          '@deporte' => $deporte_name,
        ])
      );

    }

    // Si se copia el deporte
    elseif (!is_null($this->node_entity) and $this->tipo=='deporte') {

      // Si no se selecciona copiar clubes
      if($form_state->getValue('opcion_clubes')==0){
        $alias = MyModuleController::create_node_deporte($this->node_entity->get('title')->value, 0,$this->node_entity->get('field_numero_maximo_de_equipos')->value, $competicion_node->get('nid')->value, $competicion_node->get('title')->value, $this->node_entity->get('field_fecha_de_inicio')->value, $this->node_entity->get('field_fecha_de_fin')->value,  $this->node_entity->get('field_fecha_de_inicio_inscripcio')->value,  $this->node_entity->get('field_fecha_de_fin_inscripcion')->value);


      }

      // En caso contrario
      else{
        $alias = MyModuleController::create_node_deporte($this->node_entity->get('title')->value, $this->node_entity->get('field_numero_de_equipos')->value,$this->node_entity->get('field_numero_maximo_de_equipos')->value, $competicion_node->get('nid')->value, $competicion_node->get('title')->value, $this->node_entity->get('field_fecha_de_inicio')->value, $this->node_entity->get('field_fecha_de_fin')->value,  $this->node_entity->get('field_fecha_de_inicio_inscripcio')->value,  $this->node_entity->get('field_fecha_de_fin_inscripcion')->value);

        // Se carga el deporte creado en este momento
        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('title',$this->node_entity->get('title')->value)
          ->condition('field_competicion',($competicion_node)->get('nid')->value)
          ->execute();

        $deporte_creado = Node::Load(current($query));

        // Se cargan todos los clubes del deporte que se quería copiar
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('field_deporte',($this->node_entity)->get('nid')->value)
          ->execute();

        // Se copian todos los clubes en el nuevo deporte copiado
        foreach ($query as $club){
          $node_club = Node::Load($club);

          // Si no se selecciona copiar jugadores
          if($form_state->getValue('opcion')==0)
          MyModuleController::create_node_club($node_club->get('title')->value, $deporte_creado->get('nid')->value, 0, $competicion_name,$node_club->get('field_grupo')->value,$competicion_node->get('nid')->value,$node_club->get('field_estado_club')->value,NULL);

          // En caso contrario
          else{
            MyModuleController::create_node_club($node_club->get('title')->value, $deporte_creado->get('nid')->value,$node_club->get('field_numero_de_jugadores')->value, $competicion_name,$node_club->get('field_grupo')->value,$competicion_node->get('nid')->value,$node_club->get('field_estado_club')->value,$node_club->get('field_capitan')->target_id);

            // Se carga el club creado en este momento
            $query = Drupal::entityQuery('node')
              ->condition('type', 'club')
              ->condition('title',$node_club->get('title')->value)
              ->condition('field_deporte',$deporte_creado->get('nid')->value)
              ->execute();

            $club_creado = Node::Load(current($query));

            // Se cargan todos los jugadores de dicho club
            $query = Drupal::entityQuery('node')
              ->condition('type', 'jugador')
              ->condition('field_club',$node_club->get('nid')->value)
              ->execute();


            // Se copia cada jugador en el nuevo club copiado
            foreach ($query as $jugador){
              $node_jugador = Node::Load($jugador);

              MyModuleController::create_node_jugador($node_jugador->get('title')->value, $node_jugador->get('field_apellidos')->value, $club_creado->get('nid')->value, $node_jugador->get('field_correo_electronico')->value, $node_jugador->get('field_dni')->value, $node_jugador->get('field_ficha_deportiva')->value, $node_jugador->get('field_capitan_jugador')->value);

            }



          }
        }



      }


      // Se actualiza el número de deportes de la competición en la que se ha copiado el deporte
      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('field_competicion',($competicion_node)->get('nid')->value)
        ->execute();

      $num_deportes = count($query);
      $competicion_node->set('field_numero_de_deportes', $num_deportes);
      $competicion_node->save();

      drupal_set_message($this->t('<b>Deporte copiado:</b> @nombre en  @competicion',
        ['@nombre' => $this->node_entity->get('title')->value,
          '@competicion' => $competicion_name,

        ])
      );
    }


MyModuleController::my_goto($alias);
/*
    $num_equipos = $this->sport_node->get('field_numero_de_equipos')->value;
    $this->sport_node->set('field_numero_de_equipos', $num_equipos + 1);
    $id_sport = $this->sport_node->get('nid')->value;
    $this->sport_node->save();*/










  }


}
