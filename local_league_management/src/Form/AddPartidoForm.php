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
use Drupal\user\Entity\User;
use Drupal\Core\Datetime\DrupalDateTime;



class AddPartidoForm extends FormBase
{

  // Diccionario utilizado para asignar clubes a grupos del deporte
  protected $dicc;

  // Nodo del deporte seleccionado en el formulario
  protected $tipo;


  // ID del nodo deporte al que se va a referir
  protected $node_entity;

  ###################################################################################################################
  ####################################################################################################################
// Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_addpartidoform';
  }
  ###################################################################################################################
  ####################################################################################################################
  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {


    $this->dicc = array();

    // Se cargan todos los árbitros disponibles
    $query = Drupal::entityQuery('node')
      ->condition('type', 'arbitro')
      ->execute();

    if (!empty($query)) {
      foreach ($query as $arbitro) {

        $arbitros[Node::load($arbitro)->get('field_arbitro_user')->target_id] = Node::load($arbitro)->get('title')->value.' '.Node::load($arbitro)->get('field_apellidos')->value ;

      }

    } else {
      $arbitros[] = 'No se ha encontrado ningún árbitro en la base de datos';
    }




    if (!is_null($id)) {

      $node = node_load($id);

      $type = $node->getType($node);
      $this->tipo = $type;


      // Si se crea un partido
      if ($type == 'deporte') {

        // Se consultan los clubes del deporte y se almacenan por grupo
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('field_deporte', $id)
          ->execute();

        foreach ($query as $club) {
          $this->dicc['Grupo '.Node::load($club)->get('field_grupo')->value][ Node::load($club)->get('title')->value] = Node::load($club)->get('title')->value;
        }

        // Se consulta y carga el deporte
        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('nid', $id)
          ->execute();

        $deporte_seleccionado[] = Node::load(current($query))->get('title')->value;

        $this->node_entity = Node::load(current($query));

        // Se consulta y carga la competición asociada
        $query = Drupal::entityQuery('node')
          ->condition('type', 'competicion')
          ->condition('nid', Node::load(current($query))->get('field_competicion')->target_id)
          ->execute();

        $competicion_seleccionada[] = Node::load(current($query))->get('title')->value;


        // Si se edita un partido
      } else {


        // Se consulta y carga el partido
        $query = Drupal::entityQuery('node')
          ->condition('type', 'partido')
          ->condition('nid', $id)
          ->execute();

        $this->node_entity = Node::load(current($query));

        // Se consultan todos los clubes del deporte y se almancenan por grupo
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('field_deporte', $this->node_entity->field_equipo_local->entity->field_deporte->entity->get('nid')->value)
          ->execute();

        foreach ($query as $club) {
          $this->dicc['Grupo '. Node::load($club)->get('field_grupo')->value][Node::load($club)->get('title')->value] = Node::load($club)->get('title')->value;
        }
        $competicion_seleccionada = array($this->node_entity->field_deporte->entity->field_competicion->entity->get('title')->value);
        $deporte_seleccionado = array($this->node_entity->field_deporte->entity->get('title')->value);


      }


    } else {
      \Drupal::messenger()->addMessage(t("No se puede realizar la acción indicada"), 'error');
      MyModuleController::my_goto('/competiciones');
    }



// Se define el campo para la competición del partido y sus atributos
    $form['competicion'] = [
      '#type' => 'select',
      '#title' => '<b>Competición :</b>',
      '#validated' => TRUE,
      '#options' => $competicion_seleccionada,


    ];

// Se define el campo para el deporte del partido y sus atributos
    $form['deporte'] = [
      '#type' => 'select',
      '#title' => t('<b>Deporte :</b>'),
      '#options' => $deporte_seleccionado,
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#validated' => TRUE,


    ];


    // Se define el campo para la fase del partido y sus atributos
    $form['fase'] = array(
      '#type' => 'select',
      '#title' => t('<b> Fase :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#options' => array('Fase de grupos', 'Dieciseisavos de final', 'Octavos de final', 'Cuartos de final', 'Semifinal', 'Final'),


    );


    $form['equipo_local'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('<b>Equipo local</b>'),
      '#group' => 'information',
      '#open' => TRUE,

    );

    // Se define el campo para selecciona el club local del partido y sus atributos
    $form['equipo_local']['seleccion_local'] = array(
      '#type' => 'select',
      '#title' => t('<b> Equipo :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#options' => $this->dicc,


    );

// Se define el campo para la resultado del club local del partido y sus atributos
      $form['equipo_local']['resultado_local'] = array(
        '#type' => 'number',
        '#title' => t('<b> Resultado :</b>'),
        '#validated' => TRUE,
        '#required' => TRUE,
        '#default_value' => 0,


      );



    $form['equipo_visitante'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('<b>Equipo visitante</b>'),
      '#group' => 'information',
      '#open' => TRUE,
    );

    // Se define el campo para selecciona el club visitante del partido y sus atributos
    $form['equipo_visitante']['seleccion_visitante'] = array(
      '#type' => 'select',
      '#title' => t('<b> Equipo :</b>'),
      '#validated' => TRUE,
      '#options' => $this->dicc,


    );

// Se define el campo para la resultado del club visitante del partido y sus atributos
      $form['equipo_visitante']['resultado_visitante'] = array(
        '#type' => 'number',
        '#title' => t('<b> Resultado :</b>'),
        '#validated' => TRUE,
        '#default_value' => 0,


      );


// Se define el campo para seleccionar el árbitro  del partido y sus atributos
    $form['arbitro'] = array(
      '#type' => 'select',
      '#title' => t('<b> Árbitro :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#options' => $arbitros,


    );

    // Se define el campo para el estado  del partido y sus atributos
    $form['estado'] = array(
      '#type' => 'select',
      '#title' => t('<b>Estado del partido :</b>'),
      '#options' => array('Por disputar', 'Aplazado', 'Jugado'),
      '#validated' => TRUE,


    );

    // Se define el campo para el lugar  del partido y sus atributos
    $form['lugar'] = array(
      '#type' => 'textfield',
      '#title' => t('<b> Lugar :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,


    );

    // Se define el campo para la fecha  del partido y sus atributos
    $form['fecha'] = array(
      '#type' => 'datetime',
      '#title' => t('<b> Fecha del partido :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,


    );

    // Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' => '<div class=" text-center">',
      '#suffix' => '</div>',
    ];


    if (!is_null($id)) {

      // Si se edita el partido, se aplican los valores del nodo a los campos del formulario como valores por defecto.
      if ($this->tipo == 'partido') {

        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Editar partido <br>' . $this->node_entity->field_equipo_local->entity->get('title')->value . ' - ' . $this->node_entity->field_equipo_visitante->entity->get('title')->value . '</b></li></ul>');
        $form['fecha']['#default_value'] = new DrupalDateTime(str_replace('T',' ',$this->node_entity->field_fecha_partido->value), 'Europe/Madrid');
        $form['fase']['#default_value'] = array_search($this->node_entity->field_fase->value,array('Fase de grupos', 'Dieciseisavos de final', 'Octavos de final', 'Cuartos de final', 'Semifinal', 'Final'));
        $form['lugar']['#default_value'] = $this->node_entity->field_lugar->value;
        $form['estado']['#default_value'] = array_search($this->node_entity->field_estado_partido->value,array('Por disputar', 'Aplazado', 'Jugado'));
          $form['equipo_local']['seleccion_local']['#default_value'] = array_search($this->node_entity->field_equipo_local->entity->getTitle(),$this->dicc['Grupo ' . $this->node_entity->field_equipo_local->entity->get('field_grupo')->value]);
        $form['equipo_local']['resultado_local']['#default_value'] = $this->node_entity->field_resultado_local->value;
        $form['equipo_visitante']['seleccion_visitante']['#default_value'] = array_search($this->node_entity->field_equipo_visitante->entity->getTitle(),$this->dicc['Grupo ' . $this->node_entity->field_equipo_visitante->entity->get('field_grupo')->value]);
        $form['equipo_visitante']['resultado_visitante']['#default_value'] = $this->node_entity->field_resultado_visitante->value;
        $form['arbitro']['#default_value'] = array_search($this->node_entity->field_arbitro->entity->get('uid')->value,$arbitros);


      } else {

        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir partido </b></li></ul>');


      }
    }

    // Se adjunta las bibliotecas definidas en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';
    $form['#attached']['library'][] = 'local_league_management/bootstrap';



    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {


    // Se carga el deporte correspondiente
    if($this->tipo=='deporte')
    $sport_node = $this->node_entity;
    else
      $sport_node = $this->node_entity->field_deporte->entity;


    // Se obtiene la fase introducida por el usuario
    $value = $form_state->getValue('fase');
    $fase = $form['fase']['#options'][$value];

    // Se obtiene el club local seleccionado por el usuario
    $equipo_local = $form_state->getValue('seleccion_local');





  // Se consulta y se carga el club local
    $query = Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('title', $equipo_local)
      ->condition('field_deporte', $sport_node->get('nid')->value)
      ->execute();


    $equipo_local = Node::load(current($query));


    // Se obtiene el club visitante seleccionado por el usuario
    $equipo_visitante = $form_state->getValue('seleccion_visitante');


    // Se consulta y se carga el club visitante
    $query = Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('title', $equipo_visitante)
      ->condition('field_deporte', $sport_node->get('nid')->value)
      ->execute();

    $equipo_visitante = Node::load(current($query));



    // Se comprueba si los clubes son del mismo grupo, una vez seleccionada fase de grupos
    if ($fase == 'Fase de grupos' and $equipo_local->field_grupo->value != $equipo_visitante->field_grupo->value) {
      $option_club = &$form['equipo_local']['seleccion_local'];
      $form_state->setError($option_club, $this->t("Si se selecciona fase de grupos, se debe seleccionar dos equipos del mismo grupo"));
    }


  // Se comprueba que ambos clubes no sean el mismo
    if($equipo_local->get('title')->value == $equipo_visitante->get('title')->value){
      $option_club = &$form['equipo_local']['seleccion_local'];
      $form_state->setError($option_club, $this->t("No se puede seleccionar el mismo equipo como local y como visitante"));

    }

    // Se comprueba que se ha asignado un árbitro
    if($form_state->getValue('arbitro')== 0){

      $option_club = &$form['arbitro'];
      $form_state->setError($option_club, $this->t("Es necesario asignar un árbitro al partido"));

    }





  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {


    // Se carga el deporte
    if($this->tipo=='deporte')
      $sport_node = $this->node_entity;
    else
      $sport_node = $this->node_entity->field_deporte->entity;




    if (!is_null($this->node_entity)) {

      // Se obtiene y se carga el club local seleccionado.
      $equipo_local = $form_state->getValue('seleccion_local');

      $query = Drupal::entityQuery('node')
        ->condition('type', 'club')
        ->condition('title', $equipo_local)
        ->condition('field_deporte', $sport_node->get('nid')->value)
        ->execute();

      $equipo_local = Node::load(current($query));


      // Se obtiene y se carga el club visitante seleccionado.
      $equipo_visitante = $form_state->getValue('seleccion_visitante');

      $query = Drupal::entityQuery('node')
        ->condition('type', 'club')
        ->condition('title', $equipo_visitante)
        ->condition('field_deporte', $sport_node->get('nid')->value)
        ->execute();

      $equipo_visitante = Node::load(current($query));

      // Se obtiene todos los valores introducidos por el usuario
      $resultado_visitante = $form_state->getValue('resultado_visitante');
      $resultado_local = $form_state->getValue('resultado_local');

      $fecha = $form_state->getValue('fecha');

      $fecha = $fecha['date'].'T'.$fecha['time'];


      $value = $form_state->getValue('fase');
      $fase = $form['fase']['#options'][$value];

      $lugar = $form_state->getValue('lugar');


      $value = $form_state->getValue('estado');
      $estado = $form['estado']['#options'][$value];

      $id_arbitro = $form_state->getValue('arbitro');



      if ($fase == 'Fase de grupos')
        $grupo = $equipo_local->field_grupo->value;
      else
        $grupo = NULL;


      // Se crea el partido
      if ($this->tipo == 'deporte') {

        drupal_set_message($this->t('<b>Partido creado: </b> @nombre ',
          ['@nombre' => $equipo_local->get('title')->value . ' - ' . $equipo_visitante->get('title')->value,
          ])
        );
        if($estado!='Jugado')
        $nid = MyModuleController::create_node_partido($equipo_local->get('nid')->value, NULL, $equipo_visitante->get('nid')->value, NULL, $sport_node->get('nid')->value, $estado, $fase, $fecha, $lugar, $grupo, $id_arbitro);
        else{
          $nid = MyModuleController::create_node_partido($equipo_local->get('nid')->value, $resultado_local, $equipo_visitante->get('nid')->value, $resultado_visitante, $sport_node->get('nid')->value, $estado, $fase, $fecha, $lugar, $grupo, $id_arbitro);

        }


        $query = Drupal::entityQuery('node')
          ->condition('nid', $nid)
          ->execute();

        $this->node_entity = User::load(current($query));



        // Se edita el partido
      } else {


        drupal_set_message($this->t('<b>Partido editado: </b> @nombre ',
          ['@nombre' => $equipo_local->get('title')->value . ' - ' . $equipo_visitante->get('title')->value,
          ])
        );




        // Se modifican los atributos del partido con los valores introducidos.
        $this->node_entity->set('field_equipo_local', $equipo_local->get('nid')->value);
        $this->node_entity->set('field_equipo_visitante', $equipo_visitante->get('nid')->value);
        $this->node_entity->set('field_arbitro', $id_arbitro);
        $this->node_entity->set('field_deporte', $sport_node->get('nid')->value);
        $this->node_entity->set('field_fase', $fase);
        $this->node_entity->set('field_lugar', $lugar);
        $this->node_entity->set('field_fecha_partido', $fecha);


        $this->node_entity->set('field_estado_partido', $estado);
        $this->node_entity->set('field_partido_grupo', $grupo);
        $this->node_entity->set('field_resultado_local', $resultado_local);
        $this->node_entity->set('field_resultado_visitante', $resultado_visitante);








        $this->node_entity->save();


      }

      // Si se selecciona el partido jugado de fase de grupos, se actualizan las estadísticas de los clubes participantes
      if ($fase == 'Fase de grupos' and $estado=='Jugado' and ($this->tipo=='deporte' or ($this->tipo=='partido' and $estado!=$this->node_entity->get('field_estadp_partido')->value))) {
        $this->node_entity->field_equipo_local->entity->set('field_puntos_goles_a_favor', ($this->node_entity->field_equipo_local->entity->get('field_puntos_goles_a_favor')->value)+$resultado_local);
        $this->node_entity->field_equipo_local->entity->set('field_puntos_goles_en_contra', ($this->node_entity->field_equipo_local->entity->get('field_puntos_goles_en_contra')->value)+$resultado_visitante);
        $this->node_entity->field_equipo_local->entity->set('field_diferencia_puntos_goles', ($this->node_entity->field_equipo_local->entity->get('field_diferencia_puntos_goles')->value)+($resultado_local - $resultado_visitante));

        $this->node_entity->field_equipo_visitante->entity->set('field_puntos_goles_a_favor', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos_goles_a_favor')->value)+$resultado_visitante);
        $this->node_entity->field_equipo_visitante->entity->set('field_puntos_goles_en_contra', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos_goles_en_contra')->value)+$resultado_local);
        $this->node_entity->field_equipo_visitante->entity->set('field_diferencia_puntos_goles', ($this->node_entity->field_equipo_visitante->entity->get('field_diferencia_puntos_goles')->value)+($resultado_visitante-$resultado_local));


        if($resultado_local > $resultado_visitante){
          $this->node_entity->field_equipo_local->entity->set('field_puntos', ($this->node_entity->field_equipo_local->entity->get('field_puntos')->value)+3);

        }
        elseif ($resultado_local == $resultado_visitante){
          $this->node_entity->field_equipo_local->entity->set('field_puntos', ($this->node_entity->field_equipo_local->entity->get('field_puntos')->value)+1);
          $this->node_entity->field_equipo_visitante->entity->set('field_puntos', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos')->value)+1);


        }
        else{
          $this->node_entity->field_equipo_visitante->entity->set('field_puntos', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos')->value)+3);

        }

        $this->node_entity->field_equipo_local->entity->save();
        $this->node_entity->field_equipo_visitante->entity->save();

      }



    }

  }

}


