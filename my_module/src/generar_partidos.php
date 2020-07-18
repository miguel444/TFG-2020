<?php

use Drupal;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;


function generar_partidos($deporte,$num_equipos_grupo){

  $partidos_jornada = array();


  $lista_partidos_jornada = Drupal::entityQuery('node')
    ->condition('type', 'partido')
    ->condition('field_deporte_partido', Node::load($deporte)->get('nid')->value)
    ->execute();

  if (!empty($lista_partidos_jornada)) {
    foreach ($lista_partidos_jornada as $partido) {
      $partidos_jornada[] = Node::load($partido);


    }



  }
}
