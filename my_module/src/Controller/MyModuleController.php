<?php

/**
 * @file
 * Contains \Drupal\my_module\Controller\MyModuleController
 */

namespace Drupal\my_module\Controller;

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

  public $nodos_competiciones;
  public $nodos_clubes;
  public $nodos_jugadores;


  public static function create_node_club($nombre, $num_jugadores, $nid_deporte, $nombre_deporte, $nombre_competicion, $grupo,$nid_competicion)
  {



    $node = Node::create(array(
      'type' => 'club',
      'title' => $nombre,
      'field_deporte' => $nid_deporte,
      'field_numero_de_jugadores' => $num_jugadores,
      'field_grupo' => $grupo,
      'field_competicion' => $nid_competicion,
      'path' => [
        'alias' => '/sport_tracker/' . str_replace(' ', '_', $nombre_competicion) . '/' . str_replace(' ', '_', $nombre_deporte) . '/' . str_replace(' ', '_', $nombre),]
    ));

    $node->save();

    return \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->get('nid')->value);

    //$this->update_nodes(1);
  }

  public static function create_node_jugador($nombre, $fecha, $club, $correo, $telefono, $foto)
  {

    $node = Node::create(array(
      'type' => 'jugador',
      'title' => $nombre,
      'field_club' => $club,
      'field_fecha' => $fecha,
      'field_correo_electronico' => $correo,
      'field_telefono' => $telefono,
      'field_foto_jugador' => $foto,));

    $node->save();

    //$this->update_nodes(1);
  }


  public static function create_node_deporte($nombre, $num_equipos, $nid_competicion, $nombre_competicion,$fecha_inicio,$fecha_fin,$fecha_inicio_inscripcion,$fecha_fin_inscripcion)
  {

    $node = Node::create(array(
      'type' => 'deporte',
      'title' => $nombre,
      'field_numero_de_equipos' => $num_equipos,
      'field_competicion' => $nid_competicion,
      'field_fecha_de_inicio' => $fecha_inicio,
      'field_fecha_de_inicio_inscripcio' => $fecha_inicio_inscripcion,
      'field_fecha_de_fin' => $fecha_fin,
      'field_fecha_de_fin_inscripcion' => $fecha_fin_inscripcion,
      'path' => [
        'alias' => '/sport_tracker/' . str_replace(' ', '_', $nombre_competicion) . '/' . str_replace(' ', '_', $nombre),]
    ));


    $node->save();

    return \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->get('nid')->value);
  }

  public static function create_node_competicion($nombre, $num_deportes,$body,$reglamento,$anio)
  {

    $node = Node::create(array(
      'type' => 'competicion',
      'field_reglamento' => $reglamento,
      'title' => $nombre,
      'field_ano_academico' => $anio,
      'field_numero_de_deportes' => $num_deportes,
      'body' => $body,
      'path' => [
        'alias' => '/sport_tracker/' . str_replace(' ', '_', $nombre) ]
    ));

    $node->save();

    //$this->update_nodes(0);
  }

  public static function my_goto($path)
  {
    $response = new RedirectResponse($path);
    $response->send();
    return;
  }

  public static function compare_date($date1, $date2)
  {




    if ((int)substr($date1, 0, 4) > (int)substr($date2, 0, 4)) {
      return TRUE;
    }
    elseif((int)substr($date1, 5, 2) > (int)substr($date2, 5, 2)) {
      return TRUE;
    }
    elseif ((int)substr($date1, -2) > (int)substr($date2, -2)){
      return TRUE;}
    else{
      return FALSE;}

  }


  public function settings()
  {
    foreach ($this->nodos_clubes as $clubes) {


      $aux[] = $clubes->get('field_numero_de_jugadores')->value;

    }

    return array(
      '#type' => 'item',
      '#markup' => t("Hola mundo"),

    );
  }


  public function test()
  {

    return array(
      '#type' => 'item',
      '#markup' => t("Hola mundo"),

    );

  }


  public function add_menu()
  {

    $my_menu = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties(['menu_name' => 'my-menu-name']);
    foreach ($my_menu as $menu_item) {
      $parent_id = $menu_item->getParentId();
      if (!empty($parent_id)) {
        $top_level = $parent_id;
        break;
      }
    }
    $menu_link = MenuLinkContent::create([
      'title' => 'My menu link title',
      'link' => ['uri' => 'internal:/my/path'],
      'menu_name' => 'my-menu-name',
      'parent' => $top_level,
      'expanded' => TRUE,
      'weight' => 0,
    ]);
    $menu_link->save();

    return $menu_link;

  }

  public function add_form($id)
  {

    $node = node_load($id);

    $type = $node->getType($node);

    switch ($type) {
      case "competicion":
        return \Drupal::formBuilder()->getForm('\Drupal\my_module\Form\AddSportForm', $id);
        break;
      case "deporte":
        return \Drupal::formBuilder()->getForm('\Drupal\my_module\Form\AddClubForm', $id);
        break;
      case "club":
        return \Drupal::formBuilder()->getForm('\Drupal\my_module\Form\AddJugadorForm', $id);
        break;


    }

  }


  public function edit_form($id)
  {

    $node = node_load($id);

    $type = $node->getType($node);

    switch ($type) {
      case "competicion":
        return \Drupal::formBuilder()->getForm('\Drupal\my_module\Form\AddSportForm', $id);
        break;
      case "deporte":
        return \Drupal::formBuilder()->getForm('\Drupal\my_module\Form\AddClubForm', $id);
        break;
      case "club":
        return \Drupal::formBuilder()->getForm('\Drupal\my_module\Form\AddJugadorForm', $id);
        break;


    }

  }




}

?>
