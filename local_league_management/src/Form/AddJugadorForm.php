<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\local_league_management\Controller\MyModuleController;

class AddJugadorForm extends FormBase
{

  // Nodo de la entidad a trabajar (club o jugador)
  protected $node_entity;

  // Indica si el usuario es adminstrador o gestor
  protected $admin=FALSE;

  // Indica el tipo de entidad
  protected $tipo;

  ###################################################################################################################
  ####################################################################################################################

  // Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_addplayerform';
  }
  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {

    // Se carga el usuario actual
    $current_user = \Drupal::currentUser();


    if(!is_null($id)) {

      $node = node_load($id);

      $type = $node->getType($node);
      $this->tipo = $type;

      // Si se crea un nuevo jugador
      if ($type == 'club') {

        // Se extraen los roles del usuario actual
        $roles = $current_user->getRoles();

        // Si el usuario no es gestor ni administrador
        if (!in_array('gestor', $roles) and !in_array('administrator', $roles)) {
          $node_club = Node::load($id);

          // Si el usuario es becario se comprueba que haya creado el club del jugador a añadir
          if (in_array('becario', $roles) and $node_club->getOwner()->id() != $current_user->id()) {

            \Drupal::messenger()->addMessage(t("No se puede añadir un jugador a un club que nos ha sido creado por uno mismo."), 'error');
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_club->get('nid')->value);

            MyModuleController::my_goto($alias);

          }

          // Si el usuario es capitán se comprueba que sea el capitán del club en el que se intenta añadir el jugador
          elseif (in_array('capitan', $roles) and $node_club->get('field_capitan')->target_id != $current_user->id()){

            \Drupal::messenger()->addMessage(t("No se puede añadir un jugador a un club del que no se es capitán"), 'error');
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_club->get('nid')->value);

            MyModuleController::my_goto($alias);

          }

          // Si el club no está activo, no se puede añadir un jugador
          if ($node_club->get('status') != TRUE){
            \Drupal::messenger()->addMessage(t("No se puede añadir un jugador al club. Espere que el club sea aprobado para poder realizar su gestión"), 'error');
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_club->get('nid')->value);

            MyModuleController::my_goto($alias);

          }
        }

        // Si se está editando un jugador
      } else {

        // Se extraen los roles del usuario actual
        $roles = $current_user->getRoles();

        // Si el usuario no es gestor ni administrador
        if (!in_array('gestor', $roles) and !in_array('administrator', $roles))
        {


          $node_jugador = Node::load($id);

          // Si el usuario es becario se comprueba que haya creado el club del jugador
          if (in_array('becario', $roles) and $node_jugador->field_club->entity->getOwner()->id() != $current_user->id()) {

            \Drupal::messenger()->addMessage(t("No se puede editar un jugador de un club que no ha sido creado por uno mismo."), 'error');

            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_jugador->field_club->entity->get('nid')->value);

            MyModuleController::my_goto($alias);

          }

          // Si es usuario es capitán se comprueba que sea capitán del club del jugador a editar
          elseif (in_array('capitan', $roles)){
            $jugadores = Drupal::entityQuery('node')
              ->condition('type', 'club')
              ->condition('field_capitan',$current_user->id())
              ->condition('nid', $node_jugador->field_club->entity->get('nid')->value)
              ->execute();


            if(empty($jugadores)) {

              \Drupal::messenger()->addMessage(t("No se puede editar un jugador de un club del que no se es capitán"), 'error');

              $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_jugador->field_club->entity->get('nid')->value);

              MyModuleController::my_goto($alias);

            }
          }
        }
        else{

          $this->admin=TRUE;

        }

      }
    }




// Se define el campo para el DNI del jugador y sus atributos
    $form['dni'] = array(
      '#type' => 'number',
      '#title' => t('DNI sin letra :'),
      '#required' => TRUE,
      '#validated' => TRUE,

    );

    // Se define el campo para la ficha deportiva del jugador y sus atributos
    $form['ficha_deportiva'] = array(
      '#type' => 'number',
      '#title' => t('Ficha deportiva :'),
      '#required' => TRUE,
      '#validated' => TRUE,

    );

    // Se define el campo para el nombre del jugador y sus atributos
    $form['nombre'] = array(
      '#type' => 'textfield',
      '#title' => t('Nombre del jugador :'),
      '#required' => TRUE,

    );

    // Se define el campo para los apellidos del jugador y sus atributos
    $form['apellidos'] = array(
      '#type' => 'textfield',
      '#title' => t('Apellidos del jugador :'),
      '#required' => TRUE,

    );


