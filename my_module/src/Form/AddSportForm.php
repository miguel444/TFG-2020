<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;

class AddSportForm extends FormBase {

  protected $competicion_node;


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

    $form['#title'] = $this->t('<div id="title" align="center"><b>AÑADIR NUEVO DEPORTE</b></div>');





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


    $form['equipos_maximos'] = array(
      '#type' => 'number',
      '#title' => t('Número máximo de equipos participantes. Si indica 0 podrán participar todos los equipos que se desee'),
      '#value' => 0,
      '#required' => TRUE,

    );

    $form['fecha_competicion'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('Competición'),
      '#group' => 'information',
      '#open' => TRUE,
    );

    $form['fecha_competicion']['fecha_inicio'] = array(
      '#type' => 'date',
      '#title' => t('Fecha de inicio de la competición :'),
      '#prefix' => '<div id="fecha_competicion">',
    );



    $form['fecha_competicion']['fecha_fin'] = array(
      '#type' => 'date',
      '#title' => t('Fecha de fin de la competición :'),
      '#suffix' => '</div>',
    );

    $form['fecha_inscripcion'] = array(
      '#type' => 'details',
      '#title' => $this
        ->t('Inscripción'),
      '#group' => 'information',
      '#open' => TRUE,
    );

    $form['fecha_inscripcion']['fecha_inicio_inscripcion'] = array(
      '#type' => 'date',
      '#title' => t('Fecha de inicio de inscripción :'),
      '#prefix' => '<div id="fecha_inscripcion">',
    );

    $form['fecha_inscripcion']['fecha_fin_inscripcion'] = array(
      '#type' => 'date',
      '#title' => t('Fecha de fin de inscripción: '),
      '#suffix' => '</div>',
    );

    $form['reglamento'] = array(
      '#type' => 'managed_file',
      '#title' => $this
        ->t('Reglamento'),
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

    $fecha_inicio = $form_state->getValue('fecha_inicio');
    $fecha_fin = $form_state->getValue('fecha_fin');

    if(MyModuleController::compare_date($fecha_inicio,$fecha_fin)){
      $fecha = &$form['fecha_competicion']['fecha_fin'];
      $form_state->setError($fecha, $this->t("La fecha de finalización de la competición no puede ser inferior a la fecha de inicio"));

    }

    $fecha_inicio_inscripcion = $form_state->getValue('fecha_inicio_inscripcion');
    $fecha_fin_incripcion = $form_state->getValue('fecha_fin_inscripcion');

    if(MyModuleController::compare_date($fecha_inicio_inscripcion,$fecha_fin_incripcion)){
      $fecha = &$form['fecha_inscripcion']['fecha_fin_inscripcion'];
      $form_state->setError($fecha, $this->t("La fecha de fin de inscripción no puede ser inferior a la fecha de inicio de inscripción"));

    }

    if(MyModuleController::compare_date($fecha_inicio_inscripcion,$fecha_inicio)){
      $fecha = &$form['fecha_competicion']['fecha_inicio'];
      $form_state->setError($fecha, $this->t("La fecha de inicio de la competición no puede ser superior a la fecha de inicio de inscripción"));

    }

    if(MyModuleController::compare_date($fecha_fin_incripcion,$fecha_inicio)){
      $fecha = &$form['fecha_inscripcion']['fecha_fin_inscripcion'];
      $form_state->setError($fecha, $this->t("La fecha de fin de la inscripción no puede ser superior a la fecha de inicio de la competición"));

    }








  }

  public function submitForm(array &$form, FormStateInterface $form_state) {




    $clave = $form_state->getValue('competicion');
    $competicion_form = &$form['competicion']['#options'][$clave];

    drupal_set_message($this->t('<b>DEPORTE INSCRITO</b>: @nombre en @competicion',
        [ '@nombre' => $form_state->getValue('nombre'),
          '@competicion' => $competicion_form,
        ])
    );


    $PATH = MyModuleController::create_node_deporte($form_state->getValue('nombre'),0,($this->competicion_node)->get('nid')->value,$competicion_form);

    MyModuleController::my_goto($PATH);
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
