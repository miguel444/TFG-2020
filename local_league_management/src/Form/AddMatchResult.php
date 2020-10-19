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



class AddMatchResult extends FormBase
{

  // Diccionario utilizado para asignar deportes a competiciones
  protected $dicc;

  // Nodo del deporte seleccionado en el formulario
  protected $tipo;


  // ID del nodo deporte al que se va a referir
  protected $node_entity;

  // Arrays de los jugadores del club local y visitante
  protected $jugadores_locales, $jugadores_visitantes;

  ###################################################################################################################
  ####################################################################################################################

// Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_addmatchresult';
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {




    if (!is_null($id)) {

      $node = node_load($id);

      $type = $node->getType();
      $this->tipo = $type;


      if ($type == 'partido') {


          // Se carga el nodo del partido
          $query = Drupal::entityQuery('node')
            ->condition('type', 'partido')
            ->condition('nid', $id)
            ->execute();

          $this->node_entity = Node::load(current($query));

          // Se carga el usuario actual y sus roles
          $current_user = \Drupal::currentUser();
          $roles = $current_user->getRoles();

          // Si el usuario es árbitro
          if(in_array('arbitro',$roles)){

            // Se comprueba que esté asignado como árbitro del partido
            if($current_user->id() != $this->node_entity->get('field_arbitro')->target_id){

              \Drupal::messenger()->addMessage(t("No se puede rellenar el acta de un partido en el que no esté asignado como árbitro"), 'error');

              $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_deporte->entity->get('nid')->value);

              MyModuleController::my_goto($alias);
            }

            // Se comprueba que el partido no se haya jugado ya
            if($this->node_entity->get('field_estado_partido')->value != 'Por disputar'){


              \Drupal::messenger()->addMessage(t("No se puede rellenar el acta de un partido ya disputado o aplazado"), 'error');

              $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_deporte->entity->get('nid')->value);

              MyModuleController::my_goto($alias);
            }
      }


          // Se obtienen la competición y deporte del partido
          $competicion_seleccionada = array($this->node_entity->field_deporte->entity->field_competicion->entity->get('title')->value);
          $deporte_seleccionado = array($this->node_entity->field_deporte->entity->get('title')->value);

          $query = Drupal::entityQuery('node')
            ->condition('type', 'jugador')
            ->condition('field_club', $this->node_entity->field_equipo_local->entity->get('nid')->value)
            ->condition('field_expulsado',0)
            ->execute();

          // Se guardan los nodos de los jugadores del club local
          foreach ($query as $local){
            $this->jugadores_locales[] = Node::Load($local);
          }

          $query = Drupal::entityQuery('node')
            ->condition('type', 'jugador')
            ->condition('field_club', $this->node_entity->field_equipo_visitante->entity->get('nid')->value)
            ->condition('field_expulsado',0)
            ->execute();

        // Se guardan los nodos de los jugadores del club visitante
          foreach ($query as $visitante){
            $this->jugadores_visitantes[] = Node::Load($visitante);
          }


        }


      else {
        \Drupal::messenger()->addMessage(t("No se puede realizar la acción indicada"), 'error');
        MyModuleController::my_goto('/competiciones');
      }
    }else {
      \Drupal::messenger()->addMessage(t("No se puede realizar la acción indicada"), 'error');
      MyModuleController::my_goto('/competiciones');}

// Se define el campo para la competición del partido y sus atributos
    $form['competicion'] = [
      '#type' => 'textfield',
      '#title' => '<b>Competición :</b>',
      '#required' => TRUE,
      '#validated' => TRUE,
      '#default_value' => $competicion_seleccionada,
      '#attributes' => array('readonly' => 'readonly'),


    ];

// Se define el campo para el deporte del partido y sus atributos
    $form['deporte'] = [
      '#type' => 'textfield',
      '#title' => t('<b>Deporte :</b>'),
      '#default_value' => $deporte_seleccionado,
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#attributes' => array('readonly' => 'readonly'),


    ];

