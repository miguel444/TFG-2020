<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\my_module\Controller\MyModuleController;

class AddClubForm extends FormBase {
  
  
    public function getFormId() {
        return 'my_module_addclubform';
      }
  
  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $competicion) {
           $this->competiciones_id[] = Node::load($competicion);
           $competicion_names[] = Node::load($competicion)->get('title')->value;}}

            
    
    

    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('Nombre del equipo :'),
        '#required' => TRUE,

    );

    $form['jugadores'] = array(
        '#type' => 'number',
        '#title' => t('Número de jugadores :'),
        '#required' => TRUE,

    );

    $form['competicion'] = array(
      '#type' => 'select',
      '#title' => t('Competición :'),
      '#required' => TRUE,
      '#options' => $competicion_names,

  );

    $form['accept'] = array(
        '#type' => 'checkbox',
        '#title' => t('Acepto los términos de uso de esta web'),
        '#description' =>t('Por favor lee y acepta las condiciones de uso'),
        '#required' => TRUE,
      );

    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      ];
    
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'club')
    ->execute();

    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];
    $num_jugadores = $form_state->getValue('jugadores');
    $nombre_club = $form_state->getValue('nombre');

    if (!empty($query)) {
        foreach ($query as $club) {
          $nid = Node::load($club)->get('field_competicion')->target_id;
          if(!is_null($nid)){
              $club_competiciones = Node::load($nid)->get('title')->value;
              if ($club_competiciones == $competicion_form) {
                  $club_names[] = Node::load($club)->get('title')->value;
          }
           }}
           
        if (in_array($nombre_club,$club_names)) {

          $option_club = &$form['nombre'];
          $form_state->setError($option_club, $this->t("Ese club ya está inscrito en esa competición"));
    }}
    

    if($num_jugadores < 11 || $num_jugadores > 22){
      $option_players = &$form['jugadores'];
      $form_state->setError($option_players, $this->t("Numero de jugadores érroneo (Min=11 , Max=22) "));
    }

    
    
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {


   

    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];

    drupal_set_message($this->t('Club inscrito: @nombre en @competicion', 
        [ '@nombre' => $form_state->getValue('nombre'),
          '@competicion' => $competicion_form,
        ])
    );


    foreach ($this->competiciones_id as $competicion ) {
      if ($competicion->get('title')->value == $competicion_form) {
          $num_equipos = $competicion->get('field_numero_de_equipos')->value;
          $competicion->set('field_numero_de_equipos',$num_equipos+1);
          $id_competition = $competicion->get('nid')->value;
          $competicion->save();
      }
    }

    MyModuleController::create_node_club($form_state->getValue('nombre'),$form_state->getValue('jugadores'),$id_competition);

    MyModuleController::my_goto('<front>');
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