<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\local_league_management\Controller\MyModuleController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;



class AddClubForm extends FormBase
{

  // Diccionario utilizado para asignar deportes a competiciones
  protected $dicc;

  // Nodo del deporte seleccionado en el formulario
  protected $tipo;

  // Si el usuario es gestor
  protected $admin;

  // Nodo del club
  protected $club_node;

  // Nodo del deporte del club
  protected $sport_node;

  ###################################################################################################################
  ####################################################################################################################

// Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_addclubform';
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state,$id=NULL)
  {

    $this->dicc = array();

    // Se almancenan todas las competiciones junto a todos sus deportes correspondientes
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
        \Drupal::messenger()->addMessage(t("No existen deportes activos donde inscribir el club"), 'error');
        MyModuleController::my_goto('<front>');
      }


    } else {

      \Drupal::messenger()->addMessage(t("No existen competiciones activas donde inscribir el club"), 'error');
      MyModuleController::my_goto('<front>');

    }

    // Se obtiene el usuario actual
    $current_user = \Drupal::currentUser();


    if(!is_null($id)){

      $node = node_load($id);

      $type = $node->getType();
      $this->tipo = $type;

      // Si se va a crear un club
      if ($type == 'deporte') {

        // Se carga el deporte indicado y se almacena el nombre.
        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('nid',$id)
          ->execute();

        $deporte_seleccionado[] = Node::load(current($query))->get('title')->value;

        $this->sport_node = Node::load(current($query));


      // Se carga la competición indicado y se almacena el nombre.
        $query = Drupal::entityQuery('node')
          ->condition('type', 'competicion')
          ->condition('nid',Node::load(current($query))->get('field_competicion')->target_id)
          ->execute();

        $competicion_seleccionada[] = Node::load(current($query))->get('title')->value;

        // Se obtienen los roles del usuario actual
        $roles = $current_user->getRoles();

        // Si el usuario es gestor o administrador
        if(in_array('gestor',$roles) or in_array('administrator',$roles))
          $this->admin=TRUE;

        // En caso contrario
         else {
           $time = time();
           // Se comprueba si ha finalizado el periodo de inscripción de clubes indicado en dicho deporte
            if(MyModuleController::compare_date(format_date($time, 'html_date'),$this->sport_node->field_fecha_de_fin_inscripcion->value)){
              \Drupal::messenger()->addMessage(t("Lo siento no es posible inscribir un club, ha finalizado el período de inscripción."), 'error');
              $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->sport_node->id());

              MyModuleController::my_goto($alias);
              exit();
            }

            // Se hace una consulta de todos los clubes que forman el deporte
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('field_deporte', $this->sport_node->get('nid')->value)
          ->execute();

        foreach ($query as $club) {
          $node_club = Node::Load($club);

          // Se comprueba si el usuario becario ha creado ya un club en dicho deporte
          if ($node_club->getOwner()->id() == $current_user->id()) {

            \Drupal::messenger()->addMessage(t("Ya ha añadido un equipo al deporte. Espere su aprobación o elimínelo para poder crear otro. "), 'error');
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->sport_node->id());

            MyModuleController::my_goto($alias);

          }
        }

        }

         // Se comprueba que no se ha alcanzado el número máximo de clubes especificado en dicho deporte
        if($this->sport_node->get('field_numero_maximo_de_equipos')->value > 0 and $this->sport_node->get('field_numero_maximo_de_equipos')->value == $this->sport_node->get('field_numero_de_equipos')->value){
          \Drupal::messenger()->addMessage(t("Se ha alcanzado el número máximo de clubes para este deporte "), 'error');
          $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->sport_node->id());

          MyModuleController::my_goto($alias);
        }
      }

      // En caso de editar un club
      else{


        // Se carga el club y el deporte y competición en los que está inscrito.
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('nid', $id)
          ->execute();

        $this->club_node = Node::load(current($query));
        $competicion_seleccionada = array($this->club_node->field_competicion->entity->get('title')->value);
        $deporte_seleccionado = array($this->club_node->field_deporte->entity->get('title')->value);


      // Se obtienen los roles del usuario actual
        $roles = $current_user->getRoles();

        // Si el usuario es gestor o administrador
        if(in_array('gestor',$roles) or in_array('administrator',$roles))
          $this->admin=TRUE;
        //En caso contrario
        else{

          // Se comprueba si el usuario becario es el creador del club a editar
          if($this->club_node->getOwner()->id() != $current_user->id()){

            \Drupal::messenger()->addMessage(t("No se puede editar un club que nos ha sido creado por uno mismo."), 'error');

            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->club_node->field_deporte->entity->get('nid')->value);

            MyModuleController::my_goto($alias);

          }
        }

      }


    }
    else {
      $competicion_seleccionada = $nombres_competiciones;
      $deporte_seleccionado = $this->dicc;
    }









    // Se define el campo para el nombre del club y sus atributos
    $form['nombre'] = array(
      '#type' => 'textfield',
      '#title' => '<b>Nombre del equipo :</b>',
      '#required' => TRUE,

    );







