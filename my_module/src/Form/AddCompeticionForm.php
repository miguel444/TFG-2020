<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;
use Drupal\file\Entity\File;

class AddCompeticionForm extends FormBase {

  protected $competicion_names;

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

    $form['descripcion'] = array(
      '#type' => 'text_format',
      '#title' => t('Descripción :'),
      '#validated' => TRUE,


    );

    $form['año'] = array(
      '#type' => 'number',
      '#title' => t('Año académico (Ejemplo 2011 -> Curso 2011/2012) :'),
      '#validated' => TRUE,
      '#requited' => TRUE,
      '#size' => 4,
      '#prefix' => '<div id="año">',
      '#suffix' => '</div>',


    );

    $form['reglamento'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Reglamento (Archivos PDF)'),
      '#prefix' => '<div class="reglamento">',
      '#suffix' => '</div>',
      '#upload_location' => 'public://rules',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
      ],

    );



    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
      ];

    $form['#attached']['library'][] = 'my_module/my_module.styles';

    return $form;
  }



  public function validateForm(array &$form, FormStateInterface $form_state) {


    $nombre_competicion = $form_state->getValue('nombre');

    if(!empty($this->competicion_names)){
        if (in_array($nombre_competicion,$this->competicion_names)) {

            $option_competicion = &$form['nombre'];
            $form_state->setError($option_competicion, $this->t("Ya existe una competición con ese nombre"));
    }}


  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_file = $form_state->getValue('reglamento', 0);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
    }

    $anio = $form_state->getValue('año');


    $competicion_form = $form_state->getValue('nombre');

    $body = $form_state->getValue('descripcion');


    drupal_set_message($this->t('<b>Competición añadida:</b> @competicion',
        ['@competicion' => $competicion_form,
        ])
    );


    MyModuleController::create_node_competicion($form_state->getValue('nombre'),0,$body,$form_file[0],$anio);

    MyModuleController::my_goto('<front>');
  }



}
