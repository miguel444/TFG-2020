<?php

namespace Drupal\local_league_management\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\local_league_management\Controller\MyModuleController;
use Drupal\file\Entity\File;
use Drupal\user\Entity\Role;

class CreateUser extends FormBase {


  protected $user;

  ###################################################################################################################
  ####################################################################################################################

  // Función de devuelve el identificador del formulario
  public function getFormId()
  {
    return 'my_module_adduser';
  }

  ###################################################################################################################
  ####################################################################################################################


  // Función principal que se encarga de construir el formulario en base a los elementos añadidos y sus propiedades,
// se trabaja con una matriz renderizable $form que se convierte en HTML a la hora de mostrarle el formulario al usuario.
  public function buildForm(array $form, FormStateInterface $form_state,$id=NULL) {

    $roles = \Drupal\user\Entity\Role::loadMultiple();

    if (!empty($roles)) {
      foreach ($roles as $rol) {

        if($rol->id() != 'administrator' and $rol->id() != 'authenticated' and $rol->id() != 'anonymous' )
        $roles_names[$rol->id()] = $rol->label();}}



    if(!is_null($id)){

      $query = Drupal::entityQuery('user')
        ->condition('uid', $id)
        ->execute();

      $this->user = User::load(current($query));
    }




    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('<b>Nombre de usuario :</b>'),
      '#required' => TRUE,
      '#validated' => TRUE,



    );

    if(is_null($id)) {
      $form['password'] = array(
        '#type' => 'password',
        '#title' => t('<b>Contraseña :</b>'),
        '#validated' => TRUE,
        '#required' => TRUE,


      );
    }

    $form['email'] = array(
      '#type' => 'email',
      '#title' => t('<b> Correo electrónico: </b>'),
      '#validated' => TRUE,
      '#required' => TRUE,


    );

    $form['rol'] = array(
      '#type' => 'select',
      '#title' => $this->t('<b>Rol aplicado :</b>'),
       '#options' => $roles_names,
      '#validated' => TRUE,
      '#required' => TRUE,

    );


// Se define el botón de enviar formulario y sus propiedades
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#prefix' => '<div id="edit-submit">',
      '#suffix' => '</div>',
    ];




   if(is_null($id))
    $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Añadir usuario </b></li><li><span class="glyphicon glyphicon-user"></span></li></ul>');
  else{

      $form['#title'] = $this->t('<ul class="list-inline text-center"> <li><b> Editar usuario - ' . $this->user->get('name')->value . '</b></li></span></li></ul>');
      $form['username']['#default_value'] = $this->user->get('name')->value;
      $form['password']['#default_value'] =$this->user->get('pass')->value;
      $form['email']['#default_value'] = $this->user->get('mail')->value;
      $form['rol']['#default_value'] = array_search($roles_names[$this->user->get('roles')->target_id],$roles_names);


    }

    // Se adjunta las bibliotecas definidas en el módulo para modificar el estilo del formulario
    $form['#attached']['library'][] = 'local_league_management/local_league_management.styles';
    $form['#attached']['library'][] = 'local_league_management/bootstrap';


    return $form;
  }

  ###################################################################################################################
  ####################################################################################################################

  // Función que recibe los datos introducidos por el usuario y se encarga de realizar una serie de validaciones de
// cara a su posterior procesamiento.
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Se cargan todos los nombres de usuarios del sistema
    $users = \Drupal\user\Entity\User::loadMultiple();
    if(!empty($users)) {
      foreach ($users as $user) {
                $users_names[] = $user->get('name')->value;

      }


    }

    // Se comprueba si existe otro usuario con el mismo nombre de usuario que el introducido
    if (in_array($form_state->getValue('username'), $users_names) and is_null($this->user) or (in_array($form_state->getValue('username'), $users_names) and !is_null($this->user) and $form_state->getValue('username') != $this->user->get('mail')->value)) {

      $option_club = &$form['username'];
      $form_state->setError($option_club, $this->t("El nombre de usuario seleccionado ya se encuentra dado de alta en la base de datos del sistema."));
    }


  }
  ###################################################################################################################
  ####################################################################################################################

  // Función que ,una vez realizada la validación de los datos de entrada, procede a realizar su procesamiento.
  // En este caso se tratará de añadir o editar un nodo y todas las entidades que hagan referencia a éste.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    // Se extraen los valores introducidos
    $value = $form_state->getValue('rol');
    $rol_name = &$form['rol']['#options'][$value];

    // Si es un nuevo usuario , se crea un usuario asociado
    if (is_null($this->user)) {

      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $user = \Drupal\user\Entity\User::create();




      $user->setPassword($form_state->getValue('password'));
      $user->enforceIsNew();
      $user->setEmail($form_state->getValue('email'));
      $user->setUsername($form_state->getValue('username'));//This username must be unique and accept only a-Z,0-9, - _ @


      $roles = \Drupal\user\Entity\Role::loadMultiple();

      // Se aplica el rol seleccionado
      if (!empty($roles)) {
        foreach ($roles as $rol) {
          if ($rol->label() == $rol_name)
            $user->addRole($rol->id());
        }
      }



      $user->set("init", $form_state->getValue('email'));
      $user->set("langcode", $language);
      $user->set("preferred_langcode", $language);
      $user->set("preferred_admin_langcode", $language);
      //$user->set("setting_name", 'setting_value');
      $user->activate();

//Save user
      $res = $user->save();


      drupal_set_message($this->t('<div id="title" align="center"><b>Nuevo usuario </b></div> <b>Username:</b> @nombre <br> <b>Correo electrónico :</b> @email <br> <b>Rol :</b> @rol',
        ['@nombre' => $form_state->getValue('username'),
          '@email' => $form_state->getValue('email'),
          '@rol' => $rol_name,
        ])
      );

      // En caso de editar el usuario, se aplican los valores introducidos.
    } else{



      $this->user->set('name', $form_state->getValue('username'));
      $this->user->set('mail', $form_state->getValue('email'));
      $this->user->removeRole($this->user->getRoles()[1]);
      $roles = \Drupal\user\Entity\Role::loadMultiple();

      if (!empty($roles)) {
        foreach ($roles as $rol) {
          if ($rol->label() == $rol_name)
            $this->user->addRole($rol->id());
        }
      }

      $this->user->save();

      drupal_set_message($this->t('<div id="title" align="center"><b>Usuario editado </b></div> <b>Username:</b> @nombre <br> <b>Correo electrónico :</b> @email <br> <b>Rol :</b> @rol',
        ['@nombre' => $form_state->getValue('username'),
          '@email' => $form_state->getValue('email'),
          '@rol' => $rol_name,
        ])
      );

  }





}



}
