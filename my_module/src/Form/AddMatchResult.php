<?php

namespace Drupal\my_module\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\my_module\Controller\MyModuleController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;


class AddMatchResult extends FormBase
{

  protected $dicc_competicion_deporte;
  protected $dicc_deporte_jornada;
  protected $dicc_jornada_partidos;

  protected $nid_partidos;

  public function getFormId()
  {
    return 'my_module_addmatchresult';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {





    //drupal_add_css(drupal_get_path('module', 'my_module') .'/my_module.css');

    $this->dicc_competicion_deporte = array();
    $this->dicc_deporte_jornada = array();
    $this->nid_nodes = array();

    $lista_competiciones = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->execute();

    if (!empty($lista_competiciones)) {

      $check_deporte = [];

      $check_partidos = [];
      foreach ($lista_competiciones as $competicion) {


        $competicion_name = Node::load($competicion)->get('title')->value;
        $nombres_competiciones[] = $competicion_name;

        $this->dicc_deporte_jornada[$competicion_name] = array();
        $this->dicc_jornada_partidos[$competicion_name] = array();


        $this->nid_partidos[$competicion_name] = array(
          'node' => Node::load($competicion),
        );



        $lista_deportes_competicion = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('field_competicion', Node::load($competicion)->get('nid')->value)
          ->execute();

        if (!empty($lista_deportes_competicion)) {
          $deporte_names = array();
          foreach ($lista_deportes_competicion as $deporte) {
            $check_deporte[] = $deporte;
            $deporte_name = Node::load($deporte)->get('title')->value;
            $deporte_names[] = $deporte_name;

            $this->dicc_jornada_partidos[$competicion_name][$deporte_name] = array();
            $this->nid_partidos[$competicion_name][$deporte_name] = array();





                $lista_partidos_grupo = Drupal::entityQuery('node')
                  ->condition('type', 'partido')
                  ->condition('field_deporte_partido', Node::load($deporte)->get('nid')->value)
                  ->execute();

                if (!empty($lista_partidos_grupo)) {
                  $partidos_grupo = array();
                  foreach ($lista_partidos_grupo as $partido) {
                    $check_partidos[] = $partido;
                    #############################################################################
                    #############################################################################
                    $nid_club_local = Node::load($partido)->get('field_equipo_local')->target_id;
                    $query = Drupal::entityQuery('node')
                      ->condition('type', 'club')
                      ->condition('nid', $nid_club_local)
                      ->execute();

                    $nombre_club_local = Node::load(array_pop($query))->get('title')->value;

                    #############################################################################
                    #############################################################################

                    $nid_club_visitante = Node::load($partido)->get('field_equipo_visitante')->target_id;
                    $query = Drupal::entityQuery('node')
                      ->condition('type', 'club')
                      ->condition('nid', $nid_club_visitante)
                      ->execute();

                    $nombre_club_visitante= Node::load(array_pop($query))->get('title')->value;

                    #############################################################################
                    #############################################################################



                    $partido_name = Node::load($partido)->get('title')->value;
                    $grupo =  Node::load($partido)->get('field_partido_grupo')->value;
                    $partidos_grupo[] = $partido_name;




                    $this->nid_partidos[$competicion_name][$deporte_name]['Grupo '.$grupo][] = Node::load($partido);



                    $this->dicc_jornada_partidos[$competicion_name][$deporte_name]['Grupo '.$grupo][] = $nombre_club_local . "   -   " . $nombre_club_visitante;

                  }








              //$this->dicc_deporte_jornada[$competicion_name][$deporte_name] = $grupos;




            } else {$this->dicc_deporte_jornada[$competicion_name][$deporte_name] = ['No hay partidos activos en este deporte'];
            $this->dicc_jornada_partidos[$competicion_name][$deporte_name]= ['No hay partidos activos en este deporte'];
                  }


          }

          $this->dicc_competicion_deporte[$competicion_name] = $deporte_names;
        }else{
          $this->dicc_competicion_deporte[$competicion_name]= ['No hay deportes activos en esta competicion'];
          $this->dicc_jornada_partidos[$competicion_name]= ['No hay deportes activos en esta competicion'];

        }
      }


      if (empty($check_deporte)) {
        \Drupal::messenger()->addMessage(t("No existen deportes activos actualmente"), 'error');
        MyModuleController::my_goto('<front>');
      }
      elseif (empty($check_partidos)){
        \Drupal::messenger()->addMessage(t("No existen partidos activos actualmente"), 'error');
        MyModuleController::my_goto('<front>');

      }

    } else {

      \Drupal::messenger()->addMessage(t("No existen competiciones activas donde inscribir el club"), 'error');
      MyModuleController::my_goto('<front>');

    }





    $form['competicion'] = array(
      '#type' => 'select',
      '#validated' => TRUE,
      '#title' => t('Competición:'),
      '#required' => TRUE,
      '#options' => $nombres_competiciones,
      '#ajax' => [
        'callback' => '::myAjaxCallback',
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering
        'event' => 'change',
        'wrapper' => 'edit-output',
        'method' => 'replace',

      ]

    );


    $form['deporte'] = [
      '#type' => 'select',
      '#title' => t('Deporte :'),
      '#options' => $this->dicc_competicion_deporte,
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#validated' => TRUE,



    ];

    $form['validar']= [
      '#type' => 'button',
      '#value' => t('Validar'),
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::checkJornada',
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering
        'wrapper' => 'edit-partido',
        'event' => 'click',
        'method' => 'replace',

      ],
      '#prefix' => '<div id="validar">',
      '#suffix' => '</div>',






    ];

    $form['partido'] = [
      '#type' => 'select',
      '#validated' => TRUE,
      '#title' => t('Seleccione el partido correspondiente :'),
      '#options' => [],
      '#prefix' => '<div id="edit-partido">',
      '#suffix' => '</div>',


      ];





    $form['club_local'] = array(
      '#type' => 'details',
      '#title' => t('Club local :'),
      '#group' => 'information',
      '#open' => TRUE,
      '#access' => FALSE,



    );

    $form['club_local']['resultado'] = array(
      '#type' => 'number',
      '#title' => t('Resultado Local:'),
      '#required' => TRUE,
      '#value' => 0,


    );

    $form['club_visitante'] = array(
      '#type' => 'details',
      '#title' => t('Club Visitante:'),
      '#group' => 'information',
      '#open' => TRUE,
      '#access' => FALSE,


    );

    $form['club_visitante']['resultado'] = array(
      '#type' => 'number',
      '#title' => t('Resultado Visitante :'),
      '#required' => TRUE,
      '#value' => 0,
      



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


  public function myAjaxCallback(array &$form, FormStateInterface $form_state)
  {


    $selectedValue = $form_state->getValue('competicion');


    $selectedText = $form['competicion']['#options'][$selectedValue];


    $form['deporte']['#options'] = $this->dicc_competicion_deporte[$selectedText];



    return $form['deporte'];
  }


  public function checkJornada(array &$form, FormStateInterface $form_state)
  {


    $selectedValue = $form_state->getValue('competicion');
    $competicion = $form['competicion']['#options'][$selectedValue];

    $selectedValue = $form_state->getValue('deporte');
    $deporte = $form['deporte']['#options'][$competicion][$selectedValue];

    if(array_key_exists($deporte,$this->dicc_jornada_partidos[$competicion]))
      $form['partido']['#options'] = $this->dicc_jornada_partidos[$competicion][$deporte];
    else
      $form['partido']['#options'] = ['No hay partidos activos en este deporte'];






    return $form['partido'];
  }




  public function validateForm(array &$form, FormStateInterface $form_state)
  {


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

  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    $clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$clave];

    $deporte_clave = $form_state->getValue('deporte');
    $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];

    $partido_clave = $form_state->getValue('partido');
    $partido_name = $this->dicc_jornada_partidos[$competicion_name][$deporte_name]['Jornada 1'][$partido_clave];
    $nid = $this->nid_partidos[$competicion_name][$deporte_name]['Jornada 1'][$partido_name];












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
