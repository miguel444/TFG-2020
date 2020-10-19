<?php

/**
 * @file
 * Contains \Drupal\local_league_management\Controller\MyModuleController
 */

namespace Drupal\local_league_management\Controller;

use Drupal;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use http\Env\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Url;

class MyModuleController extends ControllerBase
{


 // Función utilizada para crear un nodo de tipo 'club', se pasan los datos del club por parámetros,
  // se crea el nodo y se devuelve la ruta al nodo creado.
  public static function create_node_club($nombre, $nid_deporte, $jugadores, $nombre_competicion, $grupo,$nid_competicion,$estado,$capitan)
  {

    if($estado == 'Activado')
      $publicado = TRUE;
    else
      $publicado = FALSE;


    $node = Node::create(array(
      'type' => 'club',
      'title' => $nombre,
      'status' => $publicado,
      'field_deporte' => $nid_deporte,
      'field_numero_de_jugadores' => $jugadores,
      'field_capitan' => $capitan,
      'field_alias' =>str_replace(" ", "-",strtolower($nombre)),
      'field_estado_club' => $estado,
      'field_grupo' => $grupo,
      'field_diferencia_puntos_goles' => 0 ,
      'field_puntos' => 0 ,
      'field_puntos_goles_a_favor' => 0 ,
      'field_puntos_goles_en_contra' => 0,
      'field_competicion' => $nid_competicion,

    ));

    $node->save();

    return \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->get('nid')->value);


  }
  ###################################################################################################################
  ####################################################################################################################


