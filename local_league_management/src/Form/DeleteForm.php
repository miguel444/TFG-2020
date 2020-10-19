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



class DeleteForm extends FormBase
{



  // Nodo del deporte seleccionado en el formulario
  protected $node_entity;

  // Tipo de entidad a eliminar
  protected $tipo;

  // Indica si la entidad a eliminar es un usuario
  protected $is_user;

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

    if(!is_null($id)) {


      // Se carga la entidad a eliminar
      $query = Drupal::entityQuery('node')
        ->condition('nid', $id)
        ->execute();

      $this->node_entity = Node::load(current($query));

      if(!is_null($this->node_entity)) {

        $this->tipo = $this->node_entity->get('type')->target_id;

        // Se obtiene el usuario actual y su roles
        $current_user = \Drupal::currentUser();
        $roles = $current_user->getRoles();

        // Si el usuario es becario no puede eliminar un club que no haya creado él mismo
        if (in_array('becario', $roles) and $this->tipo == 'club' and $this->node_entity->getOwner()->id() != $current_user->id()) {

          \Drupal::messenger()->addMessage(t("No se puede eliminar un club que nos ha sido creado por uno mismo."), 'error');
          $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_deporte->entity->get('nid')->value);

          MyModuleController::my_goto($alias);

        }

        // Si el usuario es becario no puede eliminar un club ya aprobado por el gestor
        elseif (in_array('becario', $roles) and $this->tipo == 'club' and $this->node_entity->get('field_estado_club') == 'Activado'){
          \Drupal::messenger()->addMessage(t("No se puede eliminar un club ya aprobado. Contacte con personal del centro."), 'error');
          $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->get('nid')->value);

          MyModuleController::my_goto($alias);
        }

        // Si el usuario es becario no puede eliminar un jugador de un club que no haya creado él
        if (in_array('becario', $roles) and $this->tipo=='jugador' and $this->node_entity->field_club->entity->getOwner()->id() != $current_user->id()){

          \Drupal::messenger()->addMessage(t("No se puede eliminar un jugador de un club que no ha sido creado por uno mismo"), 'error');

          $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_club->entity->get('nid')->value);

          MyModuleController::my_goto($alias);

        }

        // Si el usuario es capitán no puede eliminar un jugador de un club en el que no sea capitán
        if (in_array('capitan', $roles) and $this->tipo=='jugador' and $this->node_entity->field_club->entity->get('field_capitan')->target_id != $current_user->id()){

          \Drupal::messenger()->addMessage(t("No se puede eliminar un jugador de un club del que no se es capitán"), 'error');

          $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_club->entity->get('nid')->value);

          MyModuleController::my_goto($alias);

        }


        // Si es una competicio, deporte, club, jugador o arbitro, se muestra un mensaje informativo
        if (in_array($this->tipo, array('competicion', 'deporte', 'club','jugador','arbitro'))) {

          $form['opcion'] = array(
            '#type' => 'textfield',
            '#validated' => TRUE,
            '#attributes' => array('readonly' => 'readonly'),

          );

        }

        switch ($this->tipo) {
          case "competicion":
            $form['opcion']['#default_value'] = t('Se eliminarán todos los deportes, clubes, partidos, etc asociados a dicha competición.');
            $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar competición - ' . ($this->node_entity->get('title')->value) . '</b></li></ul>');

            break;
          case "deporte":
            $form['opcion']['#default_value'] = t('Se eliminarán todos los clubes, partidos,etc asociados a dicho deporte.');
            $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar deporte - ' . ($this->node_entity->get('title')->value) . '</b></li></ul>');

            break;
          case "club":
            $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar club - ' . ($this->node_entity->get('title')->value) . '</b></li></ul>');
            $form['opcion']['#default_value'] = t('Se eliminarán todos los partidos y jugadores asociados a dicho club. Además se eliminará la cuenta de usuario del capitán');
            break;
          case "partido":
            $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar partido - ' . ($this->node_entity->field_equipo_local->entity->get('title')->value) . ' - ' . $this->node_entity->field_equipo_visitante->entity->get('title')->value . '</b></li></ul>');
            break;
          case "jugador":
            $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar jugador  - ' . ($this->node_entity->get('title')->value) . '</b></li></ul>');
            $form['opcion']['#default_value'] = t('Si el jugador es el capitán, además se eliminará su cuenta de usuario');

            break;

          case "arbitro":
            $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar árbitro  - ' . ($this->node_entity->get('title')->value) . '</b></li></ul>');
            $form['opcion']['#default_value'] = t('Se eliminará tambíen su cuenta de usuario');

            break;


        }

      }else{
        // En caso de ser usuario, se carga el usuario
        $query = Drupal::entityQuery('user')
          ->condition('uid', $id)
          ->execute();

        $this->node_entity = User::load(current($query));

        $this->is_user=TRUE;

        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Eliminar  usuario - ' . ($this->node_entity->get('name')->value) . '</b></li></ul>');


      }

      // Se adjunta una biblioteca definida en el módulo para modificar el estilo del formulario
      $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';

      // Se define el botón de enviar formulario y sus propiedades
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Eliminar'),
        '#prefix' => '<div id="edit-submit">',
        '#suffix' => '</div>',
      ];

    }
    else{
      drupal_set_message("Acceso denegado",'error');

    }


    return $form;


  }

