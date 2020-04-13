<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;

class AddMatchResult extends FormBase {
  
  
    public function getFormId() {
        return 'my_module_addmatchresult';
      }
  
  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $competicion) {
           $competicion_names[] = Node::load($competicion)->get('title')->value;}}
    else{

      drupal_set_message("NO EXISTEN COMPETICIONES ACTIVAS DONDE INSCRIBIR EL CLUB",'error');
      MyModuleController::my_goto('<front>');
    }


    $this->$dicc = array();
    foreach($competicion_names as $competicion){

        $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->condition('title',$competicion)
    ->execute();

    if (!empty($query)) {
        $competicion_id = Node::load(array_pop($query));
        }
    else{

      drupal_set_message("NO EXISTEN COMPETICIONES ACTIVAS",'error');
      MyModuleController::my_goto('<front>');
    }
    

    
    $query = Drupal::entityQuery('node')
    ->condition('type', 'club')
    ->condition('field_competicion',($competicion_id)->get('nid')->value)
    ->execute();

    if (!empty($query)) {
        foreach ($query as $club) {
          $club_names[] = Node::load($club)->get('title')->value;
          }

        $this->dicc[$competicion] = $club_names;}

    else $this->dicc[$competicion] = [];

        unset($club_names);
    }
    

    


            
    $form['competicion'] = array(
        '#type' => 'select',
        '#title' => t('Competición:'),
        '#required' => TRUE,
        '#options' => $competicion_names,
        '#attributes' => [
        //define static name and id so we can easier select it
        // 'id' => 'select-colour',
        'name' => 'field_select_competicion',
      ],

    );

    

    
    
    
        /*
    $competicion_form = &$form['competicion']['#options'][$i];
    


    
    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->condition('title',$competicion_form)
    ->execute();

    if (!empty($query)) {
        $competicion_id = Node::load(array_pop($query));
        }
    else{

      drupal_set_message("NO EXISTEN COMPETICIONES ACTIVAS",'error');
      MyModuleController::my_goto('<front>');
    }
    

    
    $query = Drupal::entityQuery('node')
    ->condition('type', 'club')
    ->condition('field_competicion',($competicion_id)->get('nid')->value)
    ->execute();

    if (!empty($query)) {
        foreach ($query as $club) {
          $club_names[] = Node::load($club)->get('title')->value;
          }}


        
    
    */

    
    
        

    
      
        # code...
    
    
    $form['club_local'] = array(
        '#type' => 'select',
        '#title' => t('Club local :'),
        '#required' => TRUE,
        '#options' => $this->dicc,
       

    );

    $form['club_visitante'] = array(
      '#type' => 'select',
      '#title' => t('Club visitante :'),
      '#required' => TRUE,
      '#options' => $this->dicc,

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


    /*
    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];
    $num_jugadores = $form_state->getValue('jugadores');
    $nombre_club = $form_state->getValue('nombre');

    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->condition('title',$competicion_form)
    ->execute();

    $this->competicion_node = Node::load(array_pop($query));

    
    $query = Drupal::entityQuery('node')
    ->condition('type', 'club')
    ->condition('field_competicion',($this->competicion_node)->get('nid')->value)
    ->execute();


    if (!empty($query)) {
        foreach ($query as $club) {
          $club_names[] = Node::load($club)->get('title')->value;
          }
        if (in_array($nombre_club,$club_names)) {

          $option_club = &$form['nombre'];
          $form_state->setError($option_club, $this->t("Ese club ya está inscrito en esa competición"));
    }}
    

*/
    
    
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {

/*
   

    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];

    drupal_set_message($this->t('Club inscrito: @nombre en @competicion', 
        [ '@nombre' => $form_state->getValue('nombre'),
          '@competicion' => $competicion_form,
        ])
    );


    
      $num_equipos = $this->competicion_node->get('field_numero_de_equipos')->value;
      $this->competicion_node->set('field_numero_de_equipos',$num_equipos+1);
      $id_competition = $this->competicion_node->get('nid')->value;
      $this->competicion_node->save();
      
    

    MyModuleController::create_node_club($form_state->getValue('nombre'),$form_state->getValue('jugadores'),$id_competition);
*/
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