// Se define el campo para el club del jugador y sus atributos
    $form['club'] = array(
      '#type' => 'select',
      '#title' => t('Club :'),
      '#validated' => TRUE,
      '#options' => [],

    );

    // Se define el campo para el correo electronico del jugador y sus atributos
    $form['correo'] = array(
      '#type' => 'email',
      '#title' => t('Correo electrónico :'),
      '#required' => TRUE,

    );

    // Si el usuario es gestor o administrador y se está editando un jugador
    if($this->admin){
      // Se define el campo para el número de partidos disputados del jugador y sus atributos
      $form['partidos'] = array(
        '#type' => 'number',
        '#title' => t('Partidos disputados :'),
        '#validated' => TRUE,

      );

      // Se define el campo para el número de puntos del jugador y sus atributos
      $form['puntos'] = array(
        '#type' => 'number',
        '#title' => t('Puntos/Goles :'),
        '#validated' => TRUE,

      );

      // Se define el campo para indicar si está expulsado el jugador y sus atributos
      $form['expulsado'] = array(
        '#type' => 'checkbox',
        '#title' => t('Expulsado'),
        '#validated' => TRUE,

      );


    }


    $form['capitan'] = array(
      '#type' => 'details',
      '#title' => $this->t('<b>Capitán</b>'),
      '#group' => 'information',
      '#open' => TRUE,

    );

    // Se define el campo para el capitán del club y sus atributos
    $form['capitan']['opcion'] = array(
      '#title' => t('Indicar si este jugador es capitán'),
      '#type' => 'checkbox',
      '#validated' => TRUE,

    );

// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [

      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
    ];

    if (!is_null($id)) {

      $node = node_load($id);

      $type = $node->getType($node);

      $this->tipo = $type;

      // Si se está creando un jugador
      if ($type == 'club') {
        $query = Drupal::entityQuery('node')
          ->condition('type', 'club')
          ->condition('nid', $id)
          ->execute();

        $this->node_entity = Node::load(current($query));
        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir jugador - ' . $this->node_entity->get('title')->value . '</b></li></ul>');
        $form['club']['#options'] = [$this->node_entity->get('title')->value];

        // Si se está editando un jugador, se aplican los valores del jugador como valores por defecto.
      } else {
        $query = Drupal::entityQuery('node')
          ->condition('type', 'jugador')
          ->condition('nid', $id)
          ->execute();

        $this->node_entity = Node::load(current($query));



        $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Editar jugador - ' . $this->node_entity->get('title')->value . '</b></li></ul>');
        $form['club']['#options'] = [$this->node_entity->field_club->entity->getTitle()];
        $form['nombre']['#default_value'] = $this->node_entity->get('title')->value;
        $form['apellidos']['#default_value'] = $this->node_entity->get('field_apellidos')->value;
        $form['dni']['#default_value'] = $this->node_entity->get('field_dni')->value;
        $form['ficha_deportiva']['#default_value'] = $this->node_entity->get('field_ficha_deportiva')->value;
        $form['correo']['#default_value'] = $this->node_entity->get('field_correo_electronico')->value;

        if ($this->node_entity->get('field_capitan_jugador')->value =='Si')
          $form['capitan']['opcion']['#attributes'] = array('checked' => 'checked');
        else
          $form['capitan']['opcion']['#attributes'] = array('unchecked' => 'unchecked');

        if($this->admin){
          $form['partidos']['#default_value'] = $this->node_entity->get('field_partidos_disputados')->value;
          $form['puntos']['#default_value'] = $this->node_entity->get('field_goles')->value;
          if ($this->node_entity->get('field_expulsado')->value ==1)
          $form['expulsado']['#attributes'] = array('checked' => 'checked');
          else
            $form['expulsado']['#attributes'] = array('unchecked' => 'unchecked');



        }


      }
    }

    // Se adjunta la biblioteca definida en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/bootstrap';


    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

  // Se obtiene los valores introducidos por el ususario
    $ficha = $form_state->getValue('ficha_deportiva');
    $dni = $form_state->getValue('dni');

    if (!is_null($this->node_entity)) {

      // Se carga el deporte
      if ($this->tipo == 'club') {

        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('nid', $this->node_entity->get('field_deporte')->target_id)
          ->execute();
      } else {


        $query = Drupal::entityQuery('node')
          ->condition('type', 'deporte')
          ->condition('nid', $this->node_entity->field_club->entity->get('field_deporte')->target_id)
          ->execute();

        if ($this->admin) {
          // Se comprueba que no se seleccione un número negativo de puntos/goles
          if ($form_state->getValue('goles') < 0){

            $option_ficha = &$form['goles'];
            $form_state->setError($option_ficha, $this->t("El número de goles/puntos no puede ser negativo"));

          }


          if($form_state->getValue('partidos')<0){
          // Se comprueba que no se seleccione un número negativo de partidos disputados
            $option_ficha = &$form['partidos'];
            $form_state->setError($option_ficha, $this->t("No se puede establecer un número negativo en partidos"));

          }


      }
      }


    }


    // Se cargan todos los jugadores del deporte y se almacenan las fichas deportivas  y DNI
    if (!empty($query)) {

      $deporte = Node::load(current($query));

      $query = Drupal::entityQuery('node')
        ->condition('type', 'club')
        ->condition('field_deporte', $deporte->get('nid')->value)
        ->execute();

      $fichas_jugadores = array();

      if (!empty($query)) {
        foreach ($query as $club) {
          $club_loaded = Node::load($club);

          $consulta = Drupal::entityQuery('node')
            ->condition('type', 'jugador')
            ->condition('field_club', $club_loaded->get('nid')->value)
            ->execute();

          if (!empty($consulta)) {
            foreach ($consulta as $jugador) {
              $fichas_jugadores[] = Node::load($jugador)->get('field_ficha_deportiva')->value;
              $dnis[] = Node::load($jugador)->get('field_dni')->value;


            }

          }

        }

      }

      // Se comprueba que no exista otro jugador con el mismo número de ficha deportiva
      if ((in_array($ficha, $fichas_jugadores) and $this->tipo == 'club') or ((in_array($ficha, $fichas_jugadores) and $this->tipo == 'jugador' and $form_state->getValue('ficha_deportiva') != $this->node_entity->get('field_ficha_deportiva')->value))) {

        $option_ficha = &$form['ficha_deportiva'];
        $form_state->setError($option_ficha, $this->t("Ese número de ficha ya está inscrito en este deporte"));
      }

      // Se comprueba que no exista otro jugador con el mismo número de DNI
      if ((in_array($dni, $dnis) and $this->tipo == 'club') or ((in_array($dni, $dnis) and $this->tipo == 'jugador' and $form_state->getValue('dni') != $this->node_entity->get('field_dni')->value))) {

        $option_ficha = &$form['dni'];
        $form_state->setError($option_ficha, $this->t("Ese número de DNI ya está inscrito en este deporte"));
      }







    }


    if($form_state->getValue('opcion') ==1){


        // Si se selecciona el jugador como capitán, se comprueba que no exista otro capitán en el club
      if(($this->tipo=='club' and !is_null($this->node_entity->get('field_capitan')->target_id)) or ($this->tipo=='jugador' and !is_null($this->node_entity->field_club->entity->get('field_capitan')->target_id) and $this->node_entity->get('field_capitan_jugador')->value != 'Si')){
        $fecha = &$form['capitan']['opcion'];
        $form_state->setError($fecha, $this->t("Ya existe un capitán activo en este club. Elimine el jugador o edite dicha condición."));


      }

    }


  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {


    // Se obtienen los valores introducidos por el usuario
    $nombre = $form_state->getValue('nombre');
    $apellidos = $form_state->getValue('apellidos');
    $dni = $form_state->getValue('dni');
    $ficha = $form_state->getValue('ficha_deportiva');
    $correo = $form_state->getValue('correo');

    // Se obtienen los usuarios con el mismo número de ficha
    $query = Drupal::entityQuery('user')
      ->condition('name', $ficha)
      ->execute();

    // Si se selecciona como capitán y no es capitán de ningún otro club de otro deporte, se procede
    // a crear un usuario asociado con rol de capitán
    if ($form_state->getValue('opcion') == 1 and count($query) == 0){




      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $user = \Drupal\user\Entity\User::create();


      $user->setPassword($dni);
      $user->enforceIsNew();
      $user->setEmail($correo);
      $user->setUsername($ficha);

      $roles = \Drupal\user\Entity\Role::loadMultiple();

      if (!empty($roles)) {

        foreach ($roles as $rol) {
          if ($rol->id() == 'capitan')
            $user->addRole($rol->id());
        }
      }


      $user->set("init", $correo);
      $user->set("langcode", $language);
      $user->set("preferred_langcode", $language);
      $user->set("preferred_admin_langcode", $language);

      $user->activate();

      $res = $user->save();


      $capitan = 'Si';

      // Una vez creado el usuario , se asigna como capitán del club
      if($this->tipo=='club') {
        $this->node_entity->set('field_capitan', $user->id());
        $this->node_entity->save();

      }
      elseif ($this->tipo=='jugador') {
        $this->node_entity->field_club->entity->set('field_capitan', $user->id());
        $this->node_entity->field_club->entity->save();
      }

      // Si se selecciona como capitán pero ya es capitán en otro club, no se crea usuario y
      // se asocia el usuario correspondiente como capitán del club
    } elseif ($form_state->getValue('opcion') == 1) {
      $capitan = 'Si';
      if($this->tipo=='club') {
        $this->node_entity->set('field_capitan', User::load(current($query))->get('uid')->value);
        $this->node_entity->save();

      }
      elseif ($this->tipo=='jugador') {
        $this->node_entity->field_club->entity->set('field_capitan', User::load(current($query))->get('uid')->value);
        $this->node_entity->field_club->entity->save();
      }

    } // Si no se selecciona como capitán
    elseif ($form_state->getValue('opcion') == 0 ) {

      $capitan = 'No';
    }

    // Si se está editando jugador
    if ($this->tipo == 'jugador') {

        // Si se deselecciona como capitán
      if($form_state->getValue('opcion') == 0 and $this->node_entity->get('field_capitan_jugador')->value == 'Si') {
        // Si es capitán solo en este club, se elimina la cuenta de usuario
        if(count($query)==1)
        user_delete($this->node_entity->field_club->entity->get('field_capitan')->target_id);

        // Se modifica el capitán del club
        $this->node_entity->field_club->entity->set('field_capitan',NULL);
        $this->node_entity->field_club->entity->save();

      }

      // Se guardan las modificaciones del jugador
      $this->node_entity->set('title', $nombre);
      $this->node_entity->set('field_apellidos', $apellidos);
      $this->node_entity->set('field_correo_electronico', $correo);
      $this->node_entity->set('field_dni', $dni);
      $this->node_entity->set('field_ficha_deportiva', $ficha);
      $this->node_entity->set('field_capitan_jugador', $capitan);
      if($this->admin){
        $this->node_entity->set('field_goles', $form_state->getValue('puntos'));
        $this->node_entity->set('field_partidos_disputados', $form_state->getValue('partidos'));
        if($form_state->getValue('expulsado')==1)
        $this->node_entity->set('field_expulsado', TRUE);
        else
          $this->node_entity->set('field_expulsado', FALSE);
      }


      $this->node_entity->save();

      drupal_set_message($this->t('<div id="title" align="center"><b>Jugador editado</b></div> <b>Nombre:</b> @nombre <br> <b>Correo electrónico :</b> @email <br> <b>Ficha :</b> @ficha',
        ['@nombre' => $nombre,
          '@email' => $correo,
          '@ficha' => $ficha,
        ])
      );

      // Si se está creando un jugador
    } else {

      MyModuleController::create_node_jugador($nombre, $apellidos, $this->node_entity->get('nid')->value, $correo, $dni, $ficha, $capitan);


      // Se actualiza el número de jugadores del club asociado.
      $query = Drupal::entityQuery('node')
        ->condition('type', 'jugador')
        ->condition('field_club',($this->node_entity)->get('nid')->value)
        ->execute();

      $num_equipos = count($query);
      $this->node_entity->set('field_numero_de_jugadores', $num_equipos);
      $this->node_entity->save();

      drupal_set_message($this->t('<div id="title" align="center"><b>Jugador inscrito</b></div> <b>Nombre:</b> @nombre <br> <b>Correo electrónico :</b> @email <br> <b>Ficha :</b> @ficha',
        ['@nombre' => $nombre,
          '@email' => $correo,
          '@ficha' => $ficha,
        ])
      );


    }




  }
}
