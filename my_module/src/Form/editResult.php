<?php


namespace Drupal\my_module\Form;


use Drupal\my_module\Controller\MyModuleController;
use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class editResult extends FormBase
{

  protected $competicion_names;

  public function getFormId() {
    return 'my_module_addcompeticionform';
  }

  public function buildForm(array $form, FormStateInterface $form_state,$partido=NULL) {


    $form['local'] = array(
      '#type' => 'number',
      '#title' => t("hola"),
      '#required' => TRUE,


    );
/*
    $form['visitante'] = array(
      '#type' => 'number',
      '#title' => t(($partido->get('field_equipo_visitante')->value)),
      '#required' => TRUE,


    );
*/

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }



  public function validateForm(array &$form, FormStateInterface $form_state) {



  }

  public function submitForm(array &$form, FormStateInterface $form_state) {



  }






}