// Se define el campo para la fase del partido y sus atributos
    $form['fase'] = array(
      '#type' => 'textfield',
      '#title' => t('<b> Fase :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#default_value' => $this->node_entity->field_fase->value,
      '#attributes' => array('readonly' => 'readonly'),

    );

    $form['equipo_local'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('<b>'. $this->node_entity->field_equipo_local->entity->getTitle() .'</b>'),
      '#group' => 'information',
      '#open' => TRUE,

    );

// Se define el campo para el resultado del equipo local del partido y sus atributos
    $form['equipo_local']['resultado_local'] = array(
      '#type' => 'number',
      '#title' => t('<b> Resultado :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#default_value' => 0,


    );

    // Se define el encabezado de la tabla de jugadores
    $header = array('¿Ha jugado?' ,'Nombre', 'Expulsado','Goles/Puntos');


    // Se define la tabla de jugadores locales del partido
    $form['equipo_local']['jugadores_local'] = array(
      '#type' => 'table',
      '#header' => $header,
    );

    // Se define para cada jugador local los campos: si ha jugado, si ha sido expulsado y los goles/puntos anotados
    foreach ($this->jugadores_locales as $key=>$local) {
      $form['equipo_local']['jugadores_local'][$key]['jugado'] = array(
        '#type' => 'checkbox',
      );

      $form['equipo_local']['jugadores_local'][$key]['nombre'] = array(
        '#type' => 'textfield',
        '#default_value' => $local->get('title')->value . ' '.$local->get('field_apellidos')->value ,
        '#attributes' => array('readonly' => 'readonly'),
      );



      $form['equipo_local']['jugadores_local'][$key]['roja'] = array(
        '#type' => 'checkbox',
      );

      $form['equipo_local']['jugadores_local'][$key]['goles'] = array(
        '#type' => 'number',
        '#default_value' => 0,
      );
    }



    $form['equipo_visitante'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('<b>' . $this->node_entity->field_equipo_visitante->entity->getTitle().'</b>'),
      '#group' => 'information',
      '#open' => TRUE,
    );

// Se define el campo para el resultado del equipo visitante del partido y sus atributos
    $form['equipo_visitante']['resultado_visitante'] = array(
      '#type' => 'number',
      '#title' => t('<b> Resultado :</b>'),
      '#validated' => TRUE,
      '#required' => TRUE,
      '#default_value' => 0,


    );

    // Se define la tabla de jugadores visitantes
    $form['equipo_visitante']['jugadores_visitante'] = array(
      '#type' => 'table',
      '#header' => $header,

    );

    // Se define para cada jugador visitante los campos: si ha jugado, si ha sido expulsado y los goles/puntos anotados
    foreach ($this->jugadores_visitantes as $key=>$visitante) {
      $form['equipo_visitante']['jugadores_visitante'][$key]['jugado'] = array(
        '#type' => 'checkbox',
      );

      $form['equipo_visitante']['jugadores_visitante'][$key]['nombre'] = array(
        '#type' => 'textfield',
        '#default_value' => $visitante->get('title')->value.' '.$visitante->get('field_apellidos')->value,
        '#attributes' => array('readonly' => 'readonly'),
      );




      $form['equipo_visitante']['jugadores_visitante'][$key]['roja'] = array(
        '#type' => 'checkbox',
      );

      $form['equipo_visitante']['jugadores_visitante'][$key]['goles'] = array(
        '#type' => 'number',
        '#default_value' => 0,
      );
    }


// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' => '<div class=" text-center">',
      '#suffix' => '</div>',
    ];


    if (!is_null($id)) {
      if ($this->tipo == 'partido') {
        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Rellenar acta  <br>' . $this->node_entity->field_equipo_local->entity->get('title')->value . ' - ' . $this->node_entity->field_equipo_visitante->entity->get('title')->value . '</b></li></ul>');


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
    // Se obtiene el resultado local introducido
    $resultado_local = $form_state->getValue('resultado_local');

    // Se comprueba que no se haya introducido un valor negativo
    if($resultado_local < 0){
      $option_club = &$form['equipo_local']['resultado_local'];
      $form_state->setError($option_club, $this->t("Un club no pueden meter goles/puntos negativos en el partido"));


    }
    // Se obtiene el resultado visitante introducido
    $resultado_visitante = $form_state->getValue('resultado_visitante');

    // Se comprueba que no se haya introducido un valor negativo
    if($resultado_visitante < 0){
      $option_club = &$form['equipo_visitante']['resultado_visitante'];
      $form_state->setError($option_club, $this->t("Un club no pueden meter goles/puntos negativos en el partido"));


    }

    // Se obtiene la tabla de jugadores locales
    $jugadores_locales = $form_state->getValue('jugadores_local');
    $total_goles_locales = 0;

    // Para cada jugador local
    foreach ($jugadores_locales as $key=>$jugador){

      // Se comprueba que ha jugado el partido
      if($jugador['jugado'] ==1) {

        // Se comprueba que no se introduzca un valor negativo
        if($jugador['goles'] < 0){
          $option_club = &$form['equipo_local']['jugadores_local'];
          $form_state->setError($option_club, $this->t("Los jugadores no pueden meter goles/puntos negativos en el partido"));

        }
        // Se suman todos los goles/puntos de los jugadores que han jugado
        $total_goles_locales += $jugador['goles'];

      }

    }

    // Se comprueba que los jugadores no han anotado mas goles/puntos que el resultado indicado para el club
    if($total_goles_locales > $resultado_local){
      $option_club = &$form['equipo_local']['resultado_local'];
      $form_state->setError($option_club, $this->t("Los jugadores no pueden meter más goles/puntos que goles/puntos se han metido en el partido"));

    }
    // Se obtiene la tabla de jugadores visitantes
    $jugadores_visitantes= $form_state->getValue('jugadores_visitante');
    $total_goles_visitantes = 0;

    foreach ($jugadores_visitantes as $key=>$jugador){


    // Se comprueba que ha jugado el partido
      if($jugador['jugado'] ==1) {

        // Se comprueba que no se introduzca un valor negativo
        if($jugador['goles'] < 0){
          $option_club = &$form['equipo_visitante']['jugadores_visitante'];
          $form_state->setError($option_club, $this->t("Los jugadores no pueden meter goles/puntos negativos en el partido"));

        }

        // Se suman todos los goles/puntos de los jugadores que han jugado
        $total_goles_visitantes += $jugador['goles'];
      }
    }

    // Se comprueba que los jugadores no han anotado mas goles/puntos que el resultado indicado para el club
    if($total_goles_visitantes > $resultado_visitante){
      $option_club = &$form['equipo_visitante']['resultado_visitante'];
      $form_state->setError($option_club, $this->t("Los jugadores no pueden meter más goles/puntos que goles/puntos se han metido en el partido"));

    }







  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {




  if (!is_null($this->node_entity)) {

      // Se obtienen los valores introducidos por el usuario
      $resultado_local = $form_state->getValue('resultado_local');

      $resultado_visitante = $form_state->getValue('resultado_visitante');


      $fase = $form_state->getValue('fase');

    // Se obtiene la tabla de jugadores locales
    $jugadores_locales = $form_state->getValue('jugadores_local');

    // Para cada jugador local
    foreach ($jugadores_locales as $key=>$jugador){

      // Si ha jugado
      if($jugador['jugado'] ==1) {

        // Se modifican las estadísticas del jugador
        $this->jugadores_locales[$key]->set('field_partidos_disputados', ($this->jugadores_locales[$key]->get('field_partidos_disputados')->value) + 1);
        $this->jugadores_locales[$key]->set('field_goles', ($this->jugadores_locales[$key]->get('field_goles')->value) + $jugador['goles']);
        if($jugador['roja'] == 1)
        $this->jugadores_locales[$key]->set('field_expulsado', 1);

      }
      // Se guardan los cambios
      $this->jugadores_locales[$key]->save();
    }

    // Se obtiene la tabla de jugadores visitantes
    $jugadores_visitantes= $form_state->getValue('jugadores_visitante');

    // Para cada jugador visitante
    foreach ($jugadores_visitantes as $key=>$jugador){

      // Si ha jugado
      if($jugador['jugado'] ==1) {


        // Se modifican las estadísticas del jugador
        $this->jugadores_visitantes[$key]->set('field_partidos_disputados', ($this->jugadores_visitantes[$key]->get('field_partidos_disputados')->value) + 1);
        $this->jugadores_visitantes[$key]->set('field_goles', ($this->jugadores_visitantes[$key]->get('field_goles')->value) + $jugador['goles']);
        if($jugador['roja'] ==1)
          $this->jugadores_visitantes[$key]->set('field_expulsado', 1);

      }

      // Se guardan los cambios
      $this->jugadores_visitantes[$key]->save();
    }



      // Si el partido es de fase de grupos
      if ($fase == 'Fase de grupos') {

        // Se calculan las estadísticas de cada club en función del resultado
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

        // Se guardan los cambios
        $this->node_entity->field_equipo_local->entity->save();
        $this->node_entity->field_equipo_visitante->entity->save();

      }





        drupal_set_message($this->t('<b>Acta guardada con éxito: </b> @nombre ',
          ['@nombre' => $this->node_entity->field_equipo_local->entity->get('title')->value . ' - ' . $this->node_entity->field_equipo_visitante->entity->get('title')->value,
          ])
        );


      // Se actualiza los datos del partido y se guardan los cambios.
        $this->node_entity->set('field_estado_partido', 'Jugado');
        $this->node_entity->set('field_resultado_local', $resultado_local);
        $this->node_entity->set('field_resultado_visitante', $resultado_visitante);


        $this->node_entity->save();



      }

    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_equipo_local->entity->field_deporte->entity->get('nid')->value);
    MyModuleController::my_goto($alias);


  }



}