###################################################################################################################
  ####################################################################################################################
// Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

  }

###################################################################################################################
  ####################################################################################################################
  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    // Si no es un usuario
    if(!$this->is_user) {
      switch ($this->tipo) {
        // si es competición
        case "competicion":


          // Se cargan todas las entidades que hagan referencia a la competiciñón
          $query = Drupal::entityQuery('node')
            ->condition('field_competicion', $this->node_entity->get('nid')->value)
            ->execute();


          if (!empty($query)) {
            //Cada entidad se carga
            foreach ($query as $key => $entity) {


              $node = Node::load($entity);

              // Si es un deporte
              if ($node->getType() == 'deporte') {


                // Se obtienen todos los partidos del deporte y se eliminan
                $consulta = Drupal::entityQuery('node')
                  ->condition('type', 'partido')
                  ->condition('field_deporte', $node->get('nid')->value)
                  ->execute();


                if (!empty($consulta)) {

                  foreach ($consulta as $k => $entidad) {
                    $nodo = Node::load($entidad);
                    $nodo->delete();

                  }
                }


                // Si es un club
              } elseif ($node->getType() == 'club') {


                // Se comprueba si existe algún otro club con el mismo capitán
                $consulta_capitan = Drupal::entityQuery('node')
                  ->condition('field_capitan', $node->get('field_capitan')->target_id)
                  ->execute();

                // Si solo es capitán de este club, se elimina dicho usuario
                if(count($consulta_capitan)==1)
                user_delete($node->get('field_capitan')->target_id);

                // Se cargan todas las entidades que hagan referencia al club y se eliminan
                $consulta2 = Drupal::entityQuery('node')
                  ->condition('field_club', $node->get('nid')->value)
                  ->execute();

                if (!empty($consulta2)) {
                  foreach ($consulta2 as $ke => $entiti) {
                    $nodo = Node::load($entiti);
                    $nodo->delete();
                  }
                }


              }
            }
          }


          break;
          // Si se elimina un deporte
        case "deporte":

          // Se cargan todas las entidades que hagan referencia al deporte
          $query = Drupal::entityQuery('node')
            ->condition('field_deporte', $this->node_entity->get('nid')->value)
            ->execute();

          if (!empty($query)) {
            // Para cada entidad
            foreach ($query as $key => $entity) {
              $node = Node::load($entity);

              // Si es un club
              if ($node->getType() == 'club') {

                // Se comprueba si existe algún otro club con el mismo capitán
                $consulta_capitan = Drupal::entityQuery('node')
                  ->condition('field_capitan', $node->get('field_capitan')->target_id)
                  ->execute();

                // Si solo es capitán de este club, se elimina dicho usuario
                if(count($consulta_capitan)==1)
                  user_delete($node->get('field_capitan')->target_id);

                // Se cargan todas las entidades que hacen referencia el club y se eliminan
                $consulta = Drupal::entityQuery('node')
                  ->condition('field_club', $node->get('nid')->value)
                  ->execute();

                if (!empty($consulta)) {
                  foreach ($consulta as $key => $entity) {
                    $node = Node::load($entity);
                    $node->delete();
                  }
                }

              }
            }
          }


          break;
          // Si es un club
        case "club":

          // Se cargan los partidos en los que sea local y se eliminan
          $query = Drupal::entityQuery('node')
            ->condition('field_equipo_local', $this->node_entity->get('nid')->value)
            ->execute();


          if (!empty($query)) {
            foreach ($query as $key => $entity) {
              $node = Node::load($entity);
              $node->delete();
            }
          }


          // Se cargan los partidos en los que sea visitante y se eliminan

          $query = Drupal::entityQuery('node')
            ->condition('field_equipo_visitante', $this->node_entity->get('nid')->value)
            ->execute();

          if (!empty($query)) {
            foreach ($query as $key => $entity) {
              $node = Node::load($entity);
              $node->delete();
            }
          }


          // Se cargan todas las entidades a las que haga referencia el club y se eliminan
          $query = Drupal::entityQuery('node')
            ->condition('field_club', $this->node_entity->get('nid')->value)
            ->execute();



          // Se comprueba si existe algún otro club con el mismo capitán
          $consulta_capitan = Drupal::entityQuery('node')
            ->condition('field_capitan',$this->node_entity->get('field_capitan')->target_id)
            ->execute();

          // Si solo es capitán de este club, se elimina dicho usuario
          if(count($consulta_capitan)==1)
            user_delete($this->node_entity->get('field_capitan')->target_id);

          break;

          // Si es jugador
        case "jugador":
          // Si es el capitán
          if ($this->node_entity->get('field_capitan_jugador')->value == 'Si') {

            // Se comprueba si existe algún otro club con el mismo capitán
            $consulta_capitan = Drupal::entityQuery('node')
              ->condition('field_capitan',$this->node_entity->field_club->entity->get('field_capitan')->target_id)
              ->execute();

            // Si solo es capitán de este club, se elimina dicho usuario
            if(count($consulta_capitan)==1)
              user_delete($this->node_entity->field_club->entity->get('field_capitan')->target_id);

            // Se actualiza el capitán del club
            $this->node_entity->field_club->entity->set('field_capitan', NULL);
            $this->node_entity->field_club->entity->save();
          }
          $query = array();
          break;

          // Si es árbitro se elimina su cuenta de usuario
        case "arbitro":
          user_delete($this->node_entity->get('field_arbitro_user')->target_id);

      }


      // Se cargan y eliminan todas las entidades referenciadas
      if (!empty($query)) {
        foreach ($query as $key => $entity) {
          $node = Node::load($entity);
          $node->delete();
        }
      }




  // Se elimina la entidad actual
    $this->node_entity->delete();


    // si es una competición
    if ($this->tipo =='competicion') {
      $alias = '/competiciones';
      drupal_set_message($this->t('<b>Competición eliminada</b>: @nombre ',
        [ '@nombre' => $this->node_entity->get('title')->value,

        ])
      );

    }
    // Si es un deporte
    elseif ($this->tipo =='deporte') {
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_competicion->target_id);
      drupal_set_message($this->t('<b>Deporte eliminado</b>: @nombre de @competicion ',
        [ '@nombre' => $this->node_entity->get('title')->value,
          '@competicion' => $this->node_entity->field_competicion->entity->getTitle(),

        ])
      );

      // Se actualiza el número de deporte de la competición que tenía asociada
      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('field_competicion',($this->node_entity)->get('field_competicion')->target_id)
        ->execute();

      $num_deportes = count($query);
      $this->node_entity->field_competicion->entity->set('field_numero_de_deportes', $num_deportes);
      $this->node_entity->field_competicion->entity->save();
    }
    // Si es un club
      elseif ($this->tipo=='club') {
        $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_deporte->target_id);
        drupal_set_message($this->t('<b>Club eliminado</b>: @nombre de @deporte - @competicion ',
          [ '@nombre' => $this->node_entity->get('title')->value,
            '@deporte' => $this->node_entity->field_deporte->entity->getTitle(),
            '@competicion' => $this->node_entity->field_competicion->entity->getTitle(),

          ])
        );

        // Se actualiza el número de clubes del deporte que tenía asociado
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('status',TRUE)
          ->condition('field_deporte',($this->node_entity)->get('field_deporte')->target_id)
          ->execute();

        $num_equipos = count($query);
        $this->node_entity->field_deporte->entity->set('field_numero_de_equipos', $num_equipos);
        $this->node_entity->field_deporte->entity->save();
      }

    // Si es un jugador
    elseif ($this->tipo=='jugador'){
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_club->target_id);
      drupal_set_message($this->t('<b>Jugador eliminado</b>: @nombre de @club ',
        [ '@nombre' => $this->node_entity->get('title')->value,
          '@club' => $this->node_entity->field_club->entity->getTitle(),

        ])
      );

      // Se actualiza el número de jugadores del club que tenía asociado
      $query = Drupal::entityQuery('node')
        ->condition('type', 'jugador')
        ->condition('field_club',($this->node_entity)->get('field_club')->target_id)
        ->execute();

      $num_equipos = count($query);
      $this->node_entity->field_club->entity->set('field_numero_de_jugadores', $num_equipos);
      $this->node_entity->field_club->entity->save();

    }

    // Si es árbitro
    elseif($this->tipo=='arbitro'){
      $alias = '/competiciones/arbitros';
      drupal_set_message($this->t('<b>Arbitro eliminado</b>: @nombre ',
        [ '@nombre' => $this->node_entity->get('title')->value,

        ])
      );

    }
    // Si es un partido, se reestablecen los resultados eliminando las estadísticas del partido
    else{

      if ($this->node_entity->get('field_fase')->value == 'Fase de grupos' and $this->node_entity->get('field_estado_partido')->value == 'Jugado') {
        $resultado_local = $this->node_entity->get('field_resultado_local')->value;
        $resultado_visitante = $this->node_entity->get('field_resultado_visitante')->value;

        $this->node_entity->field_equipo_local->entity->set('field_puntos_goles_a_favor', ($this->node_entity->field_equipo_local->entity->get('field_puntos_goles_a_favor')->value)-$resultado_local);
        $this->node_entity->field_equipo_local->entity->set('field_puntos_goles_en_contra', ($this->node_entity->field_equipo_local->entity->get('field_puntos_goles_en_contra')->value)-$resultado_visitante);
        $this->node_entity->field_equipo_local->entity->set('field_diferencia_puntos_goles', ($this->node_entity->field_equipo_local->entity->get('field_diferencia_puntos_goles')->value)+($resultado_visitante - $resultado_local));

        $this->node_entity->field_equipo_visitante->entity->set('field_puntos_goles_a_favor', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos_goles_a_favor')->value)-$resultado_visitante);
        $this->node_entity->field_equipo_visitante->entity->set('field_puntos_goles_en_contra', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos_goles_en_contra')->value)-$resultado_local);
        $this->node_entity->field_equipo_visitante->entity->set('field_diferencia_puntos_goles', ($this->node_entity->field_equipo_visitante->entity->get('field_diferencia_puntos_goles')->value)+($resultado_local-$resultado_visitante));


        if($resultado_local > $resultado_visitante){
          $this->node_entity->field_equipo_local->entity->set('field_puntos', ($this->node_entity->field_equipo_local->entity->get('field_puntos')->value)-3);

        }
        elseif ($resultado_local == $resultado_visitante){
          $this->node_entity->field_equipo_local->entity->set('field_puntos', ($this->node_entity->field_equipo_local->entity->get('field_puntos')->value)-1);
          $this->node_entity->field_equipo_visitante->entity->set('field_puntos', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos')->value)-1);


        }
        else{
          $this->node_entity->field_equipo_visitante->entity->set('field_puntos', ($this->node_entity->field_equipo_visitante->entity->get('field_puntos')->value)-3);

        }

        $this->node_entity->field_equipo_local->entity->save();
        $this->node_entity->field_equipo_visitante->entity->save();

      }

      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->node_entity->field_deporte->target_id);
      drupal_set_message($this->t('<b>Partido eliminado</b>: @nombre de @club ',
        [ '@nombre' => ($this->node_entity->field_equipo_local->entity->get('title')->value) .' - ' .$this->node_entity->field_equipo_visitante->entity->get('title')->value,
          '@club' => $this->node_entity->field_deporte->entity->getTitle(),

        ])
      );

    }

    // Si es un usuario
    }else{
      $alias = '/competiciones/usuarios';
      drupal_set_message($this->t('<b>Usuario eliminado</b>: @nombre ',
        [ '@nombre' => ($this->node_entity->get('name')->value),


        ])
      );

      // Se carga aquellos clubes en el que el usuario seac capitan
      $query = Drupal::entityQuery('node')
        ->condition('field_capitan', $this->node_entity->get('uid')->value)
        ->execute();



      // Si es capitán de algún equipo, se modifica dicho atributo del jugador
      if(count($query)>0){
        $query = Drupal::entityQuery('node')
          ->condition('field_club',Node::load(current($query))->get('nid')->value)
          ->condition('field_capitan_jugador', 'Si')
          ->execute();

        if(!empty($query)){
        $capitan = Node::load(current($query));
        $capitan->set('field_capitan_jugador','No');
        $capitan->save();
      }}



      // Se carga el árbitro que tiene asociado dicho usuario y se elimina
      $query = Drupal::entityQuery('node')
        ->condition('field_arbitro_user', $this->node_entity->get('uid')->value)
        ->execute();



      if(count($query)>0){


        $arbitro = Node::load(current($query));
        $arbitro->delete();
      }


      // Se elimina el usuario
      user_delete($this->node_entity->get('uid')->value);




    }


    MyModuleController::my_goto($alias);

  }


}

