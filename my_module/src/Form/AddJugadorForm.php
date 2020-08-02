<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;

class AddJugadorForm extends FormBase {

  protected $club_names;
  protected $clubes_id;
    public function getFormId() {
        return 'my_module_addplayerform';
      }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $query = Drupal::entityQuery('node')
    ->condition('type', 'club')
    ->execute();

    if (!empty($query)) {
        foreach ($query as $club) {
           $this->club_names[] = Node::load($club)->get('title')->value;
           $this->clubes_id[] = Node::load($club);}}


    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('Nombre y apellidos del jugador :'),
        '#required' => TRUE,

    );

    $form['fecha'] = array(
        '#type' => 'date',
        '#title' => t('Fecha de nacimiento :'),
        '#required' => TRUE,

    );

    $form['club'] = array(
        '#type' => 'select',
        '#title' => t('Club :'),
        '#required' => TRUE,
        '#options' => $this->club_names,

    );

    $form['correo'] = array(
        '#type' => 'email',
        '#title' => t('Correo electrónico :'),
        '#required' => TRUE,

    );

    $form['telefono'] = array(
        '#type' => 'tel',
        '#title' => t('Teléfono :'),

    );

    $form['foto_jugador'] = array(
        '#type' => 'managed_file',
        '#title' => t('Foto del jugador :'),
        '#upload_validators' => array(
          'file_validate_extensions' => array('gif png jpg jpeg'),
          '#upload_location' => 'public://pictures',
        ),

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
    ->condition('type', 'jugador')
    ->execute();

    $clave = $form_state->getValue('club');
    $club_form = &$form['club']['#options'][$clave];


    if (!empty($query)) {
        foreach ($query as $jugador) {
          $nid = Node::load($jugador)->get('field_club')->target_id;
          if(!is_null($nid)){
              $club_jugador = Node::load($nid)->get('title')->value;
              if ($club_jugador == $club_form) {
                  $jugador_names[] = Node::load($jugador)->get('title')->value;
          }
           }}}


    $nombre_jugador = $form_state->getValue('nombre');

    if(!empty($jugador_names)){
        if (in_array($nombre_jugador,$jugador_names)) {

            $option_jugador = &$form['nombre'];
            $form_state->setError($option_jugador, $this->t("Ese nombre ya existe en ese club"));
    }}


  }

  public function submitForm(array &$form, FormStateInterface $form_state) {


    $clave = $form_state->getValue('club');
    $club_form = &$form['club']['#options'][$clave];
    $nombre = $form_state->getValue('nombre');
    $fecha = $form_state->getValue('fecha');
    $correo = $form_state->getValue('correo');
    $telefono = $form_state->getValue('telefono');
    $foto = $form_state->getValue('foto_jugador');


    drupal_set_message($this->t('Jugador inscrito en : @club',
        ['@club' => $club_form,
        ])
    );

    foreach ($this->clubes_id as $club ) {
      if ($club->get('title')->value == $club_form) {
          $id_club = $club->get('nid')->value;

      }
    }


    MyModuleController::create_node_jugador($nombre,$fecha,$id_club,$correo,$telefono,$foto);

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
