<?php
/**
 * @file
 * Contains \Drupal\csv_og_upload\Form\OgForm.
 */
namespace Drupal\csv_og_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class GroupMemberForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_member_table_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['csv_upload'] = array(
			'#type' => 'managed_file',
			'#title' => $this->t('Upload CSV For groups user'),
			'#upload_location' => 'public://og',
			'#upload_validators' => [
				'file_validate_extensions' => ['csv'],
			],
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    );
    return $form;
  }


 /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
        
    \Drupal::database()->truncate('group_content_field_data')->execute();
    \Drupal::database()->truncate('group_content')->execute();
    
    /* Fetch the array of the file stored temporarily in database */
        
    //Fetch gid and uid
    $csv_file = $form_state->getValue('csv_upload');

    /* Load the object of the file by it's fid */
    $file = File::load( $csv_file[0] );

    /* Set the status flag permanent of the file object */
    $file->setPermanent();

    /* Save the file in database */
    $file->save();

    // You can use any sort of function to process your data.
    // The goal is to get each 'row' of data into an array
    // If you need to work on how data is extracted, process it here.
		$data = $this->csvtoarray($file->getFileUri(), ',');
		$connection = \Drupal::database();
    $uuid_service = \Drupal::service('uuid');
    foreach($data as $row) {
      $query = \Drupal::database()->select('groups_field_data', 'gp');
      $query->addField('gp', 'id');
      $query->addField('gp', 'uid');
      $query->condition('gp.label', $row['group']);
      $group_data = $query->execute()->fetchAll();
      $date = date_create($row['Created']);
      $result1 = $connection->insert('group_content')
        ->fields([
            'type' =>  'group-group_membership',
            'langcode' => 'en',
            'uuid' => $uuid_service->generate(),  
        ])
        ->execute();
            
      // Group field data
      $result1 = $connection->insert('group_content_field_data')
			->fields([
        'id' =>  $row['Sl'],
        'type' =>  'group-group_membership',
        'langcode' => 'en',
				'uid' => $group_data[0]->uid,
        'gid' => $group_data[0]->id,
        'label' => $row['label'],
        'entity_id' =>$row['Uid'],
				'created' => date_timestamp_get($date),
				'changed' => date_timestamp_get($date),
				'default_langcode' => 1,
			])
        ->execute();
    }
	}

  //Function to convert csv to array format
	public function csvtoarray($filename='', $delimiter){
    if(!file_exists($filename) || !is_readable($filename)) return FALSE;
    $header = NULL;
    $data = array();

    //Check for the existence of the file
    if (($handle = fopen($filename, 'r')) !== FALSE ) {
      while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
      {
        if(!$header){
          $header = $row;
        }
        else{
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }
    return $data;
  }

}