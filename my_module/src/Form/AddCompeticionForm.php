<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\my_module\Controller\MyModuleController;

class AddCompeticionForm extends FormBase {
  
  
    public function getFormId() {
        return 'my_module_addcompeticionform';
      }
  
  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'competicion')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $competicion) {
           $this->competicion_names[] = Node::load($competicion)->get('title')->value;}}

            
    
    

    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('Nombre de la competición :'),
        '#required' => TRUE,

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

    

    $nombre_competicion = $form_state->getValue('nombre');
  
    if(!empty($this->competicion_names)){
        if (in_array($nombre_competicion,$this->competicion_names)) {

            $option_club = &$form['nombre'];
            $form_state->setError($option_club, $this->t("Esa competición ya existe"));
    }}
    
    
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {


    $competicion_form = $form_state->getValue('nombre');
    

    drupal_set_message($this->t('Competición añadida: @competicion', 
        ['@competicion' => $competicion_form,
        ])
    );


    MyModuleController::create_node_competicion($form_state->getValue('nombre'),0);

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