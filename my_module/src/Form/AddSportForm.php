<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;

class AddSportForm extends FormBase {
  
  
    public function getFormId() {
        return 'my_module_addsportform';
      }
  
  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $competicion) {
           $competicion_names[] = Node::load($competicion)->get('title')->value;}}
    else{

      drupal_set_message("NO EXISTEN COMPETICIONES ACTIVAS DONDE INSCRIBIR EL DEPORTE",'error');
      MyModuleController::my_goto('<front>');
    }

            
    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('Nombre del deporte :'),
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


    
    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];
    $nombre_deporte = $form_state->getValue('nombre');

    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->condition('title',$competicion_form)
    ->execute();

    $this->competicion_node = Node::load(array_pop($query));

    
    $query = Drupal::entityQuery('node')
    ->condition('type', 'deporte')
    ->condition('field_competicion',($this->competicion_node)->get('nid')->value)
    ->execute();


    if (!empty($query)) {
        foreach ($query as $deporte) {
          $deporte_names[] = Node::load($deporte)->get('title')->value;
          }
        if (in_array($nombre_deporte,$deporte_names)) {

          $option_deporte = &$form['nombre'];
          $form_state->setError($option_deporte, $this->t("Ese deporte ya está inscrito en esa competición"));
    }}
    


    
    
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {


   

    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];

    drupal_set_message($this->t('Deporte inscrito: @nombre en @competicion', 
        [ '@nombre' => $form_state->getValue('nombre'),
          '@competicion' => $competicion_form,
        ])
    );


    MyModuleController::create_node_deporte($form_state->getValue('nombre'),0,($this->competicion_node)->get('nid')->value);

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