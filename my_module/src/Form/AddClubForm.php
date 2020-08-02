<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;



class AddClubForm extends FormBase
{

  // Diccionario utilizado para asignar deportes a competiciones
  protected $dicc;

  // Nodo del deporte seleccionado en el formulario
  protected $sport_node;

  // ID del nodo deporte al que se va a referir
  protected $id_sport_refered;


  public function getFormId()
  {
    return 'my_module_addclubform';
  }

  public function buildForm(array $form, FormStateInterface $form_state,$id=NULL)
  {



    $this->dicc = array();

    $lista_competiciones = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->execute();

    if (!empty($lista_competiciones)) {

      $check = [];
      foreach ($lista_competiciones as $competicion) {

        $competicion_name = Node::load($competicion)->get('title')->value;
        $nombres_competiciones[] = $competicion_name;



        $lista_deportes_competicion = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('field_competicion', Node::load($competicion)->get('nid')->value)
          ->execute();

        if (!empty($lista_deportes_competicion)) {
          $deporte_names = array();
          foreach ($lista_deportes_competicion as $deporte) {
            $check[] = $deporte;
            $deporte_names[] = Node::load($deporte)->get('title')->value;


          }


          $this->dicc[$competicion_name] = $deporte_names;



        } else {
          $this->dicc[$competicion_name] = ['No hay deportes activos en esta competicion'];

        }



      }

      if (empty($check)) {
        \Drupal::messenger()->addMessage(t("NO EXISTEN DEPORTES ACTIVOS DONDE INSCRIBIR EL CLUB"), 'error');
        MyModuleController::my_goto('<front>');
      }


    } else {

      \Drupal::messenger()->addMessage(t("NO EXISTEN COMPETICIONES ACTIVAS DONDE INSCRIBIR EL CLUB"), 'error');
      MyModuleController::my_goto('<front>');

    }

  $this->id_sport_refered = $id;

    if(!is_null($id)){


      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('nid',$id)
        ->execute();

      $deporte_seleccionado[] = Node::load(current($query))->get('title')->value;


      $nid = Node::load(current($query))->get('field_competicion')->target_id;



      $query = Drupal::entityQuery('node')
        ->condition('type', 'competicion')
        ->condition('nid',$nid)
        ->execute();

      $competicion_seleccionada[] = Node::load(current($query))->get('title')->value;


    }
    else {
      $competicion_seleccionada = $nombres_competiciones;
      $deporte_seleccionado = $this->dicc;
    }








    $form['#title'] = $this->t('<div id="title" align="center"><b>AÑADIR CLUB</b></div>');

    $form['nombre'] = array(
      '#type' => 'textfield',
      '#title' => 'Nombre del equipo :',
      '#required' => TRUE,

    );








    $form['competicion'] = [
      '#type' => 'select',
      '#title' => 'Competición :',
      '#required' => TRUE,
      '#validated' => TRUE,
      '#options' => $competicion_seleccionada,
      '#ajax' => [
        'callback' => '::myAjaxCallback',
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering
        'event' => 'change',
        'wrapper' => 'edit-output',
        'method' => 'replace',

      ]

    ];






    $form['deporte'] = [
      '#type' => 'select',
      '#title' => t('Deporte :'),
      '#options' => $deporte_seleccionado,
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#validated' => TRUE,



    ];

    $form['grupo'] = array(
      '#type' => 'number',
      '#title' => t('Número de jugadores :'),
      '#required' => TRUE,

    );




    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    $form['#attached']['library'][] = 'my_module/my_module.styles';



    return $form;
  }

  public function myAjaxCallback(array &$form, FormStateInterface $form_state)
  {


    $selectedValue = $form_state->getValue('competicion');



    $selectedText = $form['competicion']['#options'][$selectedValue];


    if(array_key_exists($selectedText,$this->dicc))
      $form['deporte']['#options'] = $this->dicc[$selectedText];
    else
      $form['deporte']['#options'] = ['No hay partidos activos en este deporte'];



    return $form['deporte'];
  }


  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    $nombre_club = $form_state->getValue('nombre');

    $competicion_clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$competicion_clave];

    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('title', $competicion_name)
      ->execute();

    $competicion_node = Node::load(current($query));





    $deporte_clave = $form_state->getValue('deporte');
    if(is_null($this->id_sport_refered)){
    $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];

      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('title', $deporte_name)
        ->condition('field_competicion', ($competicion_node)->get('nid')->value)
        ->execute();

    $this->sport_node = Node::load(current($query));}

    else{

    $query = Drupal::entityQuery('node')
      ->condition('type', 'deporte')
      ->condition('nid', $this->id_sport_refered)
      ->condition('field_competicion', ($competicion_node)->get('nid')->value)
      ->execute();

    $this->sport_node = Node::load(current($query));
  }






    //$num_jugadores = $form_state->getValue('jugadores');








    $query = Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('field_deporte', ($this->sport_node)->get('nid')->value)
      ->execute();


    if (!empty($query)) {
      foreach ($query as $club) {
        $club_names[] = Node::load($club)->get('title')->value;
      }
      if (in_array($nombre_club, $club_names)) {

        $option_club = &$form['nombre'];
        $form_state->setError($option_club, $this->t("Ese club ya está inscrito en esa competición"));
      }
    }


  }


  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    $competicion_clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$competicion_clave];



    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('title', $competicion_name)
      ->execute();

    $competicion_node = Node::load(array_pop($query));

    $deporte_clave = $form_state->getValue('deporte');

    if(is_null($this->id_sport_refered)) {
      $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];
    }


    else{

      $deporte_name =  ($this->sport_node)->get('title')->value;
    }


    drupal_set_message($this->t('Club inscrito: @nombre en @deporte - @competicion',
      ['@nombre' => $form_state->getValue('nombre'),
        '@competicion' => $competicion_name,
        '@deporte' => $deporte_name,
      ])
    );


    $num_equipos = $this->sport_node->get('field_numero_de_equipos')->value;
    $this->sport_node->set('field_numero_de_equipos', $num_equipos + 1);
    $id_sport = $this->sport_node->get('nid')->value;
    $this->sport_node->save();


    $alias = MyModuleController::create_node_club($form_state->getValue('nombre'), $form_state->getValue('jugadores'), $id_sport, $deporte_name, $competicion_name,$form_state->getValue('grupo'),$competicion_node->get('nid')->value);
    MyModuleController::my_goto($alias);







  }


}
