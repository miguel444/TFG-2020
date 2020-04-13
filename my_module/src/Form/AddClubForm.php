<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;

class AddClubForm extends FormBase {
  
  
    public function getFormId() {
        return 'my_module_addclubform';
      }
  
  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'deporte')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $sport) {
           $sport_names[] = Node::load($sport)->get('title')->value;}}
    else{

      drupal_set_message("NO EXISTEN COMPETICIONES ACTIVAS DONDE INSCRIBIR EL CLUB",'error');
      MyModuleController::my_goto('<front>');
    }

            
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

    $form['deporte'] = array(
      '#type' => 'select',
      '#title' => t('Competición :'),
      '#required' => TRUE,
      '#options' => $sport_names,

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


    
    $clave = $form_state->getValue('deporte');
    $sport_form = &$form['deporte']['#options'][$clave];
    $num_jugadores = $form_state->getValue('jugadores');
    $nombre_club = $form_state->getValue('nombre');

    $query = Drupal::entityQuery('node')
    ->condition('type', 'deporte')
    ->condition('title',$sport_form)
    ->execute();

    $this->sport_node = Node::load(array_pop($query));

    
    $query = Drupal::entityQuery('node')
    ->condition('type', 'club')
    ->condition('field_deporte',($this->sport_node)->get('nid')->value)
    ->execute();


    if (!empty($query)) {
        foreach ($query as $club) {
          $club_names[] = Node::load($club)->get('title')->value;
          }
        if (in_array($nombre_club,$club_names)) {

          $option_club = &$form['nombre'];
          $form_state->setError($option_club, $this->t("Ese club ya está inscrito en esa competición"));
    }}
    


    
    
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {


   

    $clave = $form_state->getValue('deporte');
    $sport_form = &$form['deporte']['#options'][$clave];

    drupal_set_message($this->t('Club inscrito: @nombre en @deporte', 
        [ '@nombre' => $form_state->getValue('nombre'),
          '@deporte' => $sport_form,
        ])
    );


    
      $num_equipos = $this->sport_node->get('field_numero_de_equipos')->value;
      $this->sport_node->set('field_numero_de_equipos',$num_equipos+1);
      $id_sport = $this->sport_node->get('nid')->value;
      $this->sport_node->save();
      
    

    MyModuleController::create_node_club($form_state->getValue('nombre'),$form_state->getValue('jugadores'),$id_sport);

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