// Función utilizada para crear un nodo de tipo 'partido', se pasan los datos del partido por parámetros,
  // se crea el nodo y se devuelve el identificador el nodo creado.
  public static function create_node_partido($club_local,$resultado_local,$club_visitante,$resulado_visitante, $deporte,$estado, $fase, $fecha, $lugar,$grupo,$arbitro)
  {


    $query = Drupal::entityQuery('node')
      ->condition('type', 'partido')
      ->execute();




    $node = Node::create(array(
        'type' => 'partido',
        'title'=> count($query),
        'field_equipo_local' => $club_local,
        'field_resultado_local' => $resultado_local,
        'field_equipo_visitante' => $club_visitante,
        'field_resultado_visitante' => $resulado_visitante,
        'field_estado_partido' => $estado,
        'field_arbitro' => $arbitro,
        'field_deporte' => $deporte,
        'field_fase' => $fase,
        'field_fecha_partido' => $fecha,
        'field_partido_grupo' => $grupo,
        'field_lugar' => $lugar,

      )
    );



    $node->save();

return $node->id();

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función utilizada para crear un nodo de tipo 'jugador', se pasan los datos del jugador por parámetros y
  // se crea el nodo.
  public static function create_node_jugador($nombre,$apellidos, $club, $correo, $dni, $ficha,$capitan)
  {


    $node = Node::create(array(
      'type' => 'jugador',
      'title' => $nombre,
      'field_club' => $club,
      'field_dni' => $dni,
      'field_correo_electronico' => $correo,
      'field_apellidos' => $apellidos,
      'field_ficha_deportiva' => $ficha,
      'field_capitan_jugador' => $capitan,

      )
    );

    $node->save();


  }

  ###################################################################################################################
  ####################################################################################################################

  // Función utilizada para crear un nodo de tipo 'arbitro', se pasan los datos del árbitro por parámetros y
  // se crea el nodo.
  public static function create_node_arbitro($nombre,$apellidos, $correo, $dni,$numero_colegiado,$arbitro)
  {


    $node = Node::create(array(
        'type' => 'arbitro',
        'title' => $nombre,
        'field_dni' => $dni,
        'field_correo_electronico' => $correo,
        'field_apellidos' => $apellidos,
        'field_numero_colegiado' => $numero_colegiado,
        'field_arbitro_user' => $arbitro,

      )
    );

    $node->save();


  }

  ###################################################################################################################
  ####################################################################################################################


// Función utilizada para crear un nodo de tipo 'deporte', se pasan los datos del deporte por parámetros,
  // se crea el nodo y se devuelve la ruta al nodo creado.
  public static function create_node_deporte($nombre, $num_equipos,$numero_maximo, $nid_competicion, $nombre_competicion,$fecha_inicio,$fecha_fin,$fecha_inicio_inscripcion,$fecha_fin_inscripcion)
  {

    $node = Node::create(array(
      'type' => 'deporte',
      'title' => $nombre,
      'field_numero_de_equipos' => $num_equipos,
      'field_numero_maximo_de_equipos' => $numero_maximo,
      'field_alias' => str_replace(" ", "-",strtolower($nombre)),
      'field_competicion' => $nid_competicion,
      'field_fecha_de_inicio' => $fecha_inicio,
      'field_fecha_de_inicio_inscripcio' => $fecha_inicio_inscripcion,
      'field_fecha_de_fin' => $fecha_fin,
      'field_fecha_de_fin_inscripcion' => $fecha_fin_inscripcion,

    ));


    $node->save();

    return \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->get('nid')->value);
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función utilizada para crear un nodo de tipo 'competicion', se pasan los datos de la competición por parámetros,
  // se crea el nodo y se devuelve la ruta al nodo creado.
  public static function create_node_competicion($nombre, $num_deportes,$body,$reglamento,$anio,$estado,$imagen)
  {

    if($estado == 'Activa')
      $publicado = TRUE;
    else
      $publicado = FALSE;

    $node = Node::create(array(
      'type' => 'competicion',
      'field_reglamento' => $reglamento,
      'title' => $nombre,
      'status'=> $publicado,
      'field_alias' => str_replace(" ", "-",strtolower($nombre)),
      'field_ano_academico' => $anio,
      'field_numero_de_deportes' => $num_deportes,
      'field_estado' => $estado,
      'field_image' => $imagen,
      'body' => $body,

    ));

    $node->save();

    return \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->get('nid')->value);
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función utilizada para dirigir al usuario a la ruta indicada.
  public static function my_goto($path)
  {
    $response = new RedirectResponse($path);
    $response->send();
    return;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función utilizada para comparar dos fechas
  public static function compare_date($date1, $date2)
  {




    if ((int)substr($date1, 0, 4) > (int)substr($date2, 0, 4)) {
      return TRUE;
    }
    elseif((int)substr($date1, 5, 2) > (int)substr($date2, 5, 2)) {
      return TRUE;
    }
    elseif ((int)substr($date1, -2) > (int)substr($date2, -2) and (int)substr($date1, 5, 2) == (int)substr($date2, 5, 2)){
      return TRUE;}
    else{
      return FALSE;}

  }




  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de competición y envía el formulario para añadir
  // deporte a  dicha competición.
  public function add_deporte($id)
  {



    if (!is_numeric($id)) {

      $query = Drupal::entityQuery('node')
        ->condition('field_alias', $id)
        ->execute();

      $id = Node::load(current($query))->get('nid')->value;

    }




    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddSportForm', $id);


  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de deporte y envía el formulario para editar el
  // deporte.
  public function edit_deporte($competicion,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddSportForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de deporte y envía el formulario para eliminar
  // un deporte del sistema.
  public function delete_deporte($competicion,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  //// Función que procesa la entrada para convertir en identificador de deporte y envía el formulario para copiar
  // un deporte a otra competición.
    public function copy_deporte($competicion,$id)
    {

      if (!is_numeric($id)) {


        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('field_alias', $id)
          ->execute();

        foreach ($query as $deporte) {
          $node_deporte = Node::Load($deporte);

          if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
            $id = $node_deporte->get('nid')->value;
            break;
          }

        }


      }


      return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\CopyForm', $id);


    }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de competicón y envía el formulario para editar la
  // competción.
  public function edit_competicion($id)
  {

    if (!is_numeric($id)) {

        $query = Drupal::entityQuery('node')
          ->condition('field_alias', $id)
          ->execute();

        $id = Node::load(current($query))->get('nid')->value;

      }


    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddCompeticionForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de competición y envía el formulario para eliminar
  // una competición del sistema.
  public function delete_competicion($id)
  {

    if (!is_numeric($id)) {

      $query = Drupal::entityQuery('node')
        ->condition('field_alias', $id)
        ->execute();

      $id = Node::load(current($query))->get('nid')->value;

    }


    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de deporte y envía el formulario para añadir
  // un club  a dicho deporte.
  public function add_club($competicion,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddClubForm',$id);

  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de club y envía el formulario para editar el
  // club.
  public function edit_club($competicion,$deporte,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','club')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $club){
        $node_club = Node::Load($club);

        if ($node_club->field_competicion->entity->field_alias->value == $competicion and $node_club->field_deporte->entity->field_alias->value == $deporte) {
          $id = $node_club->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddClubForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de club y envía el formulario para eliminar
  // un club del sistema.
  public function delete_club($competicion,$deporte,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','club')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $club){
        $node_club = Node::Load($club);

        if ($node_club->field_competicion->entity->field_alias->value == $competicion and $node_club->field_deporte->entity->field_alias->value == $deporte) {
          $id = $node_club->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de club y envía el formulario para copiar
  // un club en otro deporte.
  public function copy_club($competicion,$deporte,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','club')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $club){
        $node_club = Node::Load($club);

        if ($node_club->field_competicion->entity->field_alias->value == $competicion and $node_club->field_deporte->entity->field_alias->value == $deporte) {
          $id = $node_club->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\CopyForm',$id);

  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de club y envía el formulario para añadir
  // un jugador  a dicho club.
  public function add_jugador($competicion,$deporte,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','club')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $club){
        $node_club = Node::Load($club);

        if ($node_club->field_competicion->entity->field_alias->value == $competicion and $node_club->field_deporte->entity->field_alias->value == $deporte) {
          $id = $node_club->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddJugadorForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de jugador y envía el formulario para editar el
  // jugador.
  public function edit_jugador($competicion,$deporte,$club,$id)
  {

    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','club')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $club){
        $node_club = Node::Load($club);

        if ($node_club->field_competicion->entity->field_alias->value == $competicion and $node_club->field_deporte->entity->field_alias->value == $deporte) {
          $id = $node_club->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddJugadorForm',$id);

  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de jugador y envía el formulario para eliminar
  // un jugador del sistema.
  public function delete_jugador($competicion,$deporte,$club,$id)
  {


    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','club')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $club){
        $node_club = Node::Load($club);

        if ($node_club->field_competicion->entity->field_alias->value == $competicion and $node_club->field_deporte->entity->field_alias->value == $deporte) {
          $id = $node_club->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de deporte y envía el formulario para añadir
  // un partido a dicho deporte.
  public function add_partido($competicion,$id)
  {


    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddPartidoForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de partido y envía el formulario para editar el
  // partido.
  public function edit_partido($competicion,$deporte,$id)
  {


    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddPartidoForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de partido y envía el formulario para eliminar
  // un partido del sistema.
  public function delete_partido($competicion,$deporte,$id)
  {


    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }


  ###################################################################################################################
  ####################################################################################################################

  // Función que procesa la entrada para convertir en identificador de partido y envía el formulario para rellenar
  // el acta de dicho partido.
  public function rellenar_acta($competicion,$deporte,$id)
  {


    if (!is_numeric($id)) {



      $query = Drupal::entityQuery('node')
        ->condition('type','deporte')
        ->condition('field_alias', $id)
        ->execute();

      foreach ($query as $deporte){
        $node_deporte = Node::Load($deporte);

        if ($node_deporte->field_competicion->entity->field_alias->value == $competicion) {
          $id = $node_deporte->get('nid')->value;
          break;
        }

      }



    }



    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddMatchResult',$id);

  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que envía el formulario para editar el usuario.
  public function edit_user($id)
  {

    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\CreateUser',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que  envía el formulario para eliminar un usuario del sistema.
   public function delete_user($id)
  {


    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que envía el formulario para editar un árbitro.
  public function edit_arbitro($id)
  {

    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\AddArbitro',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que envía el formulario para eliminar un árbitro del sistema.
  public function delete_arbitro($id)
  {


    return \Drupal::formBuilder()->getForm('\Drupal\local_league_management\Form\DeleteForm',$id);

  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que dado un nodo, actualiza todas las entidades que contienen referencias a éste.
  public static function update($node){

    // Si es una competicion
    if($node->getType() == 'competicion'){

      // Se hace una consulta de todas las entidades que hacen referencia a la competición y se actualizan
      $query = Drupal::entityQuery('node')
        ->condition('field_competicion',$node->get('nid')->value)
        ->execute();

      foreach ($query as $nodo){
        Node::load($nodo)->save();
      }




    }
    // Si es un deporte
    elseif ($node->getType() == 'deporte'){
      // Se hace una consulta de todas las entidades que hacen referencia al deporte y se actualizan
      $query = Drupal::entityQuery('node')
        ->condition('field_deporte',$node->get('nid')->value)
        ->execute();

      foreach ($query as $nodo){
        Node::load($nodo)->save();
      }

    }

    // Si es un club
    elseif ($node->getType() == 'club'){
      // Se hace una consulta de todas las entidades que hacen referencia al club y se actualizan
      $query = Drupal::entityQuery('node')
        ->condition('field_club',$node->get('nid')->value)
        ->execute();

      foreach ($query as $nodo){
        Node::load($nodo)->save();
      }

    }


  }






}

?>
