<?php

/**
* @file
* Contains \Drupal\my_module\Controller\MyModuleController
*/

namespace Drupal\my_module\Controller;

use Drupal;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\RouteCollection;


class MyModuleController extends ControllerBase{

    public $nodos_competiciones ;
    public $nodos_clubes ;
    public $nodos_jugadores;

	public function __construct(){

        // Sacar datos de la base de datos
        $query = Drupal::entityQuery('node')
                ->condition('type', 'competicion')
                ->execute();
        
        if (!empty($query)) {
			foreach ($query as $competicion) {
                    $this->nodos_competiciones[] = Node::load($competicion);}}
        
        
        $query = Drupal::entityQuery('node')
                ->condition('type', 'club')
                ->execute();

        if (!empty($query)) {
            foreach ($query as $club) {
                $this->nodos_clubes[] = Node::load($club);}}
        
        $query = Drupal::entityQuery('node')
                ->condition('type', 'jugador')
                ->execute();
                
        if (!empty($query)) {
            foreach ($query as $jugador) {
                $this->nodos_jugadores[] = Node::load($jugador);}}

    }

    
    public static function create_node_club($nombre,$num_jugadores,$competicion){

        $node = Node::create(array(
            'type' => 'club',
            'title' => $nombre,
            'field_deporte' => $competicion,
            'field_numero_de_jugadores' => $num_jugadores,
        ));

        $node->save();

        //$this->update_nodes(1);
    }

    public static function create_node_jugador($nombre,$fecha,$club,$correo,$telefono,$foto){

        $node = Node::create(array(
            'type' => 'jugador',
            'title' => $nombre,
            'field_club' => $club,
            'field_fecha' => $fecha,
            'field_correo_electronico' => $correo,
            'field_telefono' => $telefono,
            'field_foto_jugador' => $foto,));

        $node->save();

        //$this->update_nodes(1);
    }
	

    public static function create_node_deporte($nombre,$num_equipos,$competicion){

        $node = Node::create(array(
            'type' => 'deporte',
            'title' => $nombre,
            'field_numero_de_equipos' => $num_equipos,
            'field_competicion' => $competicion,
        ));

        $node->save();

        //$this->update_nodes(0);
    }

    public static function create_node_competicion($nombre,$num_deportes){

        $node = Node::create(array(
            'type' => 'competicion',
            'title' => $nombre,
            'field_numero_de_deportes' => $num_deportes,
        ));

        $node->save();

        //$this->update_nodes(0);
    }

    public static function my_goto($path) { 
        $response = new RedirectResponse($path);
        $response->send();
        return;
      }



    public function settings(){
        foreach ($this->nodos_clubes as $clubes ) {
            
               
                $aux[] =  $clubes->get('field_numero_de_jugadores')->value;
            
        }

        return array(
            '#type' => 'item',
            '#markup' => t("Hola mundo"),

        );
    }
	

	public function test() {
		
        return array(
            '#type' => 'item',
            '#markup' => t("Hola mundo"),

        );

    }
    






/*
namespace Drupal\my_module\Form;



class FormularioClub extends FormBase {
  
    public function getFormId() {
        return 'my_module_formuaarioclub';
      }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['nombre'] = array(
        '#type' => 'textfield',
        '#title' => t('Nombre del equipo :'),
        '#required' => TRUE,

    );

    $form['jugadores'] = array(
        '#type' => 'number',
        '#title' => t('Numero de jugadores :'),
        '#required' => TRUE,

    );

    $form['competicion'] = array(
        '#type' => 'select',
        '#title' => t('Competicion :'),
        '#required' => TRUE,

    );

    $form['accept'] = array(
        '#type' => 'checkbox',
        '#title' => t('Acepto los términos de uso de esta web'),
        '#description' =>t('Por favor lee y acepta las condiciones de uso'),
      );

    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      ];
    
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Valores: @nombre / @apellido', 
        [ '@nombre' => $form_state->getValue('nombre'),
          '@apellido' => $form_state->getValue('apellido'),
        ])
    );
  }
}*/


public function add_menu() {

    $query = Drupal::entityQuery('node')
                ->condition('type', 'competicion')
                ->execute();
        
        

    return Node::load(array_pop($query));
 
  }

}
?>
