<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\local_league_management\Controller\MyModuleController;

class AddArbitro extends FormBase
{

  // Nodo del árbitro a tratar
  protected $arbitro_node;

  ###################################################################################################################
  ####################################################################################################################

  // Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_addarbitroform';
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {

    // Se define el campo para el nombre el árbitro y sus atributos
    $form['nombre'] = array(
      '#type' => 'textfield',
      '#title' => t('<b>Nombre del árbitro :</b>'),
      '#required' => TRUE,


    );

    // Se define el campo para los apellidos el árbitro y sus atributos
    $form['apellidos'] = array(
      '#type' => 'textfield',
      '#title' => t('<b>Apellidos :</b>'),
      '#validated' => TRUE,


    );

    // Se define el campo para el dni el árbitro y sus atributos
    $form['dni'] = array(
      '#type' => 'number',
      '#title' => t('<b>DNI sin letra :</b>'),
      '#required' => TRUE,
      '#validated' => TRUE,

    );

// Se define el campo para el número de colegiado del árbitro y sus atributos
    $form['numero_colegiado'] = array(
      '#type' => 'number',
      '#title' => t('<b>Número de colegiado :</b>'),
      '#required' => TRUE,
      '#validated' => TRUE,

    );

    // Se define el campo para el correo electrónico el árbitro y sus atributos
    $form['correo'] = array(
      '#type' => 'email',
      '#title' => t('<b>Correo electrónico :</b>'),
      '#required' => TRUE,

    );

    // Se define el campo para el nombre de usuario del árbitro y sus atributos
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('<b>Nombre de usuario :</b>'),
      '#required' => TRUE,
      '#validated' => TRUE,



    );



    // Se define el campo para la contraseña del árbitro y sus atributos, que solo se muestra a la hora de añadir un árbitro
    if(is_null($id)) {
      $form['password'] = array(
        '#title' => t('<b>Contraseña :</b>'),
        '#type' => 'password',
        '#validated' => TRUE,
        '#required' => TRUE,


      );

    }



    // Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
    ];


    $this->arbitro_node = $id;

    // Si se quiere editar un árbitro
    if (!is_null($id)) {

      // Se realiza un consulta del árbitro definido por el identificador $id
      $query = Drupal::entityQuery('node')
        ->condition('type', 'arbitro')
        ->condition('nid', $id)
        ->execute();

      // Se carga el nodo
      $this->arbitro_node = Node::load(current($query));

      // Se aplican los valores del nodo a los campos del formulario como valores por defecto.
      $form['nombre']['#default_value'] = $this->arbitro_node->get('title')->value;
      $form['#title'] = $this->t('<ul class="list-inline text-center"><li><b> Editar árbitro - ' . $this->arbitro_node->get('title')->value . '</b></li></ul>');
      $form['correo']['#default_value'] = $this->arbitro_node->get('field_correo_electronico')->value;

      $form['apellidos']['#default_value'] = $this->arbitro_node->get('field_apellidos')->value;
      $form['dni']['#default_value'] = $this->arbitro_node->get('field_dni')->value;
      $form['numero_colegiado']['#default_value'] = $this->arbitro_node->get('field_numero_colegiado')->value;


      $form['username']['#default_value'] = User::load(current($query))->get('name')->value;

    } else
      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir árbitro </b></li></ul>');

   // Se adjunta una biblioteca definida en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';

    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

// Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    // Se cargan los usuarios del sistema y se almancena el nombre de usuario de cada uno de ellos
    $users = \Drupal\user\Entity\User::loadMultiple();
    if (!empty($users)) {
      foreach ($users as $user) {
        $users_names[] = $user->get('name')->value;

      }
    }


      // Si hay usuarios en el sistema
      if (!empty($users_names)) {

        // Se comprueba que no exista un usuario con el mismo nombre de usuario que el introducido
      if (in_array($form_state->getValue('username'), $users_names) and (is_null($this->arbitro_node) or (!is_null($this->arbitro_node) and ($form_state->getValue('username') != $this->arbitro_node->field_arbitro_user->entity->get('name')->value) and in_array($form_state->getValue('username'), $users_names)))) {

        $option_competicion = &$form['username'];
        $form_state->setError($option_competicion, $this->t("Ya existe un usuario con ese nombre de usuario"));
      }


    }

      // Se hace una consulta de todos los árbitros del sistema
      $query =  $query = Drupal::entityQuery('node')
        ->condition('type', 'arbitro')
        ->execute();

      // Si hay árbitros, se almacena el número de colegiado y DNI de cada uno dellos
      if(!empty($query)) {
        foreach ($query as $arbitro) {
          $numeros_colegiados[] = Node::load($arbitro)->get('field_numero_colegiado')->value;
          $dnis[] = Node::load($arbitro)->get('field_dni')->value;
        }

        // Se comprueba que no exista un árbitro con el mismo número de colegiado que el introducido
        if ((in_array($form_state->getValue('numero_colegiado'), $numeros_colegiados)) and (is_null($this->arbitro_node)) or ((!is_null($this->arbitro_node) and ($form_state->getValue('numero_colegiado') != $this->arbitro_node->get('field_numero_colegiado')->value) and in_array($form_state->getValue('numero_colegiado'), $numeros_colegiados)))) {

          $option_competicion = &$form['numero_colegiado'];
          $form_state->setError($option_competicion, $this->t("Ya existe un árbitro con ese número de colegiado"));
        }

        // Se comprueba que no exista un usuario con el mismo DNI que el introducido
        if (in_array($form_state->getValue('dni'), $dnis) and (is_null($this->arbitro_node) or (!is_null($this->arbitro_node) and ($form_state->getValue('dni') != $this->arbitro_node->get('field_dni')->value) and in_array($form_state->getValue('dni'), $dnis)))) {

          $option_competicion = &$form['dni'];
          $form_state->setError($option_competicion, $this->t("Ya existe un árbitro con ese número de indentificación"));
        }
      }




  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {


    // Se recogen los datos introducidos por el usuario
    $nombre = $form_state->getValue('nombre');
    $apellidos = $form_state->getValue('apellidos');
    $dni = $form_state->getValue('dni');
    $numero_colegiado = $form_state->getValue('numero_colegiado');
    $correo = $form_state->getValue('correo');
    $username = $form_state->getValue('username');



   // Si se va a crear un árbitro, se crea una cuenta de usuario asociada a éste
   if(is_null($this->arbitro_node)) {



     $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
     $user = \Drupal\user\Entity\User::create();


     $user->setPassword($form_state->getValue('password'));
     $user->enforceIsNew();
     $user->setEmail($correo);
     $user->setUsername($username);

     $roles = \Drupal\user\Entity\Role::loadMultiple();

     if (!empty($roles)) {

       // Se le asigna rol arbitro
       foreach ($roles as $rol) {
         if ($rol->id() == 'arbitro')
           $user->addRole($rol->id());
       }
     }


     $user->set("init", $correo);
     $user->set("langcode", $language);
     $user->set("preferred_langcode", $language);
     $user->set("preferred_admin_langcode", $language);

     $user->activate();

     $res = $user->save();

     // Se crea el árbitro con los datos introducidos y haciendo referencia al usuario creado
     MyModuleController::create_node_arbitro($nombre, $apellidos, $correo, $dni, $numero_colegiado, $user->id());

     drupal_set_message($this->t('<div id="title" align="center"><b>Arbitro añadido</b></div> <b>Nombre:</b> @nombre <br> <b>Correo electrónico :</b> @email <br> <b> Número colegiado :</b> @ficha',
       ['@nombre' => $nombre,
         '@email' => $correo,
         '@ficha' => $numero_colegiado,
       ])
     );
   }

   // En caso de editar un árbitro
   else{


      // Se actualizan los datos de dicho árbitro con los datos introducidos
      $this->arbitro_node->set('title', $nombre);
      $this->arbitro_node->set('field_apellidos', $apellidos);
      $this->arbitro_node->set('field_correo_electronico', $correo);
      $this->arbitro_node->set('field_dni', $dni);
      $this->arbitro_node->set('field_numero_colegiado', $numero_colegiado);


      // Se cambia el nombre de usuario del usuario que referencia
        $query = Drupal::entityQuery('user')
          ->condition('uid', $this->arbitro_node->get('field_arbitro_user')->target_id)
          ->execute();

        $usuario = User::load(current($query));
        $usuario->set('name', $username);
        $usuario->save();




    // Se guardan los cambios
     $this->arbitro_node->save();

      drupal_set_message($this->t('<div id="title" align="center"><b>Arbitro editado</b></div> <b>Nombre:</b> @nombre <br> <b>Correo electrónico :</b> @email <br> <b>Número colegiado :</b> @ficha',
        ['@nombre' => $nombre,
          '@email' => $correo,
          '@ficha' => $numero_colegiado,
        ])
      );

    }






  }

}