// Se define el campo para la competición del club y sus atributos
    $form['competicion'] = [
      '#type' => 'select',
      '#title' => '<b>Competición :</b>',
      '#validated' => TRUE,
      '#options' => $competicion_seleccionada,


    ];



// Se define el campo para el deporte del club y sus atributos
    $form['deporte'] = [
      '#type' => 'select',
      '#title' => t('<b>Deporte :</b>'),
      '#options' => $deporte_seleccionado,
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
      '#validated' => TRUE,



    ];





    // Si el usuario es administrador o gestor
    if($this->admin) {

// Se define el campo para el grupo del club y sus atributos
      $form['grupo'] = array(
        '#type' => 'number',
        '#title' => t('<b> Grupo :</b>'),
        '#validated' => TRUE,

      );

      // Se define el campo para el estado del club y sus atributos
      $form['estado'] = array(
        '#type' => 'select',
        '#title' => t('<b>Estado del club :</b>'),
        '#options' => array('Activado', 'Desactivado', 'Pendiente'),
        '#validated' => TRUE,


      );

      // Si se está editando el club
      if ($this->tipo == 'club') {

        // Se define el campo para los puntos del club y sus atributos
        $form['puntos'] = array(
          '#type' => 'number',
          '#title' => t('<b> Puntos :</b>'),
          '#validated' => TRUE,

        );

        // Se define el campo para los puntos a favor del club y sus atributos
        $form['goles_a_favor'] = array(
          '#type' => 'number',
          '#title' => t('<b> Puntos/goles a favor :</b>'),
          '#validated' => TRUE,

        );

        // Se define el campo para los puntos en contra del club y sus atributos
        $form['goles_en_contra'] = array(
          '#type' => 'number',
          '#title' => t('<b> Puntos/goles en contra :</b>'),
          '#validated' => TRUE,

        );

        // Se define el campo para la diferencia de puntos del club y sus atributos
        $form['diferencia'] = array(
          '#type' => 'number',
          '#title' => t('<b> Diferencia puntos/goles :</b>'),
          '#validated' => TRUE,

        );
      }
    }




// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' =>'<div class=" text-center">',
      '#suffix'=>'</div>',
    ];



    if(!is_null($id)) {

      // Si se está editando el club, se aplican los valores del nodo a los campos del formulario como valores por defecto.
      if ($this->tipo == 'club') {
        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Editar club - ' . $this->club_node->get('title')->value . '</b></li></ul>');
        $form['nombre']['#default_value'] = $this->club_node->get('title')->value;
        if($this->admin) {
          $form['grupo']['#default_value'] = $this->club_node->get('field_grupo')->value;
          $form['estado']['#default_value'] = array_search($this->club_node->get('field_estado_club')->value,array('Activado', 'Desactivado','Pendiente'));
          $form['puntos']['#default_value'] = $this->club_node->get('field_puntos')->value;
          $form['goles_a_favor']['#default_value'] = $this->club_node->get('field_puntos_goles_a_favor')->value;
          $form['goles_en_contra']['#default_value'] = $this->club_node->get('field_puntos_goles_en_contra')->value;
          $form['diferencia']['#default_value'] = $this->club_node->get('field_diferencia_puntos_goles')->value;



        }



      } else {

        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir club </b></li><li><span class="fa fa-trophy"></span></li></ul>');


      }}
    else{

      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir club </b></li><li><span class="glyphicon glyphicon-plus-sign"></span></li></ul>');

      $form['competicion']['#ajax'] = [
        'callback' => '::myAjaxCallback',
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering
        'event' => 'change',
        'wrapper' => 'edit-output',
        'method' => 'replace',
      ];

      }

    // Se adjunta las bibliotecas definidas en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';
    $form['#attached']['library'][] = 'local_league_management/bootstrap';



return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que realiza una llamada Ajax, de forma que una seleccionado una competición se actualice el campo de
  // deporte en función de ésta.
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

  ###################################################################################################################
  ####################################################################################################################

  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    // Se obtiene el nuevo alias del club
    $nombre_club = str_replace(" ", "-",strtolower($form_state->getValue('nombre')));

    // Se extrae el nombre de la competición seleccionada y se carga
    $competicion_clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$competicion_clave];

    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('title', $competicion_name)
      ->execute();

    $competicion_node = Node::load(current($query));



    $deporte_clave = $form_state->getValue('deporte');



  // Se carga el deporte seleccionado
    if(is_null($this->tipo)){

    $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];

      $query = Drupal::entityQuery('node')
        ->condition('type', 'deporte')
        ->condition('title', $deporte_name)
        ->condition('field_competicion', ($competicion_node)->get('nid')->value)
        ->execute();

    $this->sport_node = Node::load(current($query));


    } elseif($this->tipo=='club'){

      $this->sport_node = $this->club_node->field_deporte->entity;
    }


    // Se hace una consulta de todos los clubes de dicho deporte
    $query = Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('field_deporte', ($this->sport_node)->get('nid')->value)
      ->execute();

   if (!empty($query)) {

      foreach ($query as $club) {

        $club_names[] = Node::load($club)->get('field_alias')->value;
      }

      // Se comprueba si existe un club con el mismo nombre apuntado a dicho deporte
      if (in_array($nombre_club, $club_names) and  !is_null($this->club_node) and ($nombre_club != $this->club_node->get('field_alias')->value) or ((in_array($nombre_club,$club_names) and is_null($this->club_node)))) {

        $option_club = &$form['nombre'];
        $form_state->setError($option_club, $this->t("Ese club ya está inscrito en esa competición"));
      }
    }

   // Se comprueba que el gestor o administrador no introduzca un número de grupo negativo
   if($this->admin and $form_state->getValue('grupo')<0){
     $option_club = &$form['grupo'];
     $form_state->setError($option_club, $this->t("El grupo de club no puede ser negativo"));


   }


  }
  ###################################################################################################################
  ####################################################################################################################

// Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {


    // Se extrae la competición seleccionada
    $competicion_clave = $form_state->getValue('competicion');
    $competicion_name = &$form['competicion']['#options'][$competicion_clave];



    $query = Drupal::entityQuery('node')
      ->condition('type', 'competicion')
      ->condition('title', $competicion_name)
      ->execute();

    $competicion_node = Node::load(current($query));

    $deporte_clave = $form_state->getValue('deporte');

    if(is_null($this->tipo) or ($this->tipo=='club')) {
      $deporte_name = &$form['deporte']['#options'][$competicion_name][$deporte_clave];
    }

    else{

      $deporte_name =  ($this->sport_node)->get('title')->value;
    }

    // Si se edita el club, se actualizan los valores de éste con los nuevos datos introducidos
    if(!is_null($this->club_node)){

      drupal_set_message($this->t('<b>Club editado: </b> @nombre ',
        [ '@nombre' => $form_state->getValue('nombre'),
        ])
      );

      $this->club_node->set('title',  $form_state->getValue('nombre'));
      $this->club_node->set('field_competicion', ($competicion_node)->get('nid')->value);
      $this->club_node->set('field_deporte', $this->sport_node->get('nid')->value);
      $this->club_node->set('field_alias', str_replace(" ", "-",strtolower($form_state->getValue('nombre'))));



      // Si el usuario es gestor o administrador, se modifican las propiedades oportunas
      if($this->admin) {

        $estado_value = $form_state->getValue('estado');
        $estado_club = $form['estado']['#options'][$estado_value];



        $this->club_node->set('field_estado_club', $estado_club);
        $this->club_node->set('field_grupo', $form_state->getValue('grupo'));
        $this->club_node->set('field_puntos', $form_state->getValue('puntos'));
        $this->club_node->set('field_puntos_goles_a_favor', $form_state->getValue('goles_a_favor'));
        $this->club_node->set('field_puntos_goles_en_contra', $form_state->getValue('goles_en_contra'));
        $this->club_node->set('field_diferencia_puntos_goles', $form_state->getValue('diferencia'));
        if($estado_club=='Activado'){

          $this->club_node->set('status', TRUE);

        }

        elseif ($estado_club !='Activado'){
          $this->club_node->set('status', FALSE);}


      }




      $this->club_node->save();
      MyModuleController::update($this->club_node);

      if ($this->admin)
      MyModuleController::my_goto(\Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->club_node->get('nid')->value));
  else
  MyModuleController::my_goto(\Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $this->club_node->field_deporte->entity->get('nid')->value));

    }

    // En caso contrario se crea un nuevo club en dicho deporte
    else{

      drupal_set_message($this->t('<b>Club inscrito:</b> @nombre en @deporte - @competicion',
        ['@nombre' => $form_state->getValue('nombre'),
          '@competicion' => $competicion_name,
          '@deporte' => $deporte_name,
        ])
      );



      $id_sport = $this->sport_node->get('nid')->value;

      // Si el usuario es gestor o administador, se extraen ciertos campos del formulario
      if($this->admin) {
        $estado_value = $form_state->getValue('estado');
        $estado = $form['estado']['#options'][$estado_value];
        $grupo = $form_state->getValue('grupo');
      }
      else {
        $estado = 'Pendiente';
        $grupo = NULL;
      }


      $alias = MyModuleController::create_node_club($form_state->getValue('nombre'), $id_sport, 0, $competicion_name,$grupo,$competicion_node->get('nid')->value,$estado,NULL);
      if ($this->admin)
      MyModuleController::my_goto($alias);
      else
        MyModuleController::my_goto(\Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $id_sport));



    }


    // Se actualiza el número de clubes del deporte referenciado.
    $query = Drupal::entityQuery('node')
      ->condition('type', 'club')
      ->condition('status',TRUE)
      ->condition('field_deporte',($this->sport_node)->get('nid')->value)
      ->execute();


    $num_equipos = count($query);
    $this->sport_node->set('field_numero_de_equipos', $num_equipos);
    $this->sport_node->save();











  }


